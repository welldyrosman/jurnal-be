<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JurnalSalesInvoiceTag extends Model
{
    protected $table = 'jurnal_sales_invoice_tags';

    protected $fillable = [
        'jurnal_sales_invoice_id',
        'tag_name',
    ];

    public function invoice()
    {
        return $this->belongsTo(JurnalSalesInvoice::class, 'jurnal_sales_invoice_id');
    }
}
