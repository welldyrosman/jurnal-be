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
        Schema::create('qontak_deal_products', function (Blueprint $table) {
            $table->id();

            $table->foreignId('qontak_deal_id')
                ->constrained('qontak_deals')
                ->cascadeOnDelete();

            $table->foreignId('qontak_product_id')
                ->nullable()
                ->constrained('qontak_products')
                ->nullOnDelete();
            $table->string('crm_product_id')->nullable();
            $table->string('product_name')->nullable();

            $table->decimal('quantity', 10, 2)->default(1);
            $table->decimal('price', 15, 2)->default(0);
            $table->decimal('total_price', 15, 2)->storedAs('quantity * price');
            $table->timestamps();
            $table->index(['product_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qontak_deal_products');
    }
};
