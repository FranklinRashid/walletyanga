<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FxConversion extends Model
{
    protected $fillable = [
        'user_id',
        'fx_quote_id',
        'ledger_transaction_id',
        'status',
    ];

    public function quote(): BelongsTo
    {
        return $this->belongsTo(FxQuote::class, 'fx_quote_id');
    }

    public function ledgerTransaction(): BelongsTo
    {
        return $this->belongsTo(LedgerTransaction::class);
    }
}
