<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JurnalSalesInvoice extends Model
{
    protected $table = 'jurnal_sales_invoices';

    protected $fillable = [
        'jurnal_id',
        'transaction_no',
        'transaction_date',
        'due_date',
        'expiry_date',
        'transaction_type_id',
        'transaction_type_name',
        'transaction_status_id',
        'transaction_status_name',
        'customer_id',
        'customer_name',
        'customer_type',
        'person_company_name',
        'person_tax_no',
        'person_mobile',
        'person_phone',
        'email',
        'billing_address',
        'shipping_address',
        'reference_no',
        'memo',
        'message',
        'warehouse_id',
        'warehouse_name',
        'product_id',
        'product_name',
        'product_code',
        'quantity_unit',
        'product_unit_name',
        'unit_price',
        'discount_line_rate',
        'tax_rate',
        'line_tax_amount',
        'taxable_amount_per_line',
        'total_per_line',
        'description',
        'original_amount',
        'gross_taxable_amount',
        'tax_amount',
        'discount',
        'discount_rate_percentage',
        'shipping_fee',
        'witholding_amount',
        'payment',
        'total_paid',
        'balance_due',
        'deposit_all_payment',
        'payment_method_name',
        'total_return_amount',
        'total_invoice',
        'withholding_type',
        'sales_order_no',
        'sales_invoice_no',
        'currency_code',
        'currency_list_id',
        'mc_rate',
        'account_id',
        'account_number',
        'account_name',
        'hidden_transaction',
        'hidden_transaction_type_id',
        'sync_status',
        'sync_error',
        'last_sync_at',
    ];

    protected $casts = [
        'transaction_date' => 'datetime',
        'due_date' => 'date',
        'expiry_date' => 'date',
        'last_sync_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'quantity_unit' => 'integer',
        'tax_rate' => 'integer',
        'unit_price' => 'decimal: 2',
        'discount_line_rate' => 'decimal:2',
        'line_tax_amount' => 'decimal:2',
        'taxable_amount_per_line' => 'decimal:2',
        'total_per_line' => 'decimal:2',
        'original_amount' => 'decimal: 2',
        'gross_taxable_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount' => 'decimal:2',
        'discount_rate_percentage' => 'decimal:2',
        'shipping_fee' => 'decimal:2',
        'witholding_amount' => 'decimal:2',
        'payment' => 'decimal:2',
        'total_paid' => 'decimal:2',
        'balance_due' => 'decimal:2',
        'deposit_all_payment' => 'decimal:2',
        'total_return_amount' => 'decimal:2',
        'total_invoice' => 'decimal: 2',
        'mc_rate' => 'decimal: 6',
        'hidden_transaction' => 'boolean',
    ];

    public function customFields()
    {
        return $this->hasMany(JurnalSalesInvoiceCustomField::class);
    }

    public function tags()
    {
        return $this->hasMany(JurnalSalesInvoiceTag::class);
    }
}
