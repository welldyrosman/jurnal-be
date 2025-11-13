<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel ini akan menyimpan salinan Chart of Accounts (COA) dari Jurnal.
     */
    public function up(): void
    {
        Schema::create('jurnal_accounts', function (Blueprint $table) {
            $table->id(); // Primary key lokal
            $table->unsignedBigInteger('jurnal_id')->unique(); // ID dari Jurnal API
            
            $table->string('name');
            $table->string('number')->index();
            $table->string('category')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            
            $table->boolean('is_parent')->default(false);
            $table->integer('indent')->default(0);
            
            // Untuk relasi parent-child
            $table->foreignId('parent_id')->nullable()->constrained('jurnal_accounts')->nullOnDelete();
            
            $table->decimal('balance_amount', 20, 2)->default(0);
            $table->timestamp('synced_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jurnal_accounts');
    }
};