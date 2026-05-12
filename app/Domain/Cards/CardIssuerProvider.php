<?php

namespace App\Domain\Cards;

use App\Models\VirtualCard;

interface CardIssuerProvider
{
    public function createVirtualCard(array $payload): array;

    public function freeze(VirtualCard $card): void;

    public function unfreeze(VirtualCard $card): void;
}
