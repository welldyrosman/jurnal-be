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
        Schema::table('qontak_deals', function (Blueprint $table) {

            $table->foreignId('qontak_company_id')
                ->nullable()
                ->constrained('qontak_companies')
                ->nullOnDelete();

            $table->foreignId('qontak_source_id')
                ->nullable()
                ->constrained('qontak_sources')
                ->nullOnDelete();

            $table->decimal('amount', 15, 2)->default(0)->index();

            $table->dateTime('start_date')->nullable();
            $table->dateTime('closed_date')->nullable();
            $table->dateTime('expired_date')->nullable();

            // snapshot CRM
            $table->string('crm_priority_id')->nullable();
            $table->string('crm_priority_name')->nullable();
            $table->string('crm_lost_reason_id')->nullable();
            $table->string('crm_lost_reason_name')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('qontak_deals', function (Blueprint $table) {
            if (Schema::hasColumn('qontak_deals', 'qontak_company_id')) {
                $table->dropConstrainedForeignId('qontak_company_id');
            }

            if (Schema::hasColumn('qontak_deals', 'qontak_source_id')) {
                $table->dropConstrainedForeignId('qontak_source_id');
            }

            // Kolom biasa
            $columns = [
                'amount',
                'start_date',
                'closed_date',
                'expired_date',
                'crm_priority_id',
                'crm_priority_name',
                'crm_lost_reason_id',
                'crm_lost_reason_name',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('qontak_deals', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
