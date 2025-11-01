<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JurnalInvoice extends Model
{
    use HasFactory;

    protected $guarded=[];

    public function person(): BelongsTo
    {
        return $this->belongsTo(JurnalPerson::class, 'person_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(JurnalInvoiceLine::class, 'invoice_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(JurnalPayment::class, 'invoice_id');
    }
}
