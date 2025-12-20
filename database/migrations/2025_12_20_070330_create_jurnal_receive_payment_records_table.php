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
        Schema::create('jurnal_receive_payment_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('jurnal_receive_payment_id');
            $table->unsignedBigInteger('jurnal_record_id')->comment('ID record dari API Jurnal');
            $table->unsignedBigInteger('jurnal_transaction_id')->comment('ID transaksi yang dibayar');

            $table->decimal('amount', 15, 2)->comment('Jumlah pembayaran untuk transaksi ini');
            $table->text('description')->nullable();

            // Transaction Info
            $table->unsignedBigInteger('transaction_type_id')->comment('ID tipe transaksi (1 = Sales Invoice)');
            $table->string('transaction_type')->comment('Tipe transaksi (Sales Invoice, Bill, dll)');
            $table->string('transaction_no')->comment('Nomor transaksi yang dibayar');
            $table->date('transaction_due_date')->nullable();

            // Transaction Amount Info
            $table->decimal('transaction_total', 15, 2)->comment('Total transaksi asli');
            $table->decimal('transaction_balance_due', 15, 2)->comment('Sisa yang harus dibayar');

            $table->timestamps();

            // Foreign key
            $table->foreign('jurnal_receive_payment_id')
                ->references('id')
                ->on('jurnal_receive_payments')
                ->onDelete('cascade');

            // Indexes
            $table->index('jurnal_record_id');
            $table->index('jurnal_transaction_id');
            $table->index('jurnal_receive_payment_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jurnal_receive_payment_records');
    }
};
