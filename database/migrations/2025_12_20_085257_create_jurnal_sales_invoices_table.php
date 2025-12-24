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
        Schema::create('jurnal_sales_invoices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('jurnal_id')->unique()->comment('ID dari API Jurnal');
            $table->string('transaction_no')->index()->comment('Nomor transaksi');

            // Transaction Info
            $table->dateTime('transaction_date')->index()->comment('Tanggal transaksi');
            $table->date('due_date')->nullable()->comment('Tanggal jatuh tempo');
            $table->date('expiry_date')->nullable();

            // Transaction Type & Status
            $table->unsignedBigInteger('transaction_type_id')->comment('ID tipe transaksi');
            $table->string('transaction_type_name')->comment('Nama tipe transaksi');
            $table->unsignedBigInteger('transaction_status_id')->comment('ID status transaksi');
            $table->string('transaction_status_name')->comment('Status (Lunas, Sebagian, Belum Dibayar)');

            // Customer Info
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('customer_name')->comment('Nama customer');
            $table->string('customer_type')->nullable()->comment('customer atau supplier');
            $table->string('person_company_name')->nullable();
            $table->string('person_tax_no')->nullable()->comment('NPWP');
            $table->string('person_mobile')->nullable();
            $table->string('person_phone')->nullable();

            // Address & Contact
            $table->string('email')->nullable();
            $table->text('billing_address')->nullable();
            $table->text('shipping_address')->nullable();

            // Reference & Notes
            $table->string('reference_no')->nullable();
            $table->text('memo')->nullable();
            $table->text('message')->nullable();

            // Warehouse & Product
            $table->unsignedBigInteger('warehouse_id')->nullable();
            $table->string('warehouse_name')->nullable();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->string('product_name')->nullable();
            $table->string('product_code')->nullable();

            // Item Details
            $table->integer('quantity_unit')->default(0);
            $table->string('product_unit_name')->nullable();
            $table->decimal('unit_price', 18, 2)->default(0);
            $table->decimal('discount_line_rate', 5, 2)->default(0)->comment('Diskon per item (%)');
            $table->integer('tax_rate')->default(0)->comment('Tarif pajak (%)');
            $table->decimal('line_tax_amount', 18, 2)->default(0)->comment('Jumlah pajak per line');
            $table->decimal('taxable_amount_per_line', 18, 2)->default(0);
            $table->decimal('total_per_line', 18, 2)->default(0)->comment('Total per line item');
            $table->text('description')->nullable();

            // Amount Fields
            $table->decimal('original_amount', 18, 2)->comment('Jumlah asli');
            $table->decimal('gross_taxable_amount', 18, 2)->default(0)->comment('Jumlah terkena pajak');
            $table->decimal('tax_amount', 18, 2)->default(0)->comment('Total pajak');
            $table->decimal('discount', 18, 2)->default(0);
            $table->decimal('discount_rate_percentage', 5, 2)->default(0);
            $table->decimal('shipping_fee', 18, 2)->default(0);
            $table->decimal('witholding_amount', 18, 2)->default(0)->comment('Jumlah withholding/potongan');

            // Payment Info
            $table->decimal('payment', 18, 2)->default(0)->comment('Jumlah pembayaran');
            $table->decimal('total_paid', 18, 2)->default(0)->comment('Total yang sudah dibayar');
            $table->decimal('balance_due', 18, 2)->default(0)->comment('Sisa yang harus dibayar');
            $table->decimal('deposit_all_payment', 18, 2)->default(0);
            $table->string('payment_method_name')->nullable();

            // Additional Info
            $table->decimal('total_return_amount', 18, 2)->default(0)->comment('Total retur');
            $table->decimal('total_invoice', 18, 2)->default(0);
            $table->string('withholding_type')->nullable()->comment('value atau percentage');
            $table->string('sales_order_no')->nullable();
            $table->string('sales_invoice_no')->nullable();

            // Currency
            $table->string('currency_code')->default('IDR')->index();
            $table->unsignedBigInteger('currency_list_id')->default(0);
            $table->decimal('mc_rate', 10, 6)->default(1)->comment('Exchange rate');

            // Account
            $table->unsignedBigInteger('account_id')->nullable();
            $table->string('account_number')->nullable();
            $table->string('account_name')->nullable();

            // Status Flags
            $table->boolean('hidden_transaction')->default(false);
            $table->integer('hidden_transaction_type_id')->default(0);

            // Sync Fields
            $table->enum('sync_status', ['pending', 'synced', 'failed'])->default('pending')->index();
            $table->text('sync_error')->nullable();
            $table->dateTime('last_sync_at')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['transaction_no', 'transaction_date']);
            $table->index('customer_name');
            $table->index('transaction_status_name');
            $table->index(['sync_status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jurnal_sales_invoices');
    }
};
