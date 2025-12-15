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
        Schema::create('qontak_deal_product_associations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('qontak_deal_id')
                ->constrained('qontak_deals')
                ->cascadeOnDelete();

            $table->foreignId('qontak_product_id')
                ->nullable()
                ->constrained('qontak_products')
                ->nullOnDelete();

            $table->bigInteger('crm_association_id')->unique();
            $table->bigInteger('crm_deal_id')->index();
            $table->bigInteger('crm_product_id')->index();

            $table->string('product_name')->nullable();
            $table->string('currency', 10)->nullable();

            $table->decimal('price', 15, 2)->default(0);
            $table->decimal('quantity', 10, 2)->default(1);
            $table->decimal('discount', 15, 2)->default(0);
            $table->decimal('nominal_discount', 15, 2)->default(0);
            $table->decimal('total_price', 15, 2)->default(0);

            $table->json('crm_tag_ids')->nullable();

            $table->timestamps();

            $table->index(
                ['qontak_deal_id', 'qontak_product_id'],
                'idx_qdp_assoc_deal_product'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qontak_deal_product_associations');
    }
};
