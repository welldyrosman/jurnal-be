<?php
// database/migrations/xxxx_xx_xx_create_qontak_deals_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('qontak_deals', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('deal_id')->unique(); // ID dari Qontak
            $table->string('name')->nullable();
            $table->string('slug')->nullable();

            $table->timestamp('created_at_qontak')->nullable();
            $table->timestamp('updated_at_qontak')->nullable();

            $table->string('currency', 10)->nullable();

            $table->string('crm_pipeline_id')->nullable();
            $table->string('crm_pipeline_name')->nullable();
            $table->string('crm_stage_id')->nullable();
            $table->string('crm_stage_name')->nullable();

            $table->string('creator_id')->nullable();
            $table->string('creator_name')->nullable();

            $table->string('unique_deal_id')->nullable();
            $table->string('idempotency_key')->nullable();

            $table->json('raw')->nullable(); // simpan backup seluruh response 1 record

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qontak_deals');
    }
};
