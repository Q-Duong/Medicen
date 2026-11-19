<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Công nợ CT (máy CT đặt tại bệnh viện hợp tác) + Sổ TK VCB chú Lộc, nhập trên web thay cho Excel.
 * - ct_partners: bệnh viện hợp tác (Á Châu, Bảo Lộc, Cà Mau, Gò Công, Lagi-YSG…)
 * - ct_receivables: mỗi bệnh viện mỗi tháng một dòng (doanh thu, chi phí, chia lãi, Medicen thu về)
 * - ct_payments: các lần bệnh viện thanh toán (HDB / L-VCB); L-VCB tự sinh dòng thu ở sổ VCB
 * - vcb_entries: sổ thu / chi TK VCB chú Lộc (dòng 'opening' = số dư đầu do kế toán chốt)
 * - vcb_recurrings: khoản trừ cố định hằng tháng (gốc lãi căn A44, xe VF7…)
 * - acc_memos: ghi nhớ số cũ / công nợ cũ không còn hoạt động (không tính vào báo cáo)
 * - report_items.auto_key: dòng báo cáo thu chi lấy số tự động (vcb, ct:AC, ct:AC:bhyt…)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ct_partners', function (Blueprint $t) {
            $t->id();
            $t->string('code', 10)->unique();
            $t->string('name', 100);
            $t->boolean('active')->default(true);
            $t->unsignedSmallInteger('sort')->default(0);
            $t->timestamps();
        });

        Schema::create('ct_receivables', function (Blueprint $t) {
            $t->id();
            $t->foreignId('partner_id')->constrained('ct_partners');
            $t->unsignedSmallInteger('year');
            $t->unsignedTinyInteger('month');
            $t->string('kind', 10)->default('thuong'); // thuong | bhyt
            $t->string('label', 100)->nullable();       // vd "bhyt Q1+2"
            $t->unsignedInteger('quantity')->nullable();
            $t->bigInteger('revenue')->default(0);
            $t->bigInteger('cost')->default(0);
            $t->bigInteger('hospital_share')->default(0);
            $t->bigInteger('medicen_share')->default(0);
            $t->bigInteger('hospital_collect')->default(0);
            $t->bigInteger('medicen_collect')->default(0);
            $t->text('note')->nullable();
            $t->unsignedBigInteger('user_id')->nullable();
            $t->timestamps();
            $t->index(['year', 'month']);
        });

        Schema::create('vcb_recurrings', function (Blueprint $t) {
            $t->id();
            $t->string('description', 255);
            $t->bigInteger('chi');
            $t->unsignedTinyInteger('day')->default(1);
            $t->boolean('active')->default(true);
            $t->timestamps();
        });

        Schema::create('vcb_entries', function (Blueprint $t) {
            $t->id();
            $t->date('entry_date');
            $t->string('description', 255);
            $t->bigInteger('thu')->default(0);
            $t->bigInteger('chi')->default(0);
            $t->string('source', 20)->default('manual'); // opening | manual | ct | recurring | import
            $t->unsignedBigInteger('ct_payment_id')->nullable()->index();
            $t->foreignId('recurring_id')->nullable()->constrained('vcb_recurrings')->nullOnDelete();
            $t->char('period', 7)->nullable(); // YYYY-MM, chống tạo trùng khoản cố định
            $t->text('note')->nullable();
            $t->unsignedBigInteger('user_id')->nullable();
            $t->timestamps();
            $t->index('entry_date');
            $t->unique(['recurring_id', 'period']);
        });

        Schema::create('ct_payments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('receivable_id')->constrained('ct_receivables')->cascadeOnDelete();
            $t->date('paid_on');
            $t->string('method', 10); // HDB | L-VCB | M-VCB (cũ)
            $t->bigInteger('amount');
            $t->string('note', 255)->nullable();
            $t->unsignedBigInteger('user_id')->nullable();
            $t->timestamps();
            $t->index('paid_on');
        });

        Schema::create('acc_memos', function (Blueprint $t) {
            $t->id();
            $t->string('scope', 10); // ct | vcb
            $t->string('title', 255);
            $t->string('period', 50)->nullable();
            $t->bigInteger('amount')->nullable();
            $t->text('note')->nullable();
            $t->unsignedSmallInteger('sort')->default(0);
            $t->timestamps();
        });

        Schema::table('report_items', function (Blueprint $t) {
            $t->string('auto_key', 30)->nullable()->after('summary_group');
        });

        // Gắn khóa tự động cho các dòng đang có (theo tên dòng mẫu)
        $map = [
            '- Tiền hiện có (VCB chú Lộc)' => 'vcb',
            '- CT Á Châu' => 'ct:AC',
            '- CT Á Châu (bhyt)' => 'ct:AC:bhyt',
            '- CT Bảo Lộc' => 'ct:BL',
            '- CT Cà Mau' => 'ct:CM',
            '- CT Gò Công' => 'ct:GC',
            '- CT Lagi' => 'ct:LG',
        ];
        foreach ($map as $name => $key) {
            DB::table('report_items')->where('name', $name)->update(['auto_key' => $key]);
        }
    }

    public function down(): void
    {
        Schema::table('report_items', fn (Blueprint $t) => $t->dropColumn('auto_key'));
        Schema::dropIfExists('acc_memos');
        Schema::dropIfExists('ct_payments');
        Schema::dropIfExists('vcb_entries');
        Schema::dropIfExists('vcb_recurrings');
        Schema::dropIfExists('ct_receivables');
        Schema::dropIfExists('ct_partners');
    }
};
