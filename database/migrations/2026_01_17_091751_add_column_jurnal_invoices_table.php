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
        Schema::table('jurnal_invoices', function (Blueprint $table) {
            $table->boolean('has_credit_memo')->nullable();
            $table->decimal('credit_memo_balance', 24, 8)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jurnal_invoices', function (Blueprint $table) {
            $table->dropColumn('has_credit_memo');
            $table->dropColumn('credit_memo_balance');
        });
    }
};
