<?php

namespace App\Http\Controllers;

use App\Domain\Deposits\DepositService;
use App\Domain\Payments\PaychanguClient;
use App\Models\DepositIntent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Browser return URL after Paychangu checkout (GET with tx_ref).
 * Paychangu redirects customers here; webhooks may still POST separately.
 */
class PaychanguCallbackController extends Controller
{
    public function __invoke(Request $request, PaychanguClient $paychangu, DepositService $deposits): RedirectResponse
    {
        $txRef = $request->query('tx_ref');
        if (! is_string($txRef) || $txRef === '') {
            return $this->finish(null, 'Missing transaction reference.');
        }

        $intent = DepositIntent::query()->where('tx_ref', $txRef)->first();
        if (! $intent) {
            return $this->finish(null, 'We could not find this payment.');
        }

        try {
            $verification = $paychangu->verifyPayment($txRef);
        } catch (\Throwable $e) {
            Log::warning('paychangu_verify_failed', ['tx_ref' => $txRef, 'message' => $e->getMessage()]);

            return $this->finish($intent, 'Unable to confirm payment with Paychangu. If money left your account, contact support with reference: '.$txRef);
        }

        if ($deposits->settleFromVerification($intent, $verification)) {
            return $this->finish($intent, 'Payment received. Your MWK wallet has been credited.');
        }

        return $this->finish($intent, 'Payment was not completed or is still processing. Check your wallet in a few minutes.');
    }

    private function finish(?DepositIntent $intent, string $message): RedirectResponse
    {
        if ($intent && Auth::check() && (int) Auth::id() === (int) $intent->user_id) {
            return redirect()->route('wallet.dashboard')->with('status', $message);
        }

        if ($intent && Auth::check()) {
            return redirect()->route('wallet.dashboard')->with(
                'status',
                $message.' Sign in with the account you used to add money if the balance looks wrong.'
            );
        }

        return redirect()->route('login')->with('status', $message);
    }
}
