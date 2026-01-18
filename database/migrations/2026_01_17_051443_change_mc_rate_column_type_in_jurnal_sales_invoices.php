<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('jurnal_sales_invoices', function (Blueprint $table) {
            // Ubah menjadi DECIMAL dengan presisi tinggi (Total 24 digit, 8 desimal)
            $table->decimal('mc_rate', 24, 8)->nullable()->default(1)->change();

            // Cek juga kolom uang lainnya, kadang perlu diperbesar juga
            $table->decimal('unit_price', 24, 8)->change();
            $table->decimal('original_amount', 24, 8)->change();
            $table->decimal('total_invoice', 24, 8)->change(); // sesuaikan nama kolom total
        });
    }

    public function down()
    {
        Schema::table('jurnal_sales_invoices', function (Blueprint $table) {
            // Kembalikan ke settingan awal (sesuaikan dengan migration lama Anda)
            $table->decimal('mc_rate', 15, 2)->change();
        });
    }
};
