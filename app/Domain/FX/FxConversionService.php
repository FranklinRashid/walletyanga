<?php

namespace App\Domain\FX;

use App\Domain\Ledger\LedgerService;
use App\Domain\Wallet\WalletService;
use App\Models\FxConversion;
use App\Models\FxQuote;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class FxConversionService
{
    public function __construct(
        private readonly WalletService $wallets,
        private readonly LedgerService $ledger,
        private readonly FxSpotRateService $spotRates,
    ) {
    }

    public function quoteMwkToUsd(User $user, int $mwkAmountMinor, ?string $rate = null, ?int $spreadBps = null): FxQuote
    {
        $spot = $this->spotRates->getMwkPerUsd();
        $rate ??= number_format((float) $spot['rate'], 8, '.', '');
        $spreadBps ??= (int) config('fx.conversion_spread_bps', 150);

        $effectiveRate = (float) $rate * (1 + ($spreadBps / 10_000));
        $usdMinor = (int) floor(($mwkAmountMinor / 100) / $effectiveRate * 100);

        return FxQuote::query()->create([
            'user_id' => $user->id,
            'from_currency' => 'MWK',
            'to_currency' => 'USD',
            'from_amount_minor' => $mwkAmountMinor,
            'rate' => $rate,
            'spread_bps' => $spreadBps,
            'effective_rate' => number_format($effectiveRate, 8, '.', ''),
            'to_amount_minor' => $usdMinor,
            'expires_at' => now()->addMinutes(5),
            'status' => 'quoted',
            'metadata' => [
                'spot_source' => $spot['source'],
                'spot_as_of' => $spot['as_of'],
            ],
        ]);
    }

    public function acceptQuote(FxQuote $quote): FxConversion
    {
        return DB::transaction(function () use ($quote): FxConversion {
            $quote = FxQuote::query()->lockForUpdate()->findOrFail($quote->id);

            if ($quote->status !== 'quoted') {
                throw new InvalidArgumentException('FX quote has already been used.');
            }

            if ($quote->expires_at->isPast()) {
                $quote->forceFill(['status' => 'expired'])->save();
                throw new InvalidArgumentException('FX quote has expired.');
            }

            $this->wallets->ensureUserWallet($quote->user, 'MWK');
            $this->wallets->ensureUserWallet($quote->user, 'USD');

            $mwkAccount = $quote->user->ledgerAccounts()
                ->where('code', "USER:{$quote->user_id}:MWK:main")
                ->firstOrFail();

            if ($this->ledger->accountBalanceMinor($mwkAccount) < $quote->from_amount_minor) {
                throw new InvalidArgumentException('Insufficient MWK wallet balance.');
            }

            $transaction = $this->ledger->post('fx_conversion', "fx_quote:{$quote->id}", [
                [
                    'account_code' => "USER:{$quote->user_id}:MWK:main",
                    'direction' => 'debit',
                    'currency' => 'MWK',
                    'amount_minor' => $quote->from_amount_minor,
                ],
                [
                    'account_code' => 'SYS:FX_POSITION:MWK',
                    'direction' => 'credit',
                    'currency' => 'MWK',
                    'amount_minor' => $quote->from_amount_minor,
                ],
                [
                    'account_code' => 'SYS:FX_POSITION:USD',
                    'direction' => 'debit',
                    'currency' => 'USD',
                    'amount_minor' => $quote->to_amount_minor,
                ],
                [
                    'account_code' => "USER:{$quote->user_id}:USD:main",
                    'direction' => 'credit',
                    'currency' => 'USD',
                    'amount_minor' => $quote->to_amount_minor,
                ],
            ], [
                'quote_id' => $quote->id,
                'rate' => $quote->rate,
                'effective_rate' => $quote->effective_rate,
                'spread_bps' => $quote->spread_bps,
            ]);

            $quote->forceFill(['status' => 'accepted'])->save();

            return FxConversion::query()->create([
                'user_id' => $quote->user_id,
                'fx_quote_id' => $quote->id,
                'ledger_transaction_id' => $transaction->id,
                'status' => 'posted',
            ]);
        });
    }
}
