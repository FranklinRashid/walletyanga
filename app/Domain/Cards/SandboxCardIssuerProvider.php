<?php

namespace App\Domain\Cards;

use App\Models\VirtualCard;
use Illuminate\Support\Str;

class SandboxCardIssuerProvider implements CardIssuerProvider
{
    public function createVirtualCard(array $payload): array
    {
        return [
            'provider_card_id' => 'sandbox_card_'.Str::lower(Str::random(16)),
            'masked_pan' => '424242******'.random_int(1000, 9999),
            'brand' => 'Visa',
        ];
    }

    public function freeze(VirtualCard $card): void
    {
    }

    public function unfreeze(VirtualCard $card): void
    {
    }
}
