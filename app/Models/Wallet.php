<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Wallet extends Model
{
    protected $fillable = [
        'user_id',
        'currency',
        'type',
        'status',
        'cached_balance_minor',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function ledgerAccount(): HasOne
    {
        return $this->hasOne(LedgerAccount::class);
    }
}
