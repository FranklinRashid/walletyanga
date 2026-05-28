<?php

namespace Tests\Feature;

use App\Domain\Ledger\LedgerService;
use App\Domain\Wallet\WalletService;
use App\Enums\KycProfileStatus;
use App\Models\CardProviderProfile;
use App\Models\KycProfile;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\Wallet;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class VirtualCardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.cards.issuer' => 'sandbox']);
    }

    public function test_approved_user_can_view_virtual_card_page_with_balances(): void
    {
        $this->withoutVite();

        $user = $this->approvedUser(usdBalanceMinor: 1234);

        $this->actingAs($user)
            ->get('/wallet/cards')
            ->assertOk()
            ->assertSee('MWK Balance', false)
            ->assertSee('USD Balance', false)
            ->assertSee('12.34', false);
    }

    public function test_virtual_card_page_requires_approved_kyc(): void
    {
        $this->withoutVite();

        $user = User::factory()->create([
            'role' => 'customer',
            'status' => 'active',
        ]);

        KycProfile::query()->create([
            'user_id' => $user->id,
            'status' => KycProfileStatus::PENDING->value,
            'tier' => 'basic',
            'risk_rating' => 'standard',
        ]);

        $this->actingAs($user)
            ->get('/wallet/cards')
            ->assertRedirect(route('wallet.dashboard'))
            ->assertSessionHas('status', 'Your KYC must be approved before you can create a virtual card.');
    }

    public function test_user_needs_usd_balance_before_creating_card(): void
    {
        $this->withoutVite();

        $user = $this->approvedUser(usdBalanceMinor: 0);

        $this->actingAs($user)
            ->post('/wallet/cards', ['nickname' => 'Hosting'])
            ->assertSessionHasErrors('card');

        $this->assertDatabaseCount('virtual_cards', 0);
    }

    public function test_approved_user_can_create_sandbox_virtual_card(): void
    {
        $this->withoutVite();

        $user = $this->approvedUser(usdBalanceMinor: 2500);

        $this->actingAs($user)
            ->post('/wallet/cards', ['nickname' => 'Netflix'])
            ->assertRedirect(route('wallet.cards'))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('virtual_cards', [
            'user_id' => $user->id,
            'provider' => 'sandbox',
            'nickname' => 'Netflix',
            'currency' => 'USD',
            'status' => 'active',
        ]);
    }

    public function test_sudo_issuer_creates_customer_account_and_virtual_card(): void
    {
        $this->withoutVite();

        config([
            'services.cards.issuer' => 'sudo',
            'services.sudo.api_key' => 'test-key',
            'services.sudo.base_url' => 'https://api.sandbox.sudo.cards',
        ]);

        Http::fake([
            'api.sandbox.sudo.cards/customers' => Http::response(['_id' => 'cus_123', 'name' => 'Test User']),
            'api.sandbox.sudo.cards/accounts' => Http::response(['_id' => 'acct_123', 'currency' => 'USD']),
            'api.sandbox.sudo.cards/cards' => Http::response([
                '_id' => 'card_123',
                'brand' => 'Visa',
                'currency' => 'USD',
                'status' => 'active',
                'number' => '4111111111111111',
                'cvv2' => '123',
            ]),
        ]);

        $user = $this->approvedUser(usdBalanceMinor: 2500);

        $this->actingAs($user)
            ->post('/wallet/cards', ['nickname' => 'Sudo card'])
            ->assertRedirect(route('wallet.cards'));

        $this->assertDatabaseHas('card_provider_profiles', [
            'user_id' => $user->id,
            'provider' => 'sudo_africa',
            'provider_customer_id' => 'cus_123',
            'provider_debit_account_id' => 'acct_123',
        ]);

        $this->assertDatabaseHas('virtual_cards', [
            'user_id' => $user->id,
            'provider' => 'sudo_africa',
            'provider_card_id' => 'card_123',
            'masked_pan' => '************1111',
            'nickname' => 'Sudo card',
        ]);

        $this->assertSame(1, CardProviderProfile::query()->count());
    }

    public function test_user_can_reveal_sandbox_card_details_after_password_confirmation(): void
    {
        $this->withoutVite();

        $user = $this->approvedUser(usdBalanceMinor: 2500);

        $this->actingAs($user)->post('/wallet/cards', ['nickname' => 'Hosting']);

        $card = $user->virtualCards()->firstOrFail();

        $this->actingAs($user)
            ->post(route('wallet.cards.reveal', $card), ['password' => 'password'])
            ->assertOk()
            ->assertSee('4242424242424242', false)
            ->assertSee('123', false);

        $this->assertDatabaseHas('card_detail_reveal_events', [
            'user_id' => $user->id,
            'virtual_card_id' => $card->id,
            'successful' => true,
        ]);

        $this->assertDatabaseMissing('virtual_cards', [
            'provider_card_id' => $card->provider_card_id,
            'masked_pan' => '4242424242424242',
        ]);
    }

    public function test_wrong_password_does_not_reveal_card_details(): void
    {
        $this->withoutVite();

        $user = $this->approvedUser(usdBalanceMinor: 2500);

        $this->actingAs($user)->post('/wallet/cards', ['nickname' => 'Hosting']);

        $card = $user->virtualCards()->firstOrFail();

        $this->actingAs($user)
            ->post(route('wallet.cards.reveal', $card), ['password' => 'wrong-password'])
            ->assertSessionHasErrors('password');

        $this->assertDatabaseHas('card_detail_reveal_events', [
            'user_id' => $user->id,
            'virtual_card_id' => $card->id,
            'successful' => false,
        ]);
    }

    public function test_sudo_reveal_uses_vault_without_storing_sensitive_details(): void
    {
        $this->withoutVite();

        config([
            'services.cards.issuer' => 'sudo',
            'services.sudo.api_key' => 'test-key',
            'services.sudo.vault_url' => 'https://vault.sandbox.sudo.cards',
        ]);

        Http::fake([
            'vault.sandbox.sudo.cards/cards/card_123*' => Http::response([
                '_id' => 'card_123',
                'number' => '4111111111111111',
                'expiryMonth' => '09',
                'expiryYear' => '2029',
                'cvv2' => '321',
                'nameOnCard' => 'Test User',
            ]),
        ]);

        $user = $this->approvedUser(usdBalanceMinor: 2500);

        $card = $user->virtualCards()->create([
            'provider' => 'sudo_africa',
            'provider_card_id' => 'card_123',
            'masked_pan' => '************1111',
            'brand' => 'Visa',
            'currency' => 'USD',
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->post(route('wallet.cards.reveal', $card), ['password' => 'password'])
            ->assertOk()
            ->assertSee('4111111111111111', false)
            ->assertSee('321', false);

        $this->assertDatabaseMissing('virtual_cards', [
            'provider_card_id' => 'card_123',
            'masked_pan' => '4111111111111111',
        ]);
    }

    public function test_user_can_freeze_and_unfreeze_sandbox_card(): void
    {
        $this->withoutVite();

        $user = $this->approvedUser(usdBalanceMinor: 2500);

        $card = $user->virtualCards()->create([
            'provider' => 'sandbox',
            'provider_card_id' => 'sandbox_card_123',
            'masked_pan' => '424242******1234',
            'brand' => 'Visa',
            'currency' => 'USD',
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->post(route('wallet.cards.freeze', $card))
            ->assertRedirect(route('wallet.cards'))
            ->assertSessionHas('status', 'Virtual card has been frozen.');

        $this->assertDatabaseHas('virtual_cards', [
            'provider_card_id' => 'sandbox_card_123',
            'status' => 'inactive',
        ]);

        $this->actingAs($user)
            ->post(route('wallet.cards.unfreeze', $card->fresh()))
            ->assertRedirect(route('wallet.cards'))
            ->assertSessionHas('status', 'Virtual card has been reactivated.');

        $this->assertDatabaseHas('virtual_cards', [
            'provider_card_id' => 'sandbox_card_123',
            'status' => 'active',
        ]);
    }

    public function test_user_can_top_up_active_card_from_usd_wallet(): void
    {
        $this->seed(DatabaseSeeder::class);

        $user = $this->approvedUser(usdBalanceMinor: 5000);
        $this->creditUsdWallet($user, 5000);

        $card = $user->virtualCards()->create([
            'provider' => 'sandbox',
            'provider_card_id' => 'sandbox_card_123',
            'masked_pan' => '424242******1234',
            'brand' => 'Visa',
            'currency' => 'USD',
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->post(route('wallet.cards.top-up', $card), ['amount' => '20.00'])
            ->assertRedirect(route('wallet.cards'))
            ->assertSessionHas('status', 'Virtual card funded with USD 20.00.');

        $this->assertDatabaseHas('wallets', [
            'user_id' => $user->id,
            'currency' => 'USD',
            'type' => 'main',
            'cached_balance_minor' => 3000,
        ]);

        $this->assertDatabaseHas('wallets', [
            'user_id' => $user->id,
            'currency' => 'USD',
            'type' => "card_{$card->id}",
            'cached_balance_minor' => 2000,
        ]);

        $this->assertDatabaseHas('card_transactions', [
            'virtual_card_id' => $card->id,
            'type' => 'top_up',
            'currency' => 'USD',
            'amount_minor' => 2000,
            'status' => 'posted',
        ]);
    }

    public function test_user_cannot_top_up_card_without_enough_usd(): void
    {
        $this->seed(DatabaseSeeder::class);

        $user = $this->approvedUser(usdBalanceMinor: 500);
        $this->creditUsdWallet($user, 500);

        $card = $user->virtualCards()->create([
            'provider' => 'sandbox',
            'provider_card_id' => 'sandbox_card_123',
            'masked_pan' => '424242******1234',
            'brand' => 'Visa',
            'currency' => 'USD',
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->post(route('wallet.cards.top-up', $card), ['amount' => '20.00'])
            ->assertSessionHasErrors('amount');

        $this->assertDatabaseMissing('card_transactions', [
            'virtual_card_id' => $card->id,
            'type' => 'top_up',
        ]);
    }

    public function test_user_cannot_top_up_frozen_card(): void
    {
        $this->seed(DatabaseSeeder::class);

        $user = $this->approvedUser(usdBalanceMinor: 5000);
        $this->creditUsdWallet($user, 5000);

        $card = $user->virtualCards()->create([
            'provider' => 'sandbox',
            'provider_card_id' => 'sandbox_card_123',
            'masked_pan' => '424242******1234',
            'brand' => 'Visa',
            'currency' => 'USD',
            'status' => 'inactive',
        ]);

        $this->actingAs($user)
            ->post(route('wallet.cards.top-up', $card), ['amount' => '20.00'])
            ->assertSessionHasErrors('amount');

        $this->assertDatabaseMissing('card_transactions', [
            'virtual_card_id' => $card->id,
            'type' => 'top_up',
        ]);
    }

    private function approvedUser(int $usdBalanceMinor): User
    {
        $user = User::factory()->create([
            'name' => 'Test User',
            'role' => 'customer',
            'status' => 'active',
        ]);

        UserProfile::query()->create([
            'user_id' => $user->id,
            'phone' => '+265999000111',
            'country' => 'MW',
            'status' => 'registered',
        ]);

        KycProfile::query()->create([
            'user_id' => $user->id,
            'status' => KycProfileStatus::APPROVED->value,
            'tier' => 'basic',
            'risk_rating' => 'standard',
            'address' => 'Area 3',
            'city_district' => 'Lilongwe',
        ]);

        Wallet::query()->create([
            'user_id' => $user->id,
            'currency' => 'MWK',
            'type' => 'main',
            'status' => 'active',
            'cached_balance_minor' => 0,
        ]);

        Wallet::query()->create([
            'user_id' => $user->id,
            'currency' => 'USD',
            'type' => 'main',
            'status' => 'active',
            'cached_balance_minor' => $usdBalanceMinor,
        ]);

        return $user;
    }

    private function creditUsdWallet(User $user, int $amountMinor): void
    {
        app(WalletService::class)->ensureUserWallet($user, 'USD');

        app(LedgerService::class)->post('test_usd_credit', "test_usd_credit:{$user->id}:{$amountMinor}", [
            [
                'account_code' => 'SYS:FX_POSITION:USD',
                'direction' => 'debit',
                'currency' => 'USD',
                'amount_minor' => $amountMinor,
            ],
            [
                'account_code' => "USER:{$user->id}:USD:main",
                'direction' => 'credit',
                'currency' => 'USD',
                'amount_minor' => $amountMinor,
            ],
        ]);
    }
}
