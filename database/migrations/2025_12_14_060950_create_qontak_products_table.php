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
        Schema::create('qontak_products', function (Blueprint $table) {
            $table->id();

            $table->string('crm_product_id')->unique()->nullable();
            $table->string('name')->nullable();
            $table->string('currency', 4)->nullable();

            $table->decimal('default_price', 15, 2)->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qontak_products');
    }
};
