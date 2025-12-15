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
        Schema::create('account_groupings', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('Nama grouping, e.g., BEBAN OPERASIONAL');
            $table->string('type')->comment("Tipe grouping, e.g., 'akun' atau 'budget'");
            $table->timestamps();
            $table->unique(['name', 'type']);
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_groupings');
    }
};
