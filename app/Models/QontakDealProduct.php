<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QontakDealProduct extends Model
{
    protected $fillable = [
        'qontak_deal_id',
        'product_id',
        'product_name',
        'quantity',
        'price',
    ];
}
