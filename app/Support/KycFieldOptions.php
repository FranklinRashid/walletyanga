<?php

namespace App\Support;

class KycFieldOptions
{
    public static function identityTypes(): array
    {
        return [
            'national_id' => 'National ID',
            'passport' => 'Passport',
            'drivers_license' => 'Driver license',
        ];
    }

    public static function sourcesOfFunds(): array
    {
        return [
            'salary' => 'Salary',
            'business_income' => 'Business income',
            'freelance' => 'Freelance income',
            'remittances' => 'Remittances',
            'savings' => 'Savings',
            'other' => 'Other',
        ];
    }
}
