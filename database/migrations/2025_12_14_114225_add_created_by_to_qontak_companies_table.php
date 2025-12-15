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
        Schema::table('qontak_companies', function (Blueprint $table) {
            $table->unsignedBigInteger('created_by')->nullable()->default(0)->after('id');
            $table->string('crm_type_id')->nullable();
            $table->string('crm_type_name')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('qontak_companies', function (Blueprint $table) {
            $table->dropColumn('created_by');
        });
    }
};
