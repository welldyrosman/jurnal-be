<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jurnal_invoices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('jurnal_id')->unique();

            // Relasi ke tabel persons
            $table->foreignId('person_id')->nullable()->constrained('jurnal_persons')->nullOnDelete();
            
            $table->string('transaction_no')->nullable()->index();
            $table->string('status')->nullable();
            $table->string('source')->nullable();
            $table->text('address')->nullable();
            $table->text('message')->nullable();
            $table->text('memo')->nullable();
            $table->text('shipping_address')->nullable();
            $table->boolean('is_shipped')->default(false);
            $table->string('reference_no')->nullable();
            
            // Gunakan decimal untuk semua nilai moneter untuk presisi
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('discount_price', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('shipping_price', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0); // Sesuai dengan 'original_amount'
            $table->decimal('payment_received', 15, 2)->default(0); // Sesuai dengan 'payment_received_amount'
            $table->decimal('remaining', 15, 2)->default(0);
            $table->decimal('deposit', 15, 2)->default(0);

            $table->string('term_name')->nullable();
            $table->string('transaction_status_name')->nullable();
            $table->char('currency_code', 3)->default('IDR');

            $table->date('transaction_date')->nullable();
            $table->date('due_date')->nullable();
            $table->date('shipping_date')->nullable();

            $table->timestamp('created_at_jurnal')->nullable();
            $table->timestamp('updated_at_jurnal')->nullable();
            $table->timestamp('deleted_at_jurnal')->nullable();

            $table->json('raw_data')->nullable(); // Sangat direkomendasikan untuk menyimpan data asli
            $table->timestamp('synced_at')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jurnal_invoices');
    }
};
