<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Menambahkan kolom Foreign Key ke tabel jurnal_accounts
     * yang berelasi ke tabel grouping baru.
     */
    public function up(): void
    {
        Schema::table('jurnal_accounts', function (Blueprint $table) {
            if (Schema::hasColumn('jurnal_accounts', 'grouping_akun')) {
                $table->dropColumn(['grouping_akun', 'grouping_budget']);
            }

            $table->foreignId('account_grouping_id')
                  ->nullable()
                  ->after('category_id')
                  ->constrained('account_groupings') // Berelasi ke tabel 'groupings'
                  ->nullOnDelete(); // Jika grouping dihapus, set kolom ini ke NULL

            // Relasi ke tabel groupings (untuk 'Grouping Budget')
            $table->foreignId('budget_grouping_id')
                  ->nullable()
                  ->after('account_grouping_id')
                  ->constrained('account_groupings') // Berelasi ke tabel 'groupings'
                  ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jurnal_accounts', function (Blueprint $table) {
            // Hapus foreign key constraint SEBELUM menghapus kolom
            $table->dropForeign(['account_grouping_id']);
            $table->dropForeign(['budget_grouping_id']);
            
            // Hapus kolom
            $table->dropColumn(['account_grouping_id', 'budget_grouping_id']);
        });
    }
};