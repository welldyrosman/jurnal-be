<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('jurnal_receive_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('jurnal_id')->unique()->comment('ID dari API Jurnal');
            $table->string('transaction_no')->comment('Nomor transaksi');
            $table->string('token')->nullable();
            $table->text('memo')->nullable();
            $table->string('source')->default('import');
            $table->string('custom_id')->nullable();
            $table->string('status')->comment('Status:  approved, pending, rejected, dll');

            // Transaction Status
            $table->unsignedBigInteger('transaction_status_id')->nullable();
            $table->string('transaction_status_name')->comment('Status pembayaran (Lunas, Sebagian, dll)');

            $table->dateTime('deleted_at')->nullable();
            $table->boolean('deletable')->default(true);
            $table->boolean('editable')->default(true);
            $table->string('audited_by')->nullable();

            $table->date('transaction_date')->comment('Tanggal transaksi');
            $table->date('due_date')->nullable();

            // Person (Penerima Pembayaran)
            $table->unsignedBigInteger('person_id')->nullable();
            $table->string('person_name')->comment('Nama penerima pembayaran');
            $table->string('person_email')->nullable();
            $table->text('person_address')->nullable();
            $table->string('person_phone')->nullable();
            $table->string('person_fax')->nullable();

            // Transaction Type
            $table->unsignedBigInteger('transaction_type_id')->nullable();
            $table->string('transaction_type_name')->comment('Tipe transaksi (Receive Payment)');

            // Payment Method
            $table->unsignedBigInteger('payment_method_id')->nullable();
            $table->string('payment_method_name')->comment('Metode pembayaran (Transfer Bank, Cash, dll)');

            // Deposit To (Rekening Tujuan)
            $table->unsignedBigInteger('deposit_to_id')->nullable();
            $table->string('deposit_to_name')->comment('Nama rekening tujuan');
            $table->string('deposit_to_number')->nullable()->comment('Nomor rekening');
            $table->string('deposit_to_category')->nullable()->comment('Kategori:  Cash & Bank');

            $table->boolean('is_draft')->default(false);

            // Withholding/Potongan
            $table->string('withholding_account_name')->nullable();
            $table->string('withholding_account_number')->nullable();
            $table->unsignedBigInteger('withholding_account_id')->nullable();
            $table->decimal('withholding_value', 15, 2)->default(0);
            $table->string('withholding_type')->default('value')->comment('value atau percentage');
            $table->decimal('withholding_amount', 15, 2)->default(0);
            $table->unsignedBigInteger('withholding_category_id')->nullable();

            // Amount
            $table->decimal('original_amount', 15, 2)->comment('Jumlah asli');
            $table->decimal('total', 15, 2)->comment('Total pembayaran');

            // Currency
            $table->string('currency_code')->default('IDR')->comment('Kode mata uang');
            $table->unsignedBigInteger('currency_list_id')->nullable();
            $table->unsignedBigInteger('currency_from_id')->nullable();
            $table->unsignedBigInteger('currency_to_id')->nullable();
            $table->unsignedBigInteger('multi_currency_id')->nullable();

            // Status Flags
            $table->boolean('is_reconciled')->default(false)->comment('Sudah direkonsiliasi');
            $table->boolean('is_create_before_conversion')->default(false);
            $table->boolean('is_import')->default(true)->comment('Dari import Jurnal');
            $table->unsignedBigInteger('import_id')->nullable();
            $table->boolean('skip_at')->default(false);
            $table->boolean('disable_link')->default(false);
            $table->integer('comments_size')->default(0);

            // Local System Fields
            $table->enum('sync_status', ['pending', 'synced', 'failed'])->default('pending')->comment('Status sinkronisasi');
            $table->text('sync_error')->nullable()->comment('Pesan error jika sync gagal');
            $table->dateTime('last_sync_at')->nullable()->comment('Waktu sinkronisasi terakhir');

            $table->timestamps();

            // Indexes
            $table->index('jurnal_id');
            $table->index('transaction_no');
            $table->index('transaction_date');
            $table->index('person_name');
            $table->index('status');
            $table->index('sync_status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jurnal_receive_payments');
    }
};
