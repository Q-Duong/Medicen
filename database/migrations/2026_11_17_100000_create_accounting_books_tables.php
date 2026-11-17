<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Sổ sách kế toán của cả công ty (XQ + CT), nhập từ file xuất MISA để trợ lý AI Kế toán tra cứu.
 * Chạy riêng file này trên production:
 * php artisan migrate --path=database/migrations/2026_11_17_100000_create_accounting_books_tables.php --force
 */
return new class extends Migration
{
    public function up(): void
    {
        // Mỗi lần nhập một file (để biết số liệu lấy từ đâu, ai nhập, kỳ nào)
        if (!Schema::hasTable('acc_imports')) {
            Schema::create('acc_imports', function (Blueprint $table) {
                $table->id();
                $table->string('file_name');
                $table->string('book', 30);                 // nkc, cash, bank, receivable, payable, salary
                $table->string('sheet', 100)->nullable();
                $table->date('period_from')->nullable();
                $table->date('period_to')->nullable();
                $table->unsignedInteger('rows')->default(0);
                $table->unsignedBigInteger('user_id')->nullable();
                $table->timestamps();
            });
        }

        // Sổ nhật ký chung: mỗi dòng = 1 cặp Nợ / Có
        if (!Schema::hasTable('acc_journal_entries')) {
            Schema::create('acc_journal_entries', function (Blueprint $table) {
                $table->id();
                $table->date('posted_on');
                $table->date('doc_date')->nullable();
                $table->string('doc_no', 50)->nullable();
                $table->string('invoice_no', 50)->nullable();
                $table->text('description')->nullable();
                $table->string('debit_account', 20);
                $table->string('credit_account', 20);
                $table->decimal('amount', 18, 2);
                $table->string('party_code', 50)->nullable();
                $table->unsignedBigInteger('import_id')->nullable();
                $table->index('posted_on');
                $table->index('debit_account');
                $table->index('credit_account');
            });
        }

        // Sổ quỹ tiền mặt (111) và sổ tiền gửi các ngân hàng (112): thu / chi / tồn
        if (!Schema::hasTable('acc_cash_entries')) {
            Schema::create('acc_cash_entries', function (Blueprint $table) {
                $table->id();
                $table->string('book', 20);                 // TIEN_MAT, HDB, VCB, AGRI
                $table->date('posted_on')->nullable();      // null = dòng số dư đầu kỳ
                $table->string('doc_no', 50)->nullable();
                $table->text('description')->nullable();
                $table->string('contra_account', 20)->nullable();
                $table->decimal('receipt', 18, 2)->default(0);   // thu / phát sinh Nợ
                $table->decimal('payment', 18, 2)->default(0);   // chi / phát sinh Có
                $table->decimal('balance', 18, 2)->nullable();   // tồn sau dòng (nếu sổ có)
                $table->boolean('is_opening')->default(false);
                $table->string('party_code', 50)->nullable();
                $table->string('segment', 20)->nullable();       // khoản mục: XQ / CT / VP / VAY
                $table->string('car', 20)->nullable();           // mã công trình: XE1..XE8
                $table->string('item_name', 100)->nullable();    // tên mục thu / chi
                $table->unsignedBigInteger('import_id')->nullable();
                $table->index(['book', 'posted_on']);
            });
        }

        // Tổng hợp công nợ phải thu (131) / phải trả (331) theo kỳ
        if (!Schema::hasTable('acc_party_balances')) {
            Schema::create('acc_party_balances', function (Blueprint $table) {
                $table->id();
                $table->string('kind', 20);                 // receivable / payable
                $table->date('period_from');
                $table->date('period_to');
                $table->string('code', 50)->nullable();
                $table->string('name')->nullable();
                $table->decimal('opening_debit', 18, 2)->default(0);
                $table->decimal('opening_credit', 18, 2)->default(0);
                $table->decimal('debit', 18, 2)->default(0);
                $table->decimal('credit', 18, 2)->default(0);
                $table->decimal('closing_debit', 18, 2)->default(0);
                $table->decimal('closing_credit', 18, 2)->default(0);
                $table->unsignedBigInteger('import_id')->nullable();
                $table->index(['kind', 'period_to']);
            });
        }

        // Lương thực trả theo tháng, tách CT / XQ (không có lương từng người)
        if (!Schema::hasTable('acc_salaries')) {
            Schema::create('acc_salaries', function (Blueprint $table) {
                $table->id();
                $table->unsignedSmallInteger('year');
                $table->unsignedTinyInteger('month')->nullable();   // null = thưởng
                $table->string('kind', 20)->default('luong');       // luong, thuong_le, thuong_tet
                $table->decimal('ct', 18, 2)->default(0);
                $table->decimal('xq', 18, 2)->default(0);
                $table->unsignedBigInteger('import_id')->nullable();
                $table->index(['year', 'month']);
            });
        }

        // Tài liệu: báo cáo tài chính, hợp đồng (file gốc + tóm tắt để trợ lý tra cứu)
        if (!Schema::hasTable('acc_documents')) {
            Schema::create('acc_documents', function (Blueprint $table) {
                $table->id();
                $table->string('type', 20);                 // bctc, hop_dong
                $table->string('title');
                $table->string('file_path')->nullable();    // storage/app/ke-toan/...
                $table->string('parties')->nullable();
                $table->date('signed_on')->nullable();
                $table->date('valid_from')->nullable();
                $table->date('valid_to')->nullable();
                $table->decimal('value', 18, 2)->nullable();
                $table->string('period', 50)->nullable();   // kỳ của BCTC (vd 2025, 6T/2026)
                $table->longText('summary')->nullable();    // tóm tắt nội dung / số liệu chính
                $table->unsignedBigInteger('user_id')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        foreach (['acc_documents', 'acc_salaries', 'acc_party_balances', 'acc_cash_entries', 'acc_journal_entries', 'acc_imports'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
