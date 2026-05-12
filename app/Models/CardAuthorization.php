<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CardAuthorization extends Model
{
    protected $fillable = [
        'virtual_card_id',
        'provider_authorization_id',
        'currency',
        'amount_minor',
        'merchant_name',
        'merchant_category',
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
