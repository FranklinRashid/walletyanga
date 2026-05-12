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
        'date_of_birth',
        'identity_type',
        'identity_number',
        'address',
        'city_district',
        'occupation',
        'source_of_funds',
        'id_document_path',
        'submitted_at',
        'reviewed_at',
        'reviewed_by',
        'review_notes',
        'screening_result',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'screening_result' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
