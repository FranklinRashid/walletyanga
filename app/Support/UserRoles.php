<?php

namespace App\Support;

class UserRoles
{
    public const CUSTOMER = 'customer';
    public const SUPER_ADMIN = 'super_admin';
    public const COMPLIANCE_OFFICER = 'compliance_officer';
    public const OPERATIONS_ADMIN = 'operations_admin';
    public const FINANCE_ADMIN = 'finance_admin';
    public const SUPPORT_AGENT = 'support_agent';
    public const AUDITOR = 'auditor';

    public static function staffRoles(): array
    {
        return [
            self::SUPER_ADMIN => 'Super Admin',
            self::COMPLIANCE_OFFICER => 'Compliance Officer',
            self::OPERATIONS_ADMIN => 'Operations Admin',
            self::FINANCE_ADMIN => 'Finance Admin',
            self::SUPPORT_AGENT => 'Support Agent',
            self::AUDITOR => 'Read-only Auditor',
        ];
    }

    public static function all(): array
    {
        return [self::CUSTOMER => 'Customer'] + self::staffRoles();
    }

    public static function adminRoles(): array
    {
        return array_keys(self::staffRoles());
    }
}
