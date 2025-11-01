<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jurnal_invoice_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('jurnal_id')->nullable(); // ID line item dari Jurnal

            // Relasi ke tabel invoices, jika invoice dihapus, line item juga ikut terhapus
            $table->foreignId('invoice_id')->constrained('jurnal_invoices')->cascadeOnDelete();

            $table->string('product_name')->nullable();
            $table->unsignedBigInteger('product_jurnal_id')->nullable(); // ID produk dari Jurnal
            $table->text('description')->nullable();
            
            $table->decimal('quantity', 15, 2)->default(1);
            $table->decimal('rate', 15, 2)->default(0);
            $table->decimal('amount', 15, 2)->default(0);
            
            $table->string('unit_name')->nullable();
            $table->string('tax_name')->nullable();
            $table->decimal('tax_rate', 5, 2)->nullable();
            
            $table->timestamp('created_at_jurnal')->nullable();
            $table->timestamp('updated_at_jurnal')->nullable();
            $table->timestamp('synced_at')->useCurrent();
            $table->timestamps();

            // Index untuk mempercepat query
            $table->index(['invoice_id', 'jurnal_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jurnal_invoice_lines');
    }
};
