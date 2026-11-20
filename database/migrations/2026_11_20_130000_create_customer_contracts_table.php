<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hợp đồng với khách hàng (đơn vị): nguyên tắc (dài hạn, bảng giá theo số ca) và thời vụ (một đợt, giá trọn gói).
 * Bảng giá / phần cộng thêm / phụ thu khoảng cách lưu JSON để AI đọc file điền sẵn và kế toán sửa trên form.
 * Xem App\Models\CustomerContract, App\Services\Accounting\ContractPricing.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('customer_contracts')) {
            return;
        }
        Schema::create('customer_contracts', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20)->index();                 // nguyen_tac | thoi_vu
            $table->unsignedInteger('unit_id')->nullable()->index();
            $table->string('contract_no', 120)->nullable();
            $table->string('party_a', 255)->nullable();          // tên Bên A như trong hợp đồng
            $table->string('party_a_tax_code', 30)->nullable();
            $table->string('end_company', 255)->nullable();      // thời vụ: chụp cho công ty nào
            $table->string('service', 255)->nullable();         // dịch vụ (X-quang phổi…)
            $table->date('signed_on')->nullable();
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();                // null = đến khi thanh lý
            $table->boolean('auto_renew')->default(false);
            $table->date('service_date')->nullable();            // thời vụ: ngày chụp
            $table->string('service_location', 255)->nullable();
            $table->string('price_mode', 20)->default('theo_ca'); // theo_ca | tron_goi_ngay | tron_goi_dot
            $table->bigInteger('package_price')->nullable();     // giá trọn gói (thời vụ / trọn gói ngày)
            $table->boolean('vat_included')->default(true);
            $table->json('price_tiers')->nullable();             // [{min_cases, max_cases, unit: ca|ngay, price}]
            $table->string('averaging_rule', 500)->nullable();
            $table->json('addons')->nullable();                  // [{name, price_per_case}]
            $table->json('surcharges')->nullable();              // [{from_km, to_km, min_amount, max_amount, vat_included}]
            $table->unsignedSmallInteger('service_radius_km')->nullable();
            $table->unsignedSmallInteger('payment_days')->nullable();
            $table->text('result_delivery')->nullable();
            $table->text('notable_terms')->nullable();
            $table->text('note')->nullable();
            $table->unsignedInteger('order_id')->nullable()->index(); // thời vụ: đơn hàng tương ứng
            $table->string('file_path', 255)->nullable();
            $table->string('file_name', 255)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_contracts');
    }
};
