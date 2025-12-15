<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QontakDealAdditionalField extends Model
{
    protected $fillable = [
        'qontak_deal_id',
        'field_id',
        'name',
        'value',
        'value_name',
    ];
}
