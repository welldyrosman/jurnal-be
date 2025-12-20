<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JurnalReceivePaymentRecord extends Model
{
    protected $fillable = [
        'receive_payment_id',
        'jurnal_record_id',
        'jurnal_transaction_id',
        'amount',
        'description',
        'transaction_type_id',
        'transaction_type',
        'transaction_no',
        'transaction_due_date',
        'transaction_total',
        'transaction_balance_due',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'transaction_total' => 'decimal:2',
        'transaction_balance_due' => 'decimal:2',
        'transaction_due_date' => 'date',
    ];

    /**
     * Get the receive payment this record belongs to
     */
    public function receivePayment(): BelongsTo
    {
        return $this->belongsTo(JurnalReceivePayment::class);
    }
}
