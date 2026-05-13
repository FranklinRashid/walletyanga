<?php

namespace Database\Seeders;

use App\Models\LedgerAccount;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->seedSystemAccounts();

        User::query()->firstOrCreate([
            'email' => 'test@example.com',
        ], [
            'name' => 'Test User',
            'password' => 'password',
            'role' => 'customer',
            'status' => 'active',
        ]);
    }

    private function seedSystemAccounts(): void
    {
        $accounts = [
            ['SYS:PAYCHANGU_CLEARING:MWK', 'Paychangu clearing MWK', 'MWK', 'asset', 'debit'],
            ['SYS:BANK_SETTLEMENT:MWK', 'Bank settlement MWK', 'MWK', 'asset', 'debit'],
            ['SYS:FX_POSITION:MWK', 'FX position MWK', 'MWK', 'liability', 'credit'],
            ['SYS:FX_POSITION:USD', 'FX position USD', 'USD', 'asset', 'debit'],
            ['SYS:FX_REVENUE:MWK', 'FX revenue MWK', 'MWK', 'revenue', 'credit'],
            ['SYS:CARD_ISSUER_PAYABLE:USD', 'Card issuer payable USD', 'USD', 'liability', 'credit'],
            ['SYS:FEE_REVENUE:USD', 'Fee revenue USD', 'USD', 'revenue', 'credit'],
        ];

        foreach ($accounts as [$code, $name, $currency, $type, $normalSide]) {
            LedgerAccount::query()->firstOrCreate([
                'code' => $code,
            ], [
                'name' => $name,
                'currency' => $currency,
                'type' => $type,
                'normal_side' => $normalSide,
                'status' => 'active',
            ]);
        }
    }
}
