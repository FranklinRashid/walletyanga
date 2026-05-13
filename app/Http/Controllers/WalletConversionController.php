<?php

namespace App\Http\Controllers;

use App\Domain\FX\FxConversionService;
use App\Domain\FX\FxSpotRateService;
use App\Domain\Wallet\WalletService;
use App\Enums\KycProfileStatus;
use App\Models\FxConversion;
use App\Models\FxQuote;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class WalletConversionController extends Controller
{
    public function create(Request $request, WalletService $wallets, FxSpotRateService $fx): View|RedirectResponse
    {
        $user = $request->user();

        if ($user->kycProfile?->status !== KycProfileStatus::APPROVED->value) {
            return redirect()->route('wallet.dashboard')
                ->with('status', 'Your KYC must be approved before you can convert MWK to USD.');
        }

        $mwkWallet = $wallets->ensureUserWallet($user, 'MWK');
        $usdWallet = $wallets->ensureUserWallet($user, 'USD');

        return view('wallet.convert', [
            'mwkWallet' => $mwkWallet,
            'usdWallet' => $usdWallet,
            'spot' => $fx->getMwkPerUsd(),
            'activeQuote' => FxQuote::query()
                ->where('user_id', $user->id)
                ->where('status', 'quoted')
                ->where('expires_at', '>', now())
                ->latest()
                ->first(),
            'recentConversions' => FxConversion::query()
                ->where('user_id', $user->id)
                ->with('quote')
                ->latest()
                ->limit(8)
                ->get(),
            'minMwkMinor' => (int) config('fx.min_conversion_mwk_minor', 1_000),
        ]);
    }

    public function quote(Request $request, WalletService $wallets, FxConversionService $fx): RedirectResponse
    {
        $user = $request->user();

        if ($user->kycProfile?->status !== KycProfileStatus::APPROVED->value) {
            return redirect()->route('wallet.dashboard')
                ->with('status', 'Your KYC must be approved before you can convert MWK to USD.');
        }

        $validated = $request->validate([
            'mwk_amount' => ['required', 'numeric', 'gt:0'],
        ]);

        $amountMinor = (int) round(((float) $validated['mwk_amount']) * 100);
        $minMinor = (int) config('fx.min_conversion_mwk_minor', 1_000);

        if ($amountMinor < $minMinor) {
            throw ValidationException::withMessages([
                'mwk_amount' => 'Amount is below the minimum conversion of '.number_format($minMinor / 100, 2).' MWK.',
            ]);
        }

        $mwkWallet = $wallets->ensureUserWallet($user, 'MWK');

        if ($mwkWallet->cached_balance_minor < $amountMinor) {
            throw ValidationException::withMessages([
                'mwk_amount' => 'Insufficient MWK wallet balance.',
            ]);
        }

        FxQuote::query()
            ->where('user_id', $user->id)
            ->where('status', 'quoted')
            ->update(['status' => 'cancelled']);

        $fx->quoteMwkToUsd($user, $amountMinor);

        return redirect()->route('wallet.convert')->with('status', 'FX quote generated. Confirm before it expires.');
    }

    public function accept(Request $request, FxQuote $quote, FxConversionService $fx): RedirectResponse
    {
        $user = $request->user();

        if ((int) $quote->user_id !== (int) $user->id) {
            abort(404);
        }

        if ($user->kycProfile?->status !== KycProfileStatus::APPROVED->value) {
            return redirect()->route('wallet.dashboard')
                ->with('status', 'Your KYC must be approved before you can convert MWK to USD.');
        }

        try {
            $fx->acceptQuote($quote);
        } catch (\InvalidArgumentException $e) {
            return redirect()->route('wallet.convert')->with('status', $e->getMessage());
        }

        return redirect()->route('wallet.convert')->with('status', 'MWK converted to USD.');
    }
}
