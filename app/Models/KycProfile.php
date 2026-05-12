<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KycProfile extends Model
{
    protected $fillable = [
        'user_id',
        'status',
        'tier',
        'risk_rating',
        'submitted_at',
        'reviewed_at',
        'reviewed_by',
        'review_notes',
        'screening_result',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'screening_result' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
