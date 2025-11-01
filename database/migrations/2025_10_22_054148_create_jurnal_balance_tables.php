<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('jurnal_balance_sheets', function (Blueprint $table) {
            $table->id();
            $table->date('report_date')->nullable();
            $table->string('currency_format', 50)->nullable();
            $table->decimal('total_assets', 20, 2)->nullable();
            $table->decimal('total_liabilities_equity', 20, 2)->nullable();
            $table->json('raw_data')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
        });

        Schema::create('jurnal_balance_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('balance_sheet_id')->constrained('jurnal_balance_sheets')->cascadeOnDelete();
            $table->string('group_key', 100);
            $table->string('group_name', 255)->nullable();
            $table->decimal('total_raw', 20, 2)->nullable();
            $table->string('total_currency', 50)->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
        });

        Schema::create('jurnal_balance_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('jurnal_balance_groups')->cascadeOnDelete();
            $table->bigInteger('jurnal_account_id')->nullable();
            $table->string('account_name', 255);
            $table->string('account_number', 100)->nullable();
            $table->bigInteger('category_id')->nullable();
            $table->string('category_name', 255)->nullable();
            $table->integer('indent')->default(0);
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
        });

        Schema::create('jurnal_balance_account_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('jurnal_balance_accounts')->cascadeOnDelete();
            $table->date('period_date')->nullable();
            $table->decimal('balance', 20, 2)->default(0);
            $table->string('balance_display', 50)->nullable();
            $table->decimal('percentage', 10, 2)->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jurnal_balance_account_data');
        Schema::dropIfExists('jurnal_balance_accounts');
        Schema::dropIfExists('jurnal_balance_groups');
        Schema::dropIfExists('jurnal_balance_sheets');
    }
};
