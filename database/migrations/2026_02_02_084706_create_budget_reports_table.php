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
        Schema::create('budget_reports', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('template_id');
            $table->string('layout_name');
            $table->integer('budget_year')->index();
            $table->date('start_date');
            $table->date('end_date');
            $table->string('budget_range');
            $table->text('memo')->nullable();
            $table->integer('no_interval');
            $table->dateTime('last_updated_at')->nullable();
            $table->string('last_updated_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('budget_reports');
    }
};
