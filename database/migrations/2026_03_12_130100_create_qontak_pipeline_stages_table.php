<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('qontak_pipeline_stages', function (Blueprint $table) {
            $table->id();

            $table->foreignId('qontak_pipeline_id')
                ->nullable()
                ->constrained('qontak_pipelines')
                ->nullOnDelete();

            $table->string('crm_pipeline_id')->index();
            $table->string('crm_stage_id');
            $table->string('name')->nullable();

            $table->integer('stage_order')->nullable()->index();
            $table->boolean('active')->default(true)->index();

            $table->string('crm_status_id')->nullable();
            $table->string('crm_type_id')->nullable();
            $table->string('crm_stage_additional_field_id')->nullable();

            $table->decimal('win_probability', 5, 2)->nullable();

            $table->timestamp('created_at_qontak')->nullable();
            $table->timestamp('updated_at_qontak')->nullable();

            $table->json('raw')->nullable();
            $table->timestamps();

            $table->unique(['crm_pipeline_id', 'crm_stage_id'], 'uq_qontak_pipeline_stage');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qontak_pipeline_stages');
    }
};
