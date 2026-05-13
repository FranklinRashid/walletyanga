<?php

namespace Tests\Feature;

use App\Enums\KycProfileStatus;
use App\Models\KycProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AddMoneyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake(function () {
            return Http::response([
                'date' => '2026-05-12',
                'usd' => ['mwk' => 2000.0],
            ], 200);
        });
    }

    private function userWithApprovedKyc(): User
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

        return $user;
    }

    public function test_add_money_requires_approved_kyc(): void
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
            ->get('/wallet/add-money')
            ->assertRedirect(route('wallet.dashboard'));
    }

    public function test_approved_user_can_view_add_money_form(): void
    {
        $this->withoutVite();

        $user = $this->userWithApprovedKyc();

        $this->actingAs($user)
            ->get('/wallet/add-money')
            ->assertOk()
            ->assertSee('Add money', false);
    }

    public function test_fx_api_returns_rate_json(): void
    {
        $user = $this->userWithApprovedKyc();

        $response = $this->actingAs($user)->getJson('/api/fx/usd-mwk');

        $response->assertOk();
        $this->assertEqualsWithDelta(2000.0, (float) $response->json('mwk_per_usd'), 0.000001);
        $this->assertSame('currency-api', $response->json('source'));
    }

    public function test_store_creates_mwk_intent_from_usd_amount(): void
    {
        $this->withoutVite();

        $user = $this->userWithApprovedKyc();

        $response = $this->actingAs($user)->post('/wallet/add-money', [
            'input_currency' => 'USD',
            'amount' => '10',
        ]);

        $response->assertRedirect();
        $this->assertStringContainsString('sandbox/paychangu', (string) $response->headers->get('Location'));

        $this->assertDatabaseHas('deposit_intents', [
            'user_id' => $user->id,
            'currency' => 'MWK',
            'amount_minor' => 2_000_000,
        ]);
    }

    public function test_store_creates_mwk_intent_from_mwk_amount(): void
    {
        $this->withoutVite();

        $user = $this->userWithApprovedKyc();

        $response = $this->actingAs($user)->post('/wallet/add-money', [
            'input_currency' => 'MWK',
            'amount' => '50000',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('deposit_intents', [
            'user_id' => $user->id,
            'amount_minor' => 5_000_000,
        ]);
    }

    public function test_store_rejects_below_minimum_mwk(): void
    {
        $this->withoutVite();

        $user = $this->userWithApprovedKyc();

        $this->actingAs($user)->post('/wallet/add-money', [
            'input_currency' => 'MWK',
            'amount' => '50',
        ])->assertSessionHasErrors('amount');

        $this->assertDatabaseCount('deposit_intents', 0);
    }
}
