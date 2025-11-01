<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JurnalPerson extends Model
{
    use HasFactory;
    protected $table = 'jurnal_persons'; // <-- TAMBAHKAN BARIS INI

     protected $guarded=[];
    public function invoices(): HasMany
    {
        return $this->hasMany(JurnalInvoice::class, 'person_id');
    }
}
