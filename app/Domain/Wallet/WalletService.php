<?php

namespace App\Domain\Wallet;

use App\Models\LedgerAccount;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;

class WalletService
{
    public function ensureUserWallet(User $user, string $currency, string $type = 'main'): Wallet
    {
        return DB::transaction(function () use ($user, $currency, $type): Wallet {
            $wallet = Wallet::query()->firstOrCreate([
                'user_id' => $user->id,
                'currency' => $currency,
                'type' => $type,
            ], [
                'status' => 'active',
            ]);

            LedgerAccount::query()->firstOrCreate([
                'code' => "USER:{$user->id}:{$currency}:{$type}",
            ], [
                'user_id' => $user->id,
                'wallet_id' => $wallet->id,
                'name' => "{$user->email} {$currency} {$type} wallet liability",
                'currency' => $currency,
                'type' => 'liability',
                'normal_side' => 'credit',
                'status' => 'active',
            ]);

            return $wallet->refresh()->load('ledgerAccount');
        });
    }
}
