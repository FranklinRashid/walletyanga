<?php

namespace Tests\Feature;

use App\Models\DepositIntent;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PaychanguCallbackTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake([
            'api.paychangu.com/verify-payment/*' => Http::response([
                'status' => 'success',
                'transaction_id' => 'txn-test-1',
            ], 200),
        ]);
    }

    public function test_browser_callback_credits_wallet_and_redirects(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->withoutVite();

        $user = User::factory()->create([
            'role' => 'customer',
            'status' => 'active',
        ]);

        $intent = DepositIntent::query()->create([
            'user_id' => $user->id,
            'provider' => 'paychangu',
            'tx_ref' => 'WY-DEP-CALLBACKTEST',
            'currency' => 'MWK',
            'amount_minor' => 50_000,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user)->get('/payments/paychangu/callback?tx_ref=WY-DEP-CALLBACKTEST');

        $response->assertRedirect(route('wallet.dashboard'));
        $intent->refresh();
        $this->assertSame('success', $intent->status);
    }

    public function test_legacy_webhook_get_redirects_to_callback(): void
    {
        $this->withoutVite();

        $response = $this->get('/webhooks/paychangu?tx_ref=WY-DEP-LEGACY');

        $response->assertRedirect(route('paychangu.callback', ['tx_ref' => 'WY-DEP-LEGACY']));
    }
}
