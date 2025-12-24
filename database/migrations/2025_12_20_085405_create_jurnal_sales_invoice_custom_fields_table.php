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
        Schema::create('jurnal_sales_invoice_custom_fields', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('jurnal_sales_invoice_id');
            $table->string('field_name')->comment('Nama custom field');
            $table->text('field_value')->nullable()->comment('Nilai custom field');
            $table->timestamps();

            // ✅ Gunakan nama constraint yang lebih singkat (max 64 karakter)
            $table->foreign('jurnal_sales_invoice_id', 'jsicf_jsi_id_fk')
                ->references('id')
                ->on('jurnal_sales_invoices')
                ->onDelete('cascade');

            $table->index('jurnal_sales_invoice_id');
        });
    }

    /**
     * Reverse the migrations. 
     */
    public function down(): void
    {
        Schema::dropIfExists('jurnal_sales_invoice_custom_fields');
    }
};
