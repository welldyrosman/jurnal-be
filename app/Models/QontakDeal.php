<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QontakDeal extends Model
{
    protected $fillable = [
        'deal_id',
        'name',
        'slug',
        'created_at_qontak',
        'updated_at_qontak',
        'currency',
        'amount',
        'crm_pipeline_id',
        'crm_pipeline_name',
        'crm_source_id',
        'crm_source_name',
        'crm_priority_id',
        'crm_priority_name',
        'crm_stage_id',
        'crm_stage_name',
        'creator_id',
        'creator_name',
        'unique_deal_id',
        'idempotency_key',
        'raw',
    ];

    protected $casts = [
        'created_at_qontak' => 'datetime',
        'updated_at_qontak' => 'datetime',
        'raw' => 'json',
    ];

    public function additionalFields()
    {
        return $this->hasMany(QontakDealAdditionalField::class);
    }
    public function products()
    {
        return $this->hasMany(QontakDealProduct::class);
    }
    public function productAssociations()
    {
        return $this->hasMany(QontakDealProductAssociation::class);
    }
}
