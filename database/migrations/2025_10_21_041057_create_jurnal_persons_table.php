<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jurnal_persons', function (Blueprint $table) {
            $table->id(); // Primary key lokal
            $table->unsignedBigInteger('jurnal_id')->unique(); // ID dari Jurnal API, unik

            $table->string('display_name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->text('billing_address')->nullable();

            $table->timestamp('created_at_jurnal')->nullable();
            $table->timestamp('updated_at_jurnal')->nullable();
            $table->timestamp('synced_at')->useCurrent(); // Kapan terakhir disinkronkan
            $table->timestamps(); // created_at dan updated_at lokal
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jurnal_persons');
    }
};
