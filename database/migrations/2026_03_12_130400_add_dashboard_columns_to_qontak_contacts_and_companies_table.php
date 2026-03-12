<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('qontak_contacts', function (Blueprint $table) {
            if (!Schema::hasColumn('qontak_contacts', 'created_at_qontak')) {
                $table->timestamp('created_at_qontak')->nullable()->after('telephone')->index();
            }

            if (!Schema::hasColumn('qontak_contacts', 'updated_at_qontak')) {
                $table->timestamp('updated_at_qontak')->nullable()->after('created_at_qontak')->index();
            }

            if (!Schema::hasColumn('qontak_contacts', 'crm_source_id')) {
                $table->string('crm_source_id')->nullable()->after('updated_at_qontak')->index();
            }

            if (!Schema::hasColumn('qontak_contacts', 'crm_source_name')) {
                $table->string('crm_source_name')->nullable()->after('crm_source_id');
            }

            if (!Schema::hasColumn('qontak_contacts', 'qontak_source_id')) {
                $table->foreignId('qontak_source_id')
                    ->nullable()
                    ->after('crm_source_name')
                    ->constrained('qontak_sources')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('qontak_contacts', 'crm_company_id')) {
                $table->string('crm_company_id')->nullable()->after('qontak_source_id')->index();
            }

            if (!Schema::hasColumn('qontak_contacts', 'crm_company_name')) {
                $table->string('crm_company_name')->nullable()->after('crm_company_id');
            }

            if (!Schema::hasColumn('qontak_contacts', 'creator_id')) {
                $table->string('creator_id')->nullable()->after('crm_company_name')->index();
            }

            if (!Schema::hasColumn('qontak_contacts', 'creator_name')) {
                $table->string('creator_name')->nullable()->after('creator_id');
            }
        });

        Schema::table('qontak_companies', function (Blueprint $table) {
            if (!Schema::hasColumn('qontak_companies', 'created_at_qontak')) {
                $table->timestamp('created_at_qontak')->nullable()->after('crm_type_name')->index();
            }

            if (!Schema::hasColumn('qontak_companies', 'updated_at_qontak')) {
                $table->timestamp('updated_at_qontak')->nullable()->after('created_at_qontak')->index();
            }

            if (!Schema::hasColumn('qontak_companies', 'creator_id')) {
                $table->string('creator_id')->nullable()->after('updated_at_qontak')->index();
            }

            if (!Schema::hasColumn('qontak_companies', 'creator_name')) {
                $table->string('creator_name')->nullable()->after('creator_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('qontak_contacts', function (Blueprint $table) {
            if (Schema::hasColumn('qontak_contacts', 'qontak_source_id')) {
                $table->dropConstrainedForeignId('qontak_source_id');
            }

            foreach ([
                'created_at_qontak',
                'updated_at_qontak',
                'crm_source_id',
                'crm_source_name',
                'crm_company_id',
                'crm_company_name',
                'creator_id',
                'creator_name',
            ] as $column) {
                if (Schema::hasColumn('qontak_contacts', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('qontak_companies', function (Blueprint $table) {
            foreach ([
                'created_at_qontak',
                'updated_at_qontak',
                'creator_id',
                'creator_name',
            ] as $column) {
                if (Schema::hasColumn('qontak_companies', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
