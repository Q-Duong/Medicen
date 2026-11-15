<?php

namespace App\Console\Commands;

use App\Services\Claude\DepartmentAgentProvisioner;
use Illuminate\Console\Command;
use Throwable;

/**
 * Dựng hoặc cập nhật agent của phòng ban trên Claude.
 * Chạy lại mỗi khi sửa system prompt, skill hoặc tool của phòng ban.
 *
 * php artisan claude:setup sales
 */
class ClaudeSetup extends Command
{
    protected $signature = 'claude:setup {department : Mã phòng ban trong config/claude.php, vd: sales}';

    protected $description = 'Dựng / cập nhật agent Claude của một phòng ban (skill + system prompt + tool)';

    public function handle(DepartmentAgentProvisioner $provisioner): int
    {
        try {
            $result = $provisioner->provision($this->argument('department'), fn (string $line) => $this->line("  {$line}"));
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info("Xong. Agent: {$result['agent']['id']} (v{$result['agent']['version']})");
        $this->line('ID đã lưu ở ' . config('claude.registry_path'));

        return self::SUCCESS;
    }
}
