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
        Schema::create('jurnal_sales_invoice_tags', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('jurnal_sales_invoice_id');
            $table->string('tag_name');
            $table->timestamps();

            $table->foreign('jurnal_sales_invoice_id')
                ->references('id')
                ->on('jurnal_sales_invoices')
                ->onDelete('cascade');

            $table->index(['jurnal_sales_invoice_id', 'tag_name']);
        });
    }

    /**
     * Reverse the migrations. 
     */
    public function down(): void
    {
        Schema::dropIfExists('jurnal_sales_invoice_tags');
    }
};
