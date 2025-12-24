<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JurnalSalesInvoiceCustomField extends Model
{
    protected $table = 'jurnal_sales_invoice_custom_fields';

    protected $fillable = [
        'jurnal_sales_invoice_id',
        'field_name',
        'field_value',
    ];

    public function invoice()
    {
        return $this->belongsTo(JurnalSalesInvoice::class, 'jurnal_sales_invoice_id');
    }
}
