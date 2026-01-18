<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Tabel Utama Credit Memo
        Schema::create('jurnal_credit_memos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('jurnal_id')->unique(); // ID dari Jurnal
            $table->string('transaction_no')->index(); // 10003
            $table->date('transaction_date')->nullable();

            // Info Customer/Person
            $table->unsignedBigInteger('person_id')->nullable();
            $table->string('person_name')->nullable();

            // Status
            $table->string('status')->nullable(); // Open
            $table->string('status_bahasa')->nullable(); // Belum Dibayar

            // Nominal
            $table->decimal('original_amount', 24, 8)->default(0);
            $table->decimal('remaining_amount', 24, 8)->default(0);
            $table->decimal('witholding_amount', 24, 8)->default(0);

            // Currency
            $table->string('currency_code', 10)->default('IDR');
            $table->decimal('currency_rate', 24, 8)->default(1);

            // Metadata
            $table->text('memo')->nullable();
            $table->text('tags')->nullable();

            // Sync Control
            $table->string('sync_status')->default('pending'); // synced/failed
            $table->timestamp('last_sync_at')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });

        // Tabel Detail Baris (Lines)
        Schema::create('jurnal_credit_memo_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jurnal_credit_memo_id')
                ->constrained('jurnal_credit_memos')
                ->onDelete('cascade');

            $table->unsignedBigInteger('jurnal_line_id')->nullable();

            // Akun Akuntansi
            $table->unsignedBigInteger('account_id')->nullable();
            $table->string('account_name')->nullable();
            $table->string('account_number')->nullable();

            $table->text('description')->nullable();

            // Nilai Debit/Kredit
            $table->decimal('debit', 24, 8)->default(0);
            $table->decimal('credit', 24, 8)->default(0);

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('jurnal_credit_memo_lines');
        Schema::dropIfExists('jurnal_credit_memos');
    }
};
