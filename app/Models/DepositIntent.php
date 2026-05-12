<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DepositIntent extends Model
{
    protected $fillable = [
        'user_id',
        'provider',
        'tx_ref',
        'currency',
        'amount_minor',
        'status',
        'checkout_url',
        'provider_reference',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
