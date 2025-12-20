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
        Schema::create('jurnal_receive_payment_attachments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('jurnal_receive_payment_id');
            $table->string('file_path')->comment('Path file attachment');
            $table->string('file_name')->nullable();
            $table->string('file_type')->nullable();
            $table->bigInteger('file_size')->nullable();
            $table->timestamps();

            // Foreign key (manual name)
            $table->foreign(
                'jurnal_receive_payment_id',
                'fk_jrp_attach_payment'
            )
                ->references('id')
                ->on('jurnal_receive_payments')
                ->onDelete('cascade');

            // Index (manual name)
            $table->index(
                'jurnal_receive_payment_id',
                'idx_jrp_attach_payment'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jurnal_receive_payment_attachments');
    }
};
