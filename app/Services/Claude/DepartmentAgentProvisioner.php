<?php

namespace App\Services\Claude;

use Anthropic\Beta\Agents\BetaManagedAgentsAgentToolset20260401Params;
use Anthropic\Beta\Agents\BetaManagedAgentsAgentToolsetDefaultConfigParams;
use Anthropic\Beta\Agents\BetaManagedAgentsAlwaysAllowPolicy;
use Anthropic\Beta\Agents\BetaManagedAgentsCustomSkillParams;
use Anthropic\Beta\Agents\BetaManagedAgentsCustomToolParams;
use Anthropic\Beta\Agents\BetaManagedAgentsGlobToolConfigParams;
use Anthropic\Beta\Agents\BetaManagedAgentsGrepToolConfigParams;
use Anthropic\Beta\Agents\BetaManagedAgentsReadToolConfigParams;
use Anthropic\Beta\Environments\BetaCloudConfigParams;
use Anthropic\Beta\Environments\BetaLimitedNetworkParams;
use Anthropic\Client;
use Anthropic\Core\FileParam;
use App\Services\Claude\Tools\DepartmentTools;
use Illuminate\Support\Facades\File;
use InvalidArgumentException;

/**
 * Dựng / cập nhật agent của một phòng ban trên Claude.
 * Chạy ở bước setup (php artisan claude:setup), không chạy trong request của người dùng.
 */
class DepartmentAgentProvisioner
{
    public function __construct(private Client $client, private AgentRegistry $registry)
    {
    }

    /**
     * @param callable(string): void $log
     */
    public function provision(string $department, callable $log): array
    {
        $config = config("claude.departments.{$department}");
        if (!$config) {
            throw new InvalidArgumentException("Phòng ban '{$department}' chưa khai báo trong config/claude.php");
        }

        $environmentId = $this->ensureEnvironment($log);

        $skills = [];
        foreach ($config['skills'] ?? [] as $dir) {
            $skills[] = $this->syncSkill($dir, $log);
        }

        /** @var DepartmentTools $tools */
        $tools = app($config['tools']);

        $agent = $this->syncAgent($department, $config, $skills, $tools, $log);

        return ['environment_id' => $environmentId, 'agent' => $agent, 'skills' => $skills];
    }

    private function ensureEnvironment(callable $log): string
    {
        if ($id = $this->registry->get('environment_id')) {
            $log("Environment đã có: {$id}");

            return $id;
        }

        // Agent chỉ cần đọc file skill trong container: không mở mạng ra ngoài
        $environment = $this->client->beta->environments->create(
            name: 'medicen-departments',
            description: 'Môi trường chung cho các trợ lý phòng ban Medicen',
            config: BetaCloudConfigParams::with(
                networking: BetaLimitedNetworkParams::with(
                    allowMCPServers: false,
                    allowPackageManagers: false,
                    allowedHosts: [],
                ),
            ),
        );

        $this->registry->put('environment_id', $environment->id);
        $log("Đã tạo environment: {$environment->id}");

        return $environment->id;
    }

    /**
     * Upload skill lần đầu, hoặc tạo version mới khi nội dung thay đổi.
     *
     * @return array{name: string, id: string, version: string}
     */
    private function syncSkill(string $dir, callable $log): array
    {
        if (!File::exists("{$dir}/SKILL.md")) {
            throw new InvalidArgumentException("Không thấy {$dir}/SKILL.md");
        }

        $name = basename($dir);
        $files = collect(File::allFiles($dir))->sortBy(fn ($f) => $f->getRelativePathname())->values();
        $hash = sha1($files->map(fn ($f) => $f->getRelativePathname() . "\0" . $f->getContents())->implode("\0"));

        $saved = $this->registry->get("skills.{$name}");
        if ($saved && ($saved['hash'] ?? null) === $hash) {
            $log("Skill {$name} không đổi: {$saved['id']} @ {$saved['version']}");

            return ['name' => $name, 'id' => $saved['id'], 'version' => $saved['version']];
        }

        $params = $files->map(fn ($f) => FileParam::fromString(
            $f->getContents(),
            filename: "{$name}/" . $f->getRelativePathname(),
            contentType: str_ends_with($f->getFilename(), '.md') ? 'text/markdown' : 'text/plain',
        ))->all();

        if ($saved) {
            $version = $this->client->skills->versions->create(skillID: $saved['id'], files: $params);
            $entry = ['id' => $saved['id'], 'version' => $version->id, 'hash' => $hash];
            $log("Skill {$name}: đã tạo version mới {$version->id}");
        } else {
            $skill = $this->client->skills->create(files: $params, displayName: $name);
            $entry = ['id' => $skill->id, 'version' => $skill->latestVersionID, 'hash' => $hash];
            $log("Đã upload skill {$name}: {$skill->id}");
        }

        $this->registry->put("skills.{$name}", $entry);

        return ['name' => $name] + $entry;
    }

    private function syncAgent(string $department, array $config, array $skills, DepartmentTools $tools, callable $log): array
    {
        $readOnly = BetaManagedAgentsAlwaysAllowPolicy::with();

        $toolList = [
            // Chỉ bật công cụ đọc file (để đọc skill); tắt bash/write/edit/web
            BetaManagedAgentsAgentToolset20260401Params::with(
                defaultConfig: BetaManagedAgentsAgentToolsetDefaultConfigParams::with(enabled: false),
                configs: [
                    BetaManagedAgentsReadToolConfigParams::with(enabled: true, permissionPolicy: $readOnly),
                    BetaManagedAgentsGlobToolConfigParams::with(enabled: true, permissionPolicy: $readOnly),
                    BetaManagedAgentsGrepToolConfigParams::with(enabled: true, permissionPolicy: $readOnly),
                ],
            ),
        ];

        foreach ($tools->definitions() as $def) {
            $toolList[] = BetaManagedAgentsCustomToolParams::with(
                description: $def['description'],
                inputSchema: $def['input_schema'],
                name: $def['name'],
            );
        }

        $skillParams = array_map(
            fn ($s) => BetaManagedAgentsCustomSkillParams::with(skillID: $s['id'], version: $s['version']),
            $skills,
        );

        $fields = [
            'name' => $config['name'],
            'description' => $config['description'] ?? null,
            'model' => config('claude.model'),
            'system' => trim(File::get($config['system_prompt'])),
            'tools' => $toolList,
            'skills' => $skillParams,
            'metadata' => ['app' => 'medicen', 'department' => $department],
        ];

        $saved = $this->registry->get("agents.{$department}");

        if ($saved) {
            $agent = $this->client->beta->agents->update($saved['id'], ...($fields + ['version' => $saved['version']]));
            $log($agent->version === $saved['version']
                ? "Agent {$department} không đổi: {$agent->id} v{$agent->version}"
                : "Đã cập nhật agent {$department}: {$agent->id} v{$saved['version']} → v{$agent->version}");
        } else {
            $agent = $this->client->beta->agents->create(...$fields);
            $log("Đã tạo agent {$department}: {$agent->id} v{$agent->version}");
        }

        $entry = ['id' => $agent->id, 'version' => $agent->version];
        $this->registry->put("agents.{$department}", $entry);

        return $entry;
    }
}
