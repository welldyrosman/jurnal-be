<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('qontak_entity_timelines', function (Blueprint $table) {
            $table->id();

            $table->string('entity_type')->index(); // deal, contact, company
            $table->string('entity_crm_id')->index();

            $table->unsignedBigInteger('event_id')->nullable()->index();
            $table->timestamp('event_at')->nullable()->index();

            $table->string('actor')->nullable();
            $table->string('action')->nullable()->index();
            $table->string('target')->nullable()->index();
            $table->text('summary')->nullable();
            $table->text('content')->nullable();

            $table->unsignedBigInteger('task_id')->nullable()->index();
            $table->unsignedBigInteger('ticket_id')->nullable()->index();
            $table->unsignedBigInteger('auditable_id')->nullable()->index();
            $table->unsignedBigInteger('email_id')->nullable()->index();
            $table->unsignedBigInteger('crm_deal_id')->nullable()->index();

            $table->foreignId('qontak_deal_id')
                ->nullable()
                ->constrained('qontak_deals')
                ->nullOnDelete();

            $table->foreignId('qontak_contact_id')
                ->nullable()
                ->constrained('qontak_contacts')
                ->nullOnDelete();

            $table->foreignId('qontak_company_id')
                ->nullable()
                ->constrained('qontak_companies')
                ->nullOnDelete();

            $table->string('fingerprint')->unique();
            $table->json('raw')->nullable();

            $table->timestamps();

            $table->index(['entity_type', 'event_at'], 'idx_qontak_timelines_entity_event_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qontak_entity_timelines');
    }
};
