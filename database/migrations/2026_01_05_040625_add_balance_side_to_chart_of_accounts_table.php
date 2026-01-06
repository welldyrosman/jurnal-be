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
        Schema::table('account_groupings', function (Blueprint $table) {
            $table->enum('balance_side', ['debit', 'credit'])->nullable()->after('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('account_groupings', function (Blueprint $table) {
            $table->dropColumn('balance_side');
        });
    }
};
