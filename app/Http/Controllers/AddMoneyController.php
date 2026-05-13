<?php

namespace App\Http\Controllers;

use App\Domain\Deposits\DepositService;
use App\Domain\FX\FxSpotRateService;
use App\Enums\KycProfileStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AddMoneyController extends Controller
{
    public function create(FxSpotRateService $fx): View|RedirectResponse
    {
        $user = request()->user();
        if ($user->kycProfile?->status !== KycProfileStatus::APPROVED->value) {
            return redirect()->route('wallet.dashboard')
                ->with('status', 'Your KYC must be approved before you can add money to your wallet.');
        }

        $spot = $fx->getMwkPerUsd();

        return view('wallet.add-money', [
            'spot' => $spot,
            'minMwkMinor' => (int) config('fx.min_deposit_mwk_minor'),
            'maxMwkMinor' => (int) config('fx.max_deposit_mwk_minor'),
        ]);
    }

    public function store(Request $request, DepositService $deposits, FxSpotRateService $fx): RedirectResponse
    {
        $user = $request->user();
        if ($user->kycProfile?->status !== KycProfileStatus::APPROVED->value) {
            return redirect()->route('wallet.dashboard')
                ->with('status', 'Your KYC must be approved before you can add money to your wallet.');
        }

        $validated = $request->validate([
            'input_currency' => ['required', Rule::in(['USD', 'MWK'])],
            'amount' => ['required', 'numeric', 'gt:0'],
        ]);

        $spot = $fx->getMwkPerUsd();
        $rate = $spot['rate'];
        $minMinor = (int) config('fx.min_deposit_mwk_minor');
        $maxMinor = (int) config('fx.max_deposit_mwk_minor');

        $amount = (float) $validated['amount'];
        if ($validated['input_currency'] === 'MWK') {
            $mwkMinor = (int) round($amount * 100);
        } else {
            $mwkMinor = (int) round($amount * $rate * 100);
        }

        if ($mwkMinor < $minMinor) {
            throw ValidationException::withMessages([
                'amount' => 'Amount is below the minimum deposit of '.number_format($minMinor / 100, 2).' MWK.',
            ]);
        }

        if ($mwkMinor > $maxMinor) {
            throw ValidationException::withMessages([
                'amount' => 'Amount exceeds the maximum deposit of '.number_format($maxMinor / 100, 2).' MWK.',
            ]);
        }

        try {
            $intent = $deposits->createPaychanguIntent($user, $mwkMinor);
        } catch (\Throwable $e) {
            Log::error('paychangu_checkout_failed', ['message' => $e->getMessage()]);

            return redirect()->route('wallet.add-money')
                ->with('status', 'We could not start the payment. Please try again shortly.');
        }

        $checkoutUrl = $intent->checkout_url;
        if (! $checkoutUrl) {
            return redirect()->route('wallet.add-money')
                ->with('status', 'Payment link was not returned. Please try again.');
        }

        return redirect()->away($checkoutUrl);
    }
}
