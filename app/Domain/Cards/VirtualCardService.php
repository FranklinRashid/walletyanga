<?php

namespace App\Domain\Cards;

use App\Domain\Ledger\LedgerService;
use App\Domain\Wallet\WalletService;
use App\Models\CardAuthorization;
use App\Models\CardTransaction;
use App\Models\User;
use App\Models\VirtualCard;
use App\Models\Wallet;
use Illuminate\Support\Str;
use InvalidArgumentException;

class VirtualCardService
{
    public function __construct(
        private readonly CardIssuerProvider $issuer,
        private readonly WalletService $wallets,
        private readonly LedgerService $ledger,
    ) {}

    public function createCard(User $user, ?string $nickname = null): VirtualCard
    {
        $this->wallets->ensureUserWallet($user, 'USD');

        $providerCard = $this->issuer->createVirtualCard([
            'user' => $user,
            'user_id' => $user->id,
            'email' => $user->email,
            'currency' => 'USD',
        ]);

        return VirtualCard::query()->create([
            'user_id' => $user->id,
            'provider' => $providerCard['provider'] ?? config('services.cards.issuer', 'sandbox'),
            'provider_card_id' => $providerCard['provider_card_id'],
            'masked_pan' => $providerCard['masked_pan'] ?? null,
            'brand' => $providerCard['brand'] ?? null,
            'currency' => $providerCard['currency'] ?? 'USD',
            'status' => $providerCard['status'] ?? 'active',
            'nickname' => $nickname,
            'daily_limit_minor' => $providerCard['daily_limit_minor'] ?? null,
            'monthly_limit_minor' => $providerCard['monthly_limit_minor'] ?? null,
            'controls' => $providerCard['controls'] ?? null,
        ]);
    }

    public function revealDetails(VirtualCard $card): array
    {
        if ($card->status !== 'active') {
            throw new InvalidArgumentException('Only active cards can be revealed.');
        }

        return $this->issuer->revealDetails($card);
    }

    public function freeze(VirtualCard $card): VirtualCard
    {
        if ($card->status !== 'active') {
            throw new InvalidArgumentException('Only active cards can be frozen.');
        }

        $this->issuer->freeze($card);

        $card->forceFill(['status' => 'inactive'])->save();

        return $card;
    }

    public function unfreeze(VirtualCard $card): VirtualCard
    {
        if ($card->status !== 'inactive') {
            throw new InvalidArgumentException('Only frozen cards can be unfrozen.');
        }

        $this->issuer->unfreeze($card);

        $card->forceFill(['status' => 'active'])->save();

        return $card;
    }

    public function topUp(VirtualCard $card, int $amountMinor): CardTransaction
    {
        if ($card->status !== 'active') {
            throw new InvalidArgumentException('Only active cards can be funded.');
        }

        if ($amountMinor <= 0) {
            throw new InvalidArgumentException('Top-up amount must be greater than zero.');
        }

        $this->wallets->ensureUserWallet($card->user, 'USD');
        $this->ensureCardWallet($card);

        $mainAccount = $card->user->ledgerAccounts()
            ->where('code', "USER:{$card->user_id}:USD:main")
            ->firstOrFail();

        if ($this->ledger->accountBalanceMinor($mainAccount) < $amountMinor) {
            throw new InvalidArgumentException('Insufficient USD wallet balance.');
        }

        $providerTransactionId = 'card_topup_'.Str::lower(Str::random(24));

        $transaction = $this->ledger->post('card_top_up', $providerTransactionId, [
            [
                'account_code' => "USER:{$card->user_id}:USD:main",
                'direction' => 'debit',
                'currency' => 'USD',
                'amount_minor' => $amountMinor,
            ],
            [
                'account_code' => $this->cardWalletCode($card),
                'direction' => 'credit',
                'currency' => 'USD',
                'amount_minor' => $amountMinor,
            ],
        ], [
            'virtual_card_id' => $card->id,
        ]);

        return CardTransaction::query()->create([
            'virtual_card_id' => $card->id,
            'provider_transaction_id' => $providerTransactionId,
            'type' => 'top_up',
            'currency' => 'USD',
            'amount_minor' => $amountMinor,
            'status' => 'posted',
            'ledger_transaction_id' => $transaction->id,
        ]);
    }

    public function cardBalanceMinor(VirtualCard $card): int
    {
        return $this->ledger->accountBalanceMinor($this->ensureCardWallet($card)->ledgerAccount);
    }

    public function authorize(VirtualCard $card, string $providerAuthorizationId, int $amountMinor, array $metadata = []): CardAuthorization
    {
        if ($card->status !== 'active') {
            throw new InvalidArgumentException('Card is not active.');
        }

        $this->ensureCardWallet($card);
        $this->wallets->ensureUserWallet($card->user, 'USD', 'card_reserved');

        $cardAccount = $card->user->ledgerAccounts()
            ->where('code', $this->cardWalletCode($card))
            ->firstOrFail();

        if ($this->ledger->accountBalanceMinor($cardAccount) < $amountMinor) {
            throw new InvalidArgumentException('Insufficient card balance.');
        }

        $transaction = $this->ledger->post('card_authorization', "card_auth:{$providerAuthorizationId}", [
            [
                'account_code' => $this->cardWalletCode($card),
                'direction' => 'debit',
                'currency' => 'USD',
                'amount_minor' => $amountMinor,
            ],
            [
                'account_code' => "USER:{$card->user_id}:USD:card_reserved",
                'direction' => 'credit',
                'currency' => 'USD',
                'amount_minor' => $amountMinor,
            ],
        ], $metadata);

        return CardAuthorization::query()->create([
            'virtual_card_id' => $card->id,
            'provider_authorization_id' => $providerAuthorizationId,
            'currency' => 'USD',
            'amount_minor' => $amountMinor,
            'merchant_name' => $metadata['merchant_name'] ?? null,
            'merchant_category' => $metadata['merchant_category'] ?? null,
            'status' => 'authorized',
            'ledger_transaction_id' => $transaction->id,
            'metadata' => $metadata,
        ]);
    }

    public function capture(CardAuthorization $authorization, string $providerTransactionId): CardTransaction
    {
        $card = $authorization->card;

        $transaction = $this->ledger->post('card_capture', "card_capture:{$providerTransactionId}", [
            [
                'account_code' => "USER:{$card->user_id}:USD:card_reserved",
                'direction' => 'debit',
                'currency' => 'USD',
                'amount_minor' => $authorization->amount_minor,
            ],
            [
                'account_code' => 'SYS:CARD_ISSUER_PAYABLE:USD',
                'direction' => 'credit',
                'currency' => 'USD',
                'amount_minor' => $authorization->amount_minor,
            ],
        ], [
            'authorization_id' => $authorization->id,
        ]);

        $authorization->forceFill(['status' => 'captured'])->save();

        return CardTransaction::query()->create([
            'virtual_card_id' => $card->id,
            'card_authorization_id' => $authorization->id,
            'provider_transaction_id' => $providerTransactionId,
            'type' => 'capture',
            'currency' => 'USD',
            'amount_minor' => $authorization->amount_minor,
            'status' => 'posted',
            'ledger_transaction_id' => $transaction->id,
        ]);
    }

    private function ensureCardWallet(VirtualCard $card): Wallet
    {
        return $this->wallets->ensureUserWallet($card->user, 'USD', "card_{$card->id}");
    }

    private function cardWalletCode(VirtualCard $card): string
    {
        return "USER:{$card->user_id}:USD:card_{$card->id}";
    }
}
