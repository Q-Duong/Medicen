<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * Khoá ngoại theo DB thực tế (dump 2026-11-05). Bỏ qua khoá đã tồn tại.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!$this->hasForeignKey('orders', 'orders_ibfk_1')) {
                $table->foreign('customer_id', 'orders_ibfk_1')->references('id')->on('customers')->onDelete('cascade')->onUpdate('cascade');
            }
            if (!$this->hasForeignKey('orders', 'orders_ibfk_2')) {
                $table->foreign('order_detail_id', 'orders_ibfk_2')->references('id')->on('order_details')->onDelete('cascade')->onUpdate('cascade');
            }
            if (!$this->hasForeignKey('orders', 'orders_ibfk_3')) {
                $table->foreign('unit_id', 'orders_ibfk_3')->references('id')->on('units');
            }
            if (!$this->hasForeignKey('orders', 'orders_ibfk_4')) {
                $table->foreign('status_id', 'orders_ibfk_4')->references('id')->on('status')->onDelete('cascade')->onUpdate('cascade');
            }
        });

        Schema::table('car_ktvs', function (Blueprint $table) {
            if (!$this->hasForeignKey('car_ktvs', 'car_ktvs_ibfk_1')) {
                $table->foreign('order_id', 'car_ktvs_ibfk_1')->references('id')->on('orders')->onDelete('cascade')->onUpdate('cascade');
            }
        });

        Schema::table('accountants', function (Blueprint $table) {
            if (!$this->hasForeignKey('accountants', 'accountants_ibfk_1')) {
                $table->foreign('order_id', 'accountants_ibfk_1')->references('id')->on('orders')->onDelete('cascade')->onUpdate('cascade');
            }
        });

        Schema::table('performance_analysis', function (Blueprint $table) {
            if (!$this->hasForeignKey('performance_analysis', 'performance_analysis_ibfk_1')) {
                $table->foreign('order_id', 'performance_analysis_ibfk_1')->references('id')->on('orders')->onDelete('cascade')->onUpdate('cascade');
            }
            if (!$this->hasForeignKey('performance_analysis', 'performance_analysis_ibfk_2')) {
                $table->foreign('user_id', 'performance_analysis_ibfk_2')->references('id')->on('users');
            }
        });

        Schema::table('excel_uploads', function (Blueprint $table) {
            if (!$this->hasForeignKey('excel_uploads', 'excel_uploads_nguoi_tao_id_foreign')) {
                $table->foreign('nguoi_tao_id', 'excel_uploads_nguoi_tao_id_foreign')->references('id')->on('users')->onDelete('set null');
            }
            if (!$this->hasForeignKey('excel_uploads', 'excel_uploads_nguoi_tiep_nhan_id_foreign')) {
                $table->foreign('nguoi_tiep_nhan_id', 'excel_uploads_nguoi_tiep_nhan_id_foreign')->references('id')->on('users')->onDelete('set null');
            }
            if (!$this->hasForeignKey('excel_uploads', 'excel_uploads_order_id_foreign')) {
                $table->foreign('order_id', 'excel_uploads_order_id_foreign')->references('id')->on('orders')->onDelete('set null');
            }
            if (!$this->hasForeignKey('excel_uploads', 'excel_uploads_unit_id_foreign')) {
                $table->foreign('unit_id', 'excel_uploads_unit_id_foreign')->references('id')->on('units')->onUpdate('cascade');
            }
        });

        Schema::table('profit_reports', function (Blueprint $table) {
            if (!$this->hasForeignKey('profit_reports', 'profit_reports_ibfk_1')) {
                $table->foreign('unit_id', 'profit_reports_ibfk_1')->references('id')->on('units')->onDelete('cascade')->onUpdate('cascade');
            }
        });

        Schema::table('report_items', function (Blueprint $table) {
            if (!$this->hasForeignKey('report_items', 'report_items_report_id_foreign')) {
                $table->foreign('report_id', 'report_items_report_id_foreign')->references('id')->on('reports')->onDelete('cascade');
            }
        });

        Schema::table('tasks', function (Blueprint $table) {
            if (!$this->hasForeignKey('tasks', 'tasks_ibfk_1')) {
                $table->foreign('user_id', 'tasks_ibfk_1')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            }
        });

        Schema::table('blogs', function (Blueprint $table) {
            if (!$this->hasForeignKey('blogs', 'blogs_ibfk_1')) {
                $table->foreign('blog_category_id', 'blogs_ibfk_1')->references('id')->on('blog_categories')->onDelete('cascade')->onUpdate('cascade');
            }
        });
    }

    public function down(): void
    {
    }

    private function hasForeignKey(string $table, string $name): bool
    {
        return DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $table)
            ->where('CONSTRAINT_NAME', $name)
            ->where('CONSTRAINT_TYPE', 'FOREIGN KEY')
            ->exists();
    }
};
