<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('qontak_dashboard_overrides', function (Blueprint $table) {
            $table->id();
            $table->string('metric_code')->index();
            $table->enum('period_type', ['year', 'month', 'date']);
            $table->string('period_key');
            $table->decimal('value', 20, 2);
            $table->text('note')->nullable();
            $table->boolean('active')->default(true)->index();
            $table->timestamps();

            $table->unique(['metric_code', 'period_type', 'period_key'], 'uq_qontak_dashboard_overrides_metric_period');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qontak_dashboard_overrides');
    }
};
