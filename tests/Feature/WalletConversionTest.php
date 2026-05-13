<?php

namespace Tests\Feature;

use App\Domain\Deposits\DepositService;
use App\Enums\KycProfileStatus;
use App\Models\DepositIntent;
use App\Models\FxQuote;
use App\Models\KycProfile;
use App\Models\User;
use App\Models\Wallet;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WalletConversionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake([
            '*' => Http::response([
                'date' => '2026-05-13',
                'usd' => ['mwk' => 2000.0],
            ]),
        ]);
    }

    public function test_convert_page_requires_approved_kyc(): void
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
            ->get('/wallet/convert')
            ->assertRedirect(route('wallet.dashboard'))
            ->assertSessionHas('status', 'Your KYC must be approved before you can convert MWK to USD.');
    }

    public function test_approved_user_can_see_mwk_and_usd_balances(): void
    {
        $this->withoutVite();

        $user = User::factory()->create([
            'role' => 'customer',
            'status' => 'active',
        ]);

        KycProfile::query()->create([
            'user_id' => $user->id,
            'status' => KycProfileStatus::APPROVED->value,
            'tier' => 'basic',
            'risk_rating' => 'standard',
        ]);

        Wallet::query()->create([
            'user_id' => $user->id,
            'currency' => 'MWK',
            'type' => 'main',
            'status' => 'active',
            'cached_balance_minor' => 125_000_50,
        ]);

        Wallet::query()->create([
            'user_id' => $user->id,
            'currency' => 'USD',
            'type' => 'main',
            'status' => 'active',
            'cached_balance_minor' => 12_34,
        ]);

        $this->actingAs($user)
            ->get('/wallet/convert')
            ->assertOk()
            ->assertSee('MWK Balance', false)
            ->assertSee('USD Balance', false)
            ->assertSee('125,000.50', false)
            ->assertSee('12.34', false);
    }

    public function test_approved_user_can_generate_conversion_quote(): void
    {
        $this->withoutVite();

        $user = $this->approvedUserWithWallets(250_000_00);

        $this->actingAs($user)->post('/wallet/convert/quote', [
            'mwk_amount' => '200000',
        ])->assertRedirect(route('wallet.convert'));

        $this->assertDatabaseHas('fx_quotes', [
            'user_id' => $user->id,
            'from_currency' => 'MWK',
            'to_currency' => 'USD',
            'from_amount_minor' => 200_000_00,
            'status' => 'quoted',
        ]);
    }

    public function test_quote_requires_sufficient_mwk_balance(): void
    {
        $this->withoutVite();

        $user = $this->approvedUserWithWallets(5_000_00);

        $this->actingAs($user)->post('/wallet/convert/quote', [
            'mwk_amount' => '200000',
        ])->assertSessionHasErrors('mwk_amount');

        $this->assertDatabaseCount('fx_quotes', 0);
    }

    public function test_approved_user_can_accept_conversion_quote(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->withoutVite();

        $user = $this->approvedUserWithWallets();

        $intent = DepositIntent::query()->create([
            'user_id' => $user->id,
            'provider' => 'paychangu',
            'tx_ref' => 'WY-DEP-FXTEST',
            'currency' => 'MWK',
            'amount_minor' => 200_000_00,
            'status' => 'pending',
        ]);

        app(DepositService::class)->postVerifiedDeposit($intent, ['status' => 'success']);

        $this->actingAs($user)->post('/wallet/convert/quote', [
            'mwk_amount' => '200000',
        ]);

        $quote = FxQuote::query()->where('user_id', $user->id)->firstOrFail();

        $this->actingAs($user)
            ->post(route('wallet.convert.accept', $quote))
            ->assertRedirect(route('wallet.convert'));

        $this->assertDatabaseHas('fx_quotes', [
            'id' => $quote->id,
            'status' => 'accepted',
        ]);

        $this->assertDatabaseHas('fx_conversions', [
            'user_id' => $user->id,
            'fx_quote_id' => $quote->id,
            'status' => 'posted',
        ]);

        $this->assertDatabaseHas('wallets', [
            'user_id' => $user->id,
            'currency' => 'MWK',
            'type' => 'main',
            'cached_balance_minor' => 0,
        ]);

        $this->assertDatabaseHas('wallets', [
            'user_id' => $user->id,
            'currency' => 'USD',
            'type' => 'main',
            'cached_balance_minor' => $quote->to_amount_minor,
        ]);
    }

    private function approvedUserWithWallets(int $mwkBalanceMinor = 0): User
    {
        $user = User::factory()->create([
            'role' => 'customer',
            'status' => 'active',
        ]);

        KycProfile::query()->create([
            'user_id' => $user->id,
            'status' => KycProfileStatus::APPROVED->value,
            'tier' => 'basic',
            'risk_rating' => 'standard',
        ]);

        Wallet::query()->create([
            'user_id' => $user->id,
            'currency' => 'MWK',
            'type' => 'main',
            'status' => 'active',
            'cached_balance_minor' => $mwkBalanceMinor,
        ]);

        Wallet::query()->create([
            'user_id' => $user->id,
            'currency' => 'USD',
            'type' => 'main',
            'status' => 'active',
            'cached_balance_minor' => 0,
        ]);

        return $user;
    }
}
