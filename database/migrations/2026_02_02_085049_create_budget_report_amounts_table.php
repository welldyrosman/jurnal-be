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
        Schema::create('budget_report_amounts', function (Blueprint $table) {
            $table->id();
            $table->morphs('amountable');
            $table->integer('period_index');
            $table->string('period_label');
            $table->decimal('amount', 18, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('budget_report_amounts');
    }
};
