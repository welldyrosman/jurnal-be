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
        Schema::create('jurnal_payment_collects', function (Blueprint $table) {
            $table->id();
            $table->enum("payment_type", ["credit_memo", "payment_receive"]);
            $table->string("transaction_no");
            $table->date("transaction_date");
            $table->decimal("amount", 24, 8);
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jurnal_payment_collects');
    }
};
