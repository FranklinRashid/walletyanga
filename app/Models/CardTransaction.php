<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CardTransaction extends Model
{
    protected $fillable = [
        'virtual_card_id',
        'card_authorization_id',
        'provider_transaction_id',
        'type',
        'currency',
        'amount_minor',
        'status',
        'ledger_transaction_id',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function card(): BelongsTo
    {
        return $this->belongsTo(VirtualCard::class, 'virtual_card_id');
    }
}
