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
        Schema::create('budget_report_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('line_child_id')->constrained('budget_report_line_childrens')->cascadeOnDelete();
            $table->bigInteger('external_account_id');
            $table->string('account_number');
            $table->string('account_name');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('budget_report_accounts');
    }
};
