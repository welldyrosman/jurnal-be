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
            $table->id();

            $table->unsignedBigInteger('jurnal_id')->unique();

            $table->string('name');
            $table->string('number')->index();

            $table->string('category')->nullable();
            $table->unsignedBigInteger('category_id')->nullable()->index();

            $table->boolean('is_parent')->default(false);
            $table->unsignedInteger('indent')->default(0);

            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('jurnal_accounts')
                ->nullOnDelete();

            $table->foreignId('account_grouping_id')
                ->nullable()
                ->constrained('account_groupings')
                ->nullOnDelete();

            $table->foreignId('budget_grouping_id')
                ->nullable()
                ->constrained('account_groupings')
                ->nullOnDelete();

            $table->decimal('balance_amount', 20, 2)->default(0);
            $table->timestamp('synced_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jurnal_accounts');
    }
};
