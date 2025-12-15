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
        Schema::create('qontak_companies', function (Blueprint $table) {
            $table->id();

            $table->string('crm_company_id')->unique()->nullable();
            $table->string('name')->nullable();
            $table->string('slug')->nullable();
            $table->string('email')->nullable();
            $table->string('telephone')->nullable();
            $table->string('website')->nullable();
            $table->text('address')->nullable();
            $table->string('crm_source_id')->nullable();
            $table->string('crm_source_name')->nullable();
            $table->foreignId('qontak_source_id')
                ->nullable()
                ->constrained('qontak_sources')
                ->nullOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qontak_companies');
    }
};
