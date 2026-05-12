<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminAction extends Model
{
    protected $fillable = [
        'admin_user_id',
        'action',
        'subject_type',
        'subject_id',
        'before',
        'after',
        'reason',
        'ip_address',
    ];

    protected $casts = [
        'before' => 'array',
        'after' => 'array',
    ];
}
