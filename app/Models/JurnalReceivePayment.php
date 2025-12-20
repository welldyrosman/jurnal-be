<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JurnalReceivePayment extends Model
{
    protected $fillable = [
        'jurnal_id',
        'transaction_no',
        'token',
        'memo',
        'source',
        'custom_id',
        'status',
        'transaction_status_id',
        'transaction_status_name',
        'deleted_at',
        'deletable',
        'editable',
        'audited_by',
        'transaction_date',
        'due_date',
        'person_id',
        'person_name',
        'person_email',
        'person_address',
        'person_phone',
        'person_fax',
        'transaction_type_id',
        'transaction_type_name',
        'payment_method_id',
        'payment_method_name',
        'deposit_to_id',
        'deposit_to_name',
        'deposit_to_number',
        'deposit_to_category',
        'is_draft',
        'withholding_account_name',
        'withholding_account_number',
        'withholding_account_id',
        'withholding_value',
        'withholding_type',
        'withholding_amount',
        'withholding_category_id',
        'original_amount',
        'total',
        'currency_code',
        'currency_list_id',
        'currency_from_id',
        'currency_to_id',
        'multi_currency_id',
        'is_reconciled',
        'is_create_before_conversion',
        'is_import',
        'import_id',
        'skip_at',
        'disable_link',
        'comments_size',
        'sync_status',
        'sync_error',
        'last_sync_at',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'due_date' => 'date',
        'deletable' => 'boolean',
        'editable' => 'boolean',
        'is_draft' => 'boolean',
        'is_reconciled' => 'boolean',
        'is_create_before_conversion' => 'boolean',
        'is_import' => 'boolean',
        'skip_at' => 'boolean',
        'disable_link' => 'boolean',
        'original_amount' => 'decimal: 2',
        'total' => 'decimal:2',
        'withholding_value' => 'decimal:2',
        'withholding_amount' => 'decimal:2',
        'last_sync_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the records for this receive payment
     */
    public function records()
    {
        return $this->hasMany(JurnalReceivePaymentRecord::class);
    }

    /**
     * Get the attachments for this receive payment
     */
    public function attachments()
    {
        return $this->hasMany(JurnalReceivePaymentAttachment::class);
    }

    /**
     * Scope untuk data yang belum disinkronisasi
     */
    public function scopePending($query)
    {
        return $query->where('sync_status', 'pending');
    }

    /**
     * Scope untuk data yang sudah disinkronisasi
     */
    public function scopeSynced($query)
    {
        return $query->where('sync_status', 'synced');
    }

    /**
     * Scope untuk data yang gagal sinkronisasi
     */
    public function scopeFailed($query)
    {
        return $query->where('sync_status', 'failed');
    }
}
