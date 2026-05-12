<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FxQuote extends Model
{
    protected $fillable = [
        'user_id',
        'from_currency',
        'to_currency',
        'from_amount_minor',
        'rate',
        'spread_bps',
        'effective_rate',
        'to_amount_minor',
        'expires_at',
        'status',
        'metadata',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
