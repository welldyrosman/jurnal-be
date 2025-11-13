<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountGrouping extends Model
{
     protected $fillable = [
        'name',
        'type',
    ];

    /**
     * Scope untuk mengambil hanya grouping tipe 'akun'.
     */
    public function scopeTypeAkun($query)
    {
        return $query->where('type', 'akun');
    }

    /**
     * Scope untuk mengambil hanya grouping tipe 'budget'.
     */
    public function scopeTypeBudget($query)
    {
        return $query->where('type', 'budget');
    }
}
