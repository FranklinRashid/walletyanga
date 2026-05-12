<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VirtualCard extends Model
{
    protected $fillable = [
        'user_id',
        'provider',
        'provider_card_id',
        'masked_pan',
        'brand',
        'currency',
        'status',
        'nickname',
        'daily_limit_minor',
        'monthly_limit_minor',
        'controls',
    ];

    protected $casts = [
        'controls' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function authorizations(): HasMany
    {
        return $this->hasMany(CardAuthorization::class);
    }
}
