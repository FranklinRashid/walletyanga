<?php

namespace App\Http\Controllers;

use App\Domain\Deposits\DepositService;
use App\Domain\Payments\PaychanguClient;
use App\Models\DepositIntent;
use App\Models\PaymentGatewayEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaychanguWebhookController extends Controller
{
    public function __invoke(Request $request, PaychanguClient $paychangu, DepositService $deposits): JsonResponse
    {
        $rawPayload = $request->getContent();
        $signature = $request->header('Signature');
        $payload = $request->json()->all();
        $txRef = data_get($payload, 'tx_ref') ?? data_get($payload, 'data.tx_ref');

        $event = PaymentGatewayEvent::query()->create([
            'provider' => 'paychangu',
            'event_id' => data_get($payload, 'event_id'),
            'event_type' => data_get($payload, 'event_type') ?? data_get($payload, 'event'),
            'tx_ref' => $txRef,
            'signature' => $signature,
            'payload' => $payload,
            'processing_status' => 'received',
        ]);

        if (! $paychangu->isValidWebhookSignature($rawPayload, $signature)) {
            $event->forceFill(['processing_status' => 'rejected', 'processing_error' => 'Invalid signature'])->save();

            return response()->json(['message' => 'Invalid signature'], 401);
        }

        $intent = DepositIntent::query()->where('tx_ref', $txRef)->first();

        if (! $intent) {
            $event->forceFill(['processing_status' => 'failed', 'processing_error' => 'Unknown tx_ref'])->save();

            return response()->json(['message' => 'Unknown transaction reference'], 404);
        }

        $verification = $paychangu->verifyPayment($intent->tx_ref);
        $status = data_get($verification, 'status') ?? data_get($verification, 'data.status');

        if (in_array($status, ['success', 'successful'], true)) {
            $deposits->postVerifiedDeposit($intent, $verification);
            $event->forceFill(['processing_status' => 'processed'])->save();

            return response()->json(['message' => 'Deposit credited']);
        }

        $event->forceFill(['processing_status' => 'ignored', 'processing_error' => "Payment status {$status}"])->save();

        return response()->json(['message' => 'Payment not successful']);
    }
}
