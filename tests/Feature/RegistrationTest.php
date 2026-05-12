<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_register_and_get_wallets(): void
    {
        $this->withoutVite();

        $response = $this->post('/register', [
            'name' => 'Aisha Banda',
            'email' => 'aisha@example.com',
            'phone' => '+265991234567',
            'password' => 'Wallet123',
            'password_confirmation' => 'Wallet123',
        ]);

        $response->assertRedirect(route('wallet.dashboard'));
        $this->assertAuthenticated();

        $user = User::query()->where('email', 'aisha@example.com')->firstOrFail();

        $this->assertDatabaseHas('user_profiles', [
            'user_id' => $user->id,
            'phone' => '+265991234567',
            'country' => 'MW',
            'status' => 'registered',
        ]);

        $this->assertDatabaseHas('kyc_profiles', [
            'user_id' => $user->id,
            'status' => 'not_started',
            'tier' => 'basic',
        ]);

        $this->assertDatabaseHas('wallets', [
            'user_id' => $user->id,
            'currency' => 'MWK',
            'type' => 'main',
        ]);

        $this->assertDatabaseHas('wallets', [
            'user_id' => $user->id,
            'currency' => 'USD',
            'type' => 'main',
        ]);
    }
}
