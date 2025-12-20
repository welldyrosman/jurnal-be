<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JurnalReceivePaymentAttachment extends Model
{
    protected $fillable = [
        'receive_payment_id',
        'file_path',
        'file_name',
        'file_type',
        'file_size',
    ];

    /**
     * Get the receive payment this attachment belongs to
     */
    public function receivePayment(): BelongsTo
    {
        return $this->belongsTo(JurnalReceivePayment::class);
    }
}
