<?php

namespace Tests\Feature;

use App\Domain\Deposits\DepositService;
use App\Domain\FX\FxConversionService;
use App\Domain\Cards\VirtualCardService;
use App\Models\DepositIntent;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletYangaMoneyFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_deposit_fx_conversion_and_card_capture_are_ledgered(): void
    {
        $this->seed(DatabaseSeeder::class);

        $user = User::factory()->create();

        $deposit = DepositIntent::query()->create([
            'user_id' => $user->id,
            'provider' => 'paychangu',
            'tx_ref' => 'WY-TEST-001',
            'currency' => 'MWK',
            'amount_minor' => 170_000_00,
            'status' => 'pending',
        ]);

        app(DepositService::class)->postVerifiedDeposit($deposit, ['status' => 'success']);

        $quote = app(FxConversionService::class)->quoteMwkToUsd($user, 170_000_00, '1700.00000000', 0);
        app(FxConversionService::class)->acceptQuote($quote);

        $card = app(VirtualCardService::class)->createCard($user, 'Online services');
        $authorization = app(VirtualCardService::class)->authorize($card, 'auth_001', 25_00, [
            'merchant_name' => 'Example SaaS',
        ]);
        app(VirtualCardService::class)->capture($authorization, 'capture_001');

        $this->assertDatabaseHas('wallets', [
            'user_id' => $user->id,
            'currency' => 'MWK',
            'cached_balance_minor' => 0,
        ]);

        $this->assertDatabaseHas('wallets', [
            'user_id' => $user->id,
            'currency' => 'USD',
            'type' => 'main',
            'cached_balance_minor' => 75_00,
        ]);

        $this->assertDatabaseHas('ledger_transactions', ['type' => 'deposit']);
        $this->assertDatabaseHas('ledger_transactions', ['type' => 'fx_conversion']);
        $this->assertDatabaseHas('ledger_transactions', ['type' => 'card_authorization']);
        $this->assertDatabaseHas('ledger_transactions', ['type' => 'card_capture']);
    }
}
