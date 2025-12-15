<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QontakProduct extends Model
{
    public function dealAssociations()
    {
        return $this->hasMany(QontakDealProductAssociation::class);
    }
}
