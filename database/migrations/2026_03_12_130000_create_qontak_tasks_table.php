<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('qontak_tasks', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('crm_task_id')->unique();
            $table->string('name')->nullable();

            $table->timestamp('created_at_qontak')->nullable()->index();
            $table->timestamp('updated_at_qontak')->nullable()->index();

            $table->foreignId('qontak_contact_id')
                ->nullable()
                ->constrained('qontak_contacts')
                ->nullOnDelete();

            $table->foreignId('qontak_deal_id')
                ->nullable()
                ->constrained('qontak_deals')
                ->nullOnDelete();

            $table->foreignId('qontak_company_id')
                ->nullable()
                ->constrained('qontak_companies')
                ->nullOnDelete();

            $table->string('crm_person_id')->nullable()->index();
            $table->string('crm_person_full_name')->nullable();

            $table->string('crm_deal_id')->nullable()->index();
            $table->string('crm_deal_name')->nullable();

            $table->string('crm_company_id')->nullable()->index();
            $table->string('crm_company_name')->nullable();

            $table->string('crm_task_status_id')->nullable()->index();
            $table->string('crm_task_priority_id')->nullable()->index();

            $table->string('crm_task_category_id')->nullable()->index();
            $table->string('crm_task_category_name')->nullable();

            $table->string('user_id')->nullable()->index();
            $table->string('user_full_name')->nullable();

            $table->json('crm_team_ids')->nullable();
            $table->string('crm_team_name')->nullable();

            $table->string('customer_id')->nullable()->index();
            $table->string('customer_name')->nullable();

            $table->dateTime('due_date')->nullable()->index();
            $table->dateTime('reminder_date')->nullable()->index();

            $table->text('detail')->nullable();
            $table->text('next_step')->nullable();
            $table->json('attachment')->nullable();

            $table->string('unique_task_id')->nullable()->index();
            $table->json('raw')->nullable();

            $table->timestamps();

            $table->index(['crm_task_status_id', 'crm_task_priority_id'], 'idx_qontak_tasks_status_priority');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qontak_tasks');
    }
};
