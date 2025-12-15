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
        Schema::create('qontak_pipelines', function (Blueprint $table) {
            $table->id();
            $table->string('crm_pipeline_id')->unique();
            $table->string('name');
            $table->string('alias_name')->nullable();
            $table->string('slug')->nullable();
            $table->boolean('active')->default(true);
            $table->unsignedInteger('organization_id', false);
            $table->timestamps();
            $table->index('organization_id');
            $table->index('active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qontak_pipelines');
    }
};
