<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JurnalAccount extends Model
{
    use HasFactory;
    protected $fillable = [
        'jurnal_id',
        'name',
        'number',
        'category',
        'category_id',
        'grouping_akun', // Kolom custom Anda
        'grouping_budget', // Kolom custom Anda
        'is_parent',
        'indent',
        'parent_id',
        'balance_amount',
        'synced_at',
    ];
    public function parent(): BelongsTo
    {
        return $this->belongsTo(JurnalAccount::class, 'parent_id');
    }
    public function children(): HasMany
    {
        return $this->hasMany(JurnalAccount::class, 'parent_id');
    }
    public function budgets(): HasMany
    {
        // Pastikan foreign key-nya benar (sesuai tabel AccountBudget)
        return $this->hasMany(AccountBudget::class, 'jurnal_account_id');
    }
    public function accountGrouping(): BelongsTo
    {
        return $this->belongsTo(AccountGrouping::class, 'account_grouping_id');
    }
    public function budgetGrouping(): BelongsTo
    {
        return $this->belongsTo(AccountGrouping::class, 'budget_grouping_id');
    }
    public function grouping()
    {
        return $this->belongsTo(AccountGrouping::class, 'budget_grouping_id');
    }
    public function accountBudgets()
    {
        return $this->hasMany(AccountBudget::class, 'jurnal_account_id');
    }
}
