<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LedgerTransaction extends Model
{
    protected $fillable = [
        'reference',
        'type',
        'status',
        'idempotency_key',
        'metadata',
        'posted_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'posted_at' => 'datetime',
    ];

    public function entries(): HasMany
    {
        return $this->hasMany(LedgerEntry::class);
    }
}
