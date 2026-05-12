<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentGatewayEvent extends Model
{
    protected $fillable = [
        'provider',
        'event_id',
        'event_type',
        'tx_ref',
        'signature',
        'payload',
        'processing_status',
        'processing_error',
    ];

    protected $casts = [
        'payload' => 'array',
    ];
}
