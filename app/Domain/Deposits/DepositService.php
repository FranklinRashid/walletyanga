<?php

namespace App\Domain\Deposits;

use App\Domain\Ledger\LedgerService;
use App\Domain\Payments\PaychanguClient;
use App\Domain\Wallet\WalletService;
use App\Models\DepositIntent;
use App\Models\User;
use Illuminate\Support\Str;

class DepositService
{
    public function __construct(
        private readonly PaychanguClient $paychangu,
        private readonly WalletService $wallets,
        private readonly LedgerService $ledger,
    ) {
    }

    public function createPaychanguIntent(User $user, int $amountMinor): DepositIntent
    {
        $intent = DepositIntent::query()->create([
            'user_id' => $user->id,
            'tx_ref' => 'WY-DEP-'.Str::upper(Str::random(12)),
            'currency' => 'MWK',
            'amount_minor' => $amountMinor,
            'status' => 'pending',
        ]);

        $checkout = $this->paychangu->createCheckout([
            'amount' => $amountMinor / 100,
            'currency' => 'MWK',
            'tx_ref' => $intent->tx_ref,
            'callback_url' => route('wallet.dashboard'),
            'return_url' => route('wallet.dashboard'),
            'email' => $user->email,
            'first_name' => $user->name,
        ]);

        $intent->forceFill([
            'checkout_url' => data_get($checkout, 'checkout_url') ?? data_get($checkout, 'data.checkout_url'),
            'metadata' => ['checkout_response' => $checkout],
        ])->save();

        return $intent;
    }

    public function postVerifiedDeposit(DepositIntent $intent, array $verificationPayload = []): void
    {
        if ($intent->status === 'success') {
            return;
        }

        $this->wallets->ensureUserWallet($intent->user, 'MWK');

        $this->ledger->post('deposit', "deposit:{$intent->tx_ref}", [
            [
                'account_code' => 'SYS:PAYCHANGU_CLEARING:MWK',
                'direction' => 'debit',
                'currency' => 'MWK',
                'amount_minor' => $intent->amount_minor,
            ],
            [
                'account_code' => "USER:{$intent->user_id}:MWK:main",
                'direction' => 'credit',
                'currency' => 'MWK',
                'amount_minor' => $intent->amount_minor,
            ],
        ], [
            'provider' => 'paychangu',
            'tx_ref' => $intent->tx_ref,
            'verification' => $verificationPayload,
        ]);

        $intent->forceFill([
            'status' => 'success',
            'provider_reference' => data_get($verificationPayload, 'transaction_id'),
        ])->save();
    }
}
