<?php

namespace App\Services\Claude;

use Illuminate\Support\Facades\File;
use RuntimeException;

/**
 * Lưu ID các tài nguyên đã tạo trên Claude (environment, skill, agent theo phòng ban)
 * để mỗi lần chạy chỉ tạo session mới, không tạo lại agent.
 *
 * {
 *   "environment_id": "env_...",
 *   "skills": {"nkl-sale": {"id": "skill_...", "version": "..."}},
 *   "agents": {"sales": {"id": "agent_...", "version": 3}}
 * }
 */
class AgentRegistry
{
    public function __construct(private ?string $path = null)
    {
        $this->path ??= config('claude.registry_path');
    }

    public function all(): array
    {
        if (!File::exists($this->path)) {
            return [];
        }

        return json_decode(File::get($this->path), true) ?: [];
    }

    public function get(string $key, $default = null)
    {
        return data_get($this->all(), $key, $default);
    }

    public function put(string $key, $value): void
    {
        $data = $this->all();
        data_set($data, $key, $value);
        File::ensureDirectoryExists(dirname($this->path));
        File::put($this->path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n");
    }

    public function agentFor(string $department): array
    {
        $agent = $this->get("agents.{$department}");
        $environment = $this->get('environment_id');

        if (!$agent || !$environment) {
            throw new RuntimeException("Agent phòng ban '{$department}' chưa được dựng. Chạy: php artisan claude:setup {$department}");
        }

        return ['agent_id' => $agent['id'], 'agent_version' => $agent['version'], 'environment_id' => $environment];
    }
}
