<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountBudget extends Model
{
    protected $guarded=[];
     public function account(): BelongsTo
    {
        return $this->belongsTo(JurnalAccount::class, 'jurnal_account_id');
    }
}
