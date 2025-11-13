<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel ini akan menyimpan data budget bulanan untuk setiap akun.
     */
    public function up(): void
    {
        Schema::create('account_budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jurnal_account_id')->constrained('jurnal_accounts')->onDelete('cascade');
            $table->integer('year')->comment('Tahun budget, e.g., 2026');

            // 12 kolom untuk budget bulanan
            $table->decimal('budget_jan', 20, 2)->default(0);
            $table->decimal('budget_feb', 20, 2)->default(0);
            $table->decimal('budget_mar', 20, 2)->default(0);
            $table->decimal('budget_apr', 20, 2)->default(0);
            $table->decimal('budget_mei', 20, 2)->default(0);
            $table->decimal('budget_jun', 20, 2)->default(0);
            $table->decimal('budget_jul', 20, 2)->default(0);
            $table->decimal('budget_ags', 20, 2)->default(0);
            $table->decimal('budget_sep', 20, 2)->default(0);
            $table->decimal('budget_okt', 20, 2)->default(0);
            $table->decimal('budget_nov', 20, 2)->default(0);
            $table->decimal('budget_des', 20, 2)->default(0);
            
            $table->timestamps();

            // Pastikan satu akun hanya punya satu baris budget per tahun
            $table->unique(['jurnal_account_id', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_budgets');
    }
};