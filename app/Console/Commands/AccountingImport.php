<?php

namespace App\Console\Commands;

use App\Services\Accounting\MisaImporter;
use Illuminate\Console\Command;
use Throwable;

/**
 * Nhập file sổ sách MISA (Excel) vào database cho trợ lý Kế toán.
 * Vd: php artisan accounting:import "/path/CHI TIẾT 2025-Q22026.xlsx" "/path/Chi tiet luong thuc tra.xlsx"
 * Nhập lại cùng kỳ thì thay dữ liệu cũ (không trùng).
 */
class AccountingImport extends Command
{
    protected $signature = 'accounting:import {files* : Đường dẫn file Excel}';

    protected $description = 'Nhập sổ sách kế toán từ file Excel MISA (NKC, sổ quỹ, ngân hàng, công nợ, lương)';

    public function handle(MisaImporter $importer): int
    {
        $ok = true;
        foreach ($this->argument('files') as $file) {
            $this->info('== ' . basename($file));
            try {
                $rows = $importer->import($file);
                $this->table(['Sheet', 'Sổ', 'Số dòng', 'Từ', 'Đến', 'Ghi chú'], array_map(fn ($r) => [$r['sheet'], $r['book'], $r['rows'], $r['from'], $r['to'], $r['note']], $rows));
            } catch (Throwable $e) {
                $ok = false;
                $this->error($e->getMessage());
            }
        }

        return $ok ? self::SUCCESS : self::FAILURE;
    }
}
