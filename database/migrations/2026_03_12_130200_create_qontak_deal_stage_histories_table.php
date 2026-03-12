<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('qontak_deal_stage_histories', function (Blueprint $table) {
            $table->id();

            $table->foreignId('qontak_deal_id')
                ->nullable()
                ->constrained('qontak_deals')
                ->nullOnDelete();

            $table->unsignedBigInteger('crm_deal_id')->index();

            $table->string('current_owner')->nullable();

            $table->string('prev_stage_id')->nullable();
            $table->string('prev_stage_name')->nullable()->index();

            $table->string('current_stage_id')->nullable();
            $table->string('current_stage_name')->nullable()->index();

            $table->timestamp('moved_date')->nullable()->index();
            $table->string('moved_by')->nullable();

            $table->string('fingerprint')->unique();
            $table->json('raw')->nullable();

            $table->timestamps();

            $table->index(['crm_deal_id', 'moved_date'], 'idx_qontak_stage_history_deal_moved');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qontak_deal_stage_histories');
    }
};
