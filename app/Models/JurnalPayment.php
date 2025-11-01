<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JurnalPayment extends Model
{
    use HasFactory;

     protected $guarded=[];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(JurnalInvoice::class, 'invoice_id');
    }
}
