<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jurnal_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('jurnal_id')->nullable();

            $table->foreignId('invoice_id')->constrained('jurnal_invoices')->cascadeOnDelete();
            
            $table->string('transaction_no')->nullable();
            $table->date('transaction_date')->nullable();
            $table->decimal('amount', 15, 2)->default(0);
            $table->string('payment_method_name')->nullable();

            $table->timestamp('created_at_jurnal')->nullable();
            $table->timestamp('updated_at_jurnal')->nullable();
            $table->timestamp('synced_at')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jurnal_payments');
    }
};
