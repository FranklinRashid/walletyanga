<?php

namespace App\Enums;

enum KycProfileStatus: string
{
    case NOT_STARTED = 'not_started';
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
}
