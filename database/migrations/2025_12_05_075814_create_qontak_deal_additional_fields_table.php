<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('qontak_deal_additional_fields', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('qontak_deal_id'); // relasi ke qontak_deals

            $table->unsignedBigInteger('field_id')->nullable(); // ID dari Qontak
            $table->string('name')->nullable();
            $table->text('value')->nullable();
            $table->text('value_name')->nullable();

            $table->timestamps();

            $table->foreign('qontak_deal_id')
                ->references('id')
                ->on('qontak_deals')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qontak_deal_additional_fields');
    }
};
