<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Địa chỉ của đơn vị (trang Quản lý đơn vị). Chạy riêng file này trên production:
 * php artisan migrate --path=database/migrations/2026_11_16_100000_add_address_to_units_table.php --force
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('units', 'unit_address')) {
            Schema::table('units', function (Blueprint $table) {
                $table->string('unit_address', 255)->nullable()->after('unit_tax_code');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('units', 'unit_address')) {
            Schema::table('units', function (Blueprint $table) {
                $table->dropColumn('unit_address');
            });
        }
    }
};
