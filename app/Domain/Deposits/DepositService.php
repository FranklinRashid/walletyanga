<?php

namespace App\Domain\Deposits;

use App\Domain\Ledger\LedgerService;
use App\Domain\Payments\PaychanguClient;
use App\Domain\Wallet\WalletService;
use App\Models\DepositIntent;
use App\Models\User;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DepositService
{
    public function __construct(
        private readonly PaychanguClient $paychangu,
        private readonly WalletService $wallets,
        private readonly LedgerService $ledger,
    ) {}

    public function createPaychanguIntent(User $user, int $amountMinor): DepositIntent
    {
        $intent = DepositIntent::query()->create([
            'user_id' => $user->id,
            'tx_ref' => 'WY-DEP-'.Str::upper(Str::random(12)),
            'currency' => 'MWK',
            'amount_minor' => $amountMinor,
            'status' => 'pending',
        ]);

        $nameParts = preg_split('/\s+/', trim($user->name), 2, PREG_SPLIT_NO_EMPTY) ?: [$user->name];
        $firstName = $nameParts[0] ?? $user->name;
        $lastName = $nameParts[1] ?? '';

        $appBaseUrl = rtrim((string) config('app.url'), '/');
        $requestBaseUrl = Request::getSchemeAndHttpHost();
        $baseUrl = $requestBaseUrl ?: $appBaseUrl;

        $checkoutPayload = [
            'amount' => number_format($amountMinor / 100, 2, '.', ''),
            'currency' => 'MWK',
            'tx_ref' => $intent->tx_ref,
            'callback_url' => $baseUrl.route('paychangu.callback', absolute: false),
            'return_url' => $baseUrl.route('wallet.dashboard', absolute: false),
            'email' => $user->email,
            'first_name' => $firstName,
            'last_name' => $lastName,
        ];

        Log::debug('paychangu_checkout_payload', ['payload' => $checkoutPayload]);

        $checkout = $this->paychangu->createCheckout($checkoutPayload);

        Log::debug('paychangu_checkout_response', ['response' => $checkout]);

        $intent->forceFill([
            'checkout_url' => data_get($checkout, 'checkout_url') ?? data_get($checkout, 'data.checkout_url'),
            'metadata' => ['checkout_response' => $checkout],
        ])->save();

        return $intent;
    }

    /**
     * Credit the wallet when Paychangu verification reports a successful payment.
     */
    public function settleFromVerification(DepositIntent $intent, array $verification): bool
    {
        $status = data_get($verification, 'status') ?? data_get($verification, 'data.status');

        if (! in_array($status, ['success', 'successful'], true)) {
            return false;
        }

        $this->postVerifiedDeposit($intent, $verification);

        return true;
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
