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
        Schema::create('budget_report_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_report_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->tinyInteger('line_type');
            $table->boolean('has_sub_label')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('budget_report_lines');
    }
};
