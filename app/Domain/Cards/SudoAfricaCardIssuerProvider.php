<?php

namespace App\Domain\Cards;

use App\Models\CardProviderProfile;
use App\Models\User;
use App\Models\VirtualCard;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class SudoAfricaCardIssuerProvider implements CardIssuerProvider
{
    public function createVirtualCard(array $payload): array
    {
        $user = $this->resolveUser($payload);
        $profile = $this->providerProfile($user);

        if (! $profile->provider_customer_id) {
            $customer = $this->post('/customers', $this->customerPayload($user));
            $profile->forceFill([
                'provider_customer_id' => $this->extractId($customer),
                'metadata' => array_merge($profile->metadata ?? [], ['customer' => $this->redacted($customer)]),
            ])->save();
        }

        if (! $profile->provider_debit_account_id) {
            $account = $this->post('/accounts', [
                'type' => config('services.sudo.account_type', 'wallet'),
                'currency' => config('services.sudo.card_currency', 'USD'),
                'accountType' => config('services.sudo.account_kind', 'Savings'),
                'customerId' => $profile->provider_customer_id,
            ]);

            $profile->forceFill([
                'provider_debit_account_id' => $this->extractId($account),
                'metadata' => array_merge($profile->metadata ?? [], ['debit_account' => $this->redacted($account)]),
            ])->save();
        }

        $spendingControls = [
            'allowedCategories' => [],
            'blockedCategories' => [],
            'channels' => [
                'atm' => false,
                'pos' => false,
                'web' => true,
                'mobile' => true,
            ],
            'spendingLimits' => [
                [
                    'amount' => $payload['daily_limit_minor'] ?? config('services.sudo.default_daily_limit_minor', 5000),
                    'interval' => 'daily',
                ],
            ],
        ];

        $card = $this->post('/cards', [
            'customerId' => $profile->provider_customer_id,
            'debitAccountId' => $profile->provider_debit_account_id,
            'type' => 'virtual',
            'currency' => config('services.sudo.card_currency', 'USD'),
            'status' => 'active',
            'brand' => config('services.sudo.card_brand', 'Visa'),
            'issuerCountry' => config('services.sudo.issuer_country', 'USA'),
            'enable2FA' => true,
            'metadata' => json_encode([
                'wallet_yanga_user_id' => $user->id,
                'wallet_yanga_email' => $user->email,
            ], JSON_THROW_ON_ERROR),
            'spendingControls' => $spendingControls,
        ]);

        return [
            'provider' => 'sudo_africa',
            'provider_card_id' => $this->extractId($card),
            'masked_pan' => $this->maskedPan($card),
            'brand' => Arr::get($card, 'brand') ?? Arr::get($card, 'cardBrand') ?? config('services.sudo.card_brand', 'Visa'),
            'currency' => Arr::get($card, 'currency', config('services.sudo.card_currency', 'USD')),
            'status' => Arr::get($card, 'status', 'active'),
            'controls' => [
                'sudo_customer_id' => $profile->provider_customer_id,
                'sudo_debit_account_id' => $profile->provider_debit_account_id,
                'spending_controls' => $spendingControls,
                'provider_response' => $this->redacted($card),
            ],
        ];
    }

    public function freeze(VirtualCard $card): void
    {
        $this->put("/card/{$card->provider_card_id}", ['status' => 'inactive']);
    }

    public function revealDetails(VirtualCard $card): array
    {
        $response = $this->vaultClient()
            ->get("/cards/{$card->provider_card_id}", ['reveal' => 'true']);

        if ($response->failed()) {
            throw new RuntimeException($this->errorMessage('/vault/cards', $response->status(), $response->json()));
        }

        $payload = $response->json() ?? [];

        return [
            'number' => (string) (Arr::get($payload, 'number') ?? Arr::get($payload, 'pan') ?? Arr::get($payload, 'data.number')),
            'expiry_month' => (string) (Arr::get($payload, 'expiryMonth') ?? Arr::get($payload, 'expMonth') ?? Arr::get($payload, 'data.expiryMonth')),
            'expiry_year' => (string) (Arr::get($payload, 'expiryYear') ?? Arr::get($payload, 'expYear') ?? Arr::get($payload, 'data.expiryYear')),
            'cvv' => (string) (Arr::get($payload, 'cvv2') ?? Arr::get($payload, 'cvv') ?? Arr::get($payload, 'data.cvv2')),
            'cardholder_name' => (string) (Arr::get($payload, 'nameOnCard') ?? Arr::get($payload, 'cardholderName') ?? $card->user->name),
        ];
    }

    public function unfreeze(VirtualCard $card): void
    {
        $this->put("/card/{$card->provider_card_id}", ['status' => 'active']);
    }

    private function providerProfile(User $user): CardProviderProfile
    {
        return CardProviderProfile::query()->firstOrCreate([
            'user_id' => $user->id,
            'provider' => 'sudo_africa',
        ]);
    }

    private function customerPayload(User $user): array
    {
        $nameParts = preg_split('/\s+/', trim($user->name), 2) ?: [];
        $kyc = $user->kycProfile;

        return [
            'type' => 'individual',
            'name' => $user->name,
            'phoneNumber' => $user->profile?->phone,
            'status' => 'active',
            'emailAddress' => $user->email,
            'individual' => [
                'firstName' => $nameParts[0] ?? $user->name,
                'lastName' => $nameParts[1] ?? $nameParts[0] ?? $user->name,
            ],
            'billingAddress' => [
                'line1' => $kyc?->address ?: 'Wallet Yanga customer',
                'line2' => '',
                'city' => $kyc?->city_district ?: 'Lilongwe',
                'state' => $kyc?->city_district ?: 'Lilongwe',
                'country' => config('services.sudo.billing_country', 'MW'),
                'postalCode' => config('services.sudo.postal_code', '00000'),
            ],
        ];
    }

    private function client(): PendingRequest
    {
        $apiKey = config('services.sudo.api_key');

        if (! $apiKey) {
            throw new RuntimeException('Sudo Africa API key is not configured.');
        }

        return Http::baseUrl(rtrim((string) config('services.sudo.base_url'), '/'))
            ->acceptJson()
            ->asJson()
            ->withHeaders([
                'Authorization' => trim(config('services.sudo.auth_scheme', 'Bearer').' '.$apiKey),
            ])
            ->timeout(30);
    }

    private function vaultClient(): PendingRequest
    {
        $apiKey = config('services.sudo.api_key');

        if (! $apiKey) {
            throw new RuntimeException('Sudo Africa API key is not configured.');
        }

        return Http::baseUrl(rtrim((string) config('services.sudo.vault_url'), '/'))
            ->acceptJson()
            ->asJson()
            ->withHeaders([
                'Authorization' => trim(config('services.sudo.auth_scheme', 'Bearer').' '.$apiKey),
            ])
            ->timeout(30);
    }

    private function post(string $path, array $payload): array
    {
        $response = $this->client()->post($path, $payload);

        if ($response->failed()) {
            throw new RuntimeException($this->errorMessage($path, $response->status(), $response->json()));
        }

        return $response->json() ?? [];
    }

    private function put(string $path, array $payload): array
    {
        $response = $this->client()->put($path, $payload);

        if ($response->failed()) {
            throw new RuntimeException($this->errorMessage($path, $response->status(), $response->json()));
        }

        return $response->json() ?? [];
    }

    private function resolveUser(array $payload): User
    {
        if (($payload['user'] ?? null) instanceof User) {
            return $payload['user']->loadMissing('profile', 'kycProfile');
        }

        return User::query()
            ->with('profile', 'kycProfile')
            ->findOrFail($payload['user_id']);
    }

    private function extractId(array $response): string
    {
        $id = Arr::get($response, '_id')
            ?? Arr::get($response, 'id')
            ?? Arr::get($response, 'data._id')
            ?? Arr::get($response, 'data.id');

        if (! $id) {
            throw new RuntimeException('Sudo Africa response did not include an object ID.');
        }

        return (string) $id;
    }

    private function maskedPan(array $card): ?string
    {
        $masked = Arr::get($card, 'maskedPan')
            ?? Arr::get($card, 'maskedPAN')
            ?? Arr::get($card, 'masked_pan')
            ?? Arr::get($card, 'data.maskedPan');

        if ($masked) {
            return (string) $masked;
        }

        $pan = Arr::get($card, 'number') ?? Arr::get($card, 'pan') ?? Arr::get($card, 'data.number');

        if (! $pan) {
            return null;
        }

        $lastFour = Str::substr(preg_replace('/\D/', '', (string) $pan) ?: '', -4);

        return $lastFour ? '************'.$lastFour : null;
    }

    private function redacted(array $response): array
    {
        foreach (['number', 'pan', 'cvv', 'cvv2', 'pin'] as $key) {
            if (array_key_exists($key, $response)) {
                $response[$key] = '[redacted]';
            }
        }

        return $response;
    }

    private function errorMessage(string $path, int $status, ?array $body): string
    {
        $message = Arr::get($body ?? [], 'message')
            ?? Arr::get($body ?? [], 'error')
            ?? 'Provider request failed.';

        return "Sudo Africa {$path} failed with HTTP {$status}: {$message}";
    }
}
