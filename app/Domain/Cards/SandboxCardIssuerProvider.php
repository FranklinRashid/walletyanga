<?php

namespace App\Domain\Cards;

use App\Models\VirtualCard;
use Illuminate\Support\Str;

class SandboxCardIssuerProvider implements CardIssuerProvider
{
    public function createVirtualCard(array $payload): array
    {
        return [
            'provider' => 'sandbox',
            'provider_card_id' => 'sandbox_card_'.Str::lower(Str::random(16)),
            'masked_pan' => '424242******'.random_int(1000, 9999),
            'brand' => 'Visa',
            'currency' => $payload['currency'] ?? 'USD',
            'status' => 'active',
            'controls' => [
                'spending_controls' => [
                    'channels' => [
                        'atm' => false,
                        'pos' => false,
                        'web' => true,
                        'mobile' => true,
                    ],
                ],
            ],
        ];
    }

    public function freeze(VirtualCard $card): void
    {
    }

    public function revealDetails(VirtualCard $card): array
    {
        return [
            'number' => '4242424242424242',
            'expiry_month' => '12',
            'expiry_year' => now()->addYears(3)->format('Y'),
            'cvv' => '123',
            'cardholder_name' => $card->user->name,
        ];
    }

    public function unfreeze(VirtualCard $card): void
    {
    }
}
