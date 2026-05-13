<?php

namespace App\Domain\Payments;

use Illuminate\Support\Facades\Http;

class PaychanguClient
{
    public function __construct(
        private readonly ?string $publicKey = null,
        private readonly ?string $secretKey = null,
        private readonly ?string $webhookSecret = null,
    ) {}

    public function publicKey(): ?string
    {
        return $this->publicKey;
    }

    public function createCheckout(array $payload): array
    {
        if (! $this->secretKey) {
            return [
                'status' => 'sandbox',
                'checkout_url' => url('/sandbox/paychangu/'.($payload['tx_ref'] ?? 'missing-reference')),
            ];
        }

        return Http::withToken($this->secretKey)
            ->acceptJson()
            ->post('https://api.paychangu.com/payment', $payload)
            ->throw()
            ->json();
    }

    public function verifyPayment(string $txRef): array
    {
        if (! $this->secretKey) {
            return ['status' => 'success', 'tx_ref' => $txRef, 'sandbox' => true];
        }

        return Http::withToken($this->secretKey)
            ->acceptJson()
            ->get("https://api.paychangu.com/verify-payment/{$txRef}")
            ->throw()
            ->json();
    }

    public function isValidWebhookSignature(string $rawPayload, ?string $signature): bool
    {
        if (! $this->webhookSecret) {
            return true;
        }

        if (! $signature) {
            return false;
        }

        return hash_equals(hash_hmac('sha256', $rawPayload, $this->webhookSecret), $signature);
    }
}
