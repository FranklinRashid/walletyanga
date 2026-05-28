<?php

namespace Tests\Feature;

use App\Domain\Cards\VirtualCardService;
use App\Domain\Ledger\LedgerService;
use App\Domain\Wallet\WalletService;
use App\Enums\KycProfileStatus;
use App\Models\KycProfile;
use App\Models\User;
use App\Models\UserProfile;
use App\Support\UserRoles;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_cannot_access_admin_dashboard(): void
    {
        $this->withoutVite();

        $customer = User::factory()->create([
            'role' => UserRoles::CUSTOMER,
            'status' => 'active',
        ]);

        $this->actingAs($customer)
            ->get('/admin')
            ->assertForbidden();
    }

    public function test_super_admin_can_create_compliance_officer(): void
    {
        $this->withoutVite();

        $admin = User::factory()->create([
            'role' => UserRoles::SUPER_ADMIN,
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->post('/admin/staff', [
            'name' => 'Grace Phiri',
            'email' => 'grace@example.com',
            'role' => UserRoles::COMPLIANCE_OFFICER,
            'password' => 'Wallet123',
            'password_confirmation' => 'Wallet123',
        ]);

        $response->assertRedirect(route('admin.staff.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'grace@example.com',
            'role' => UserRoles::COMPLIANCE_OFFICER,
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('admin_actions', [
            'admin_user_id' => $admin->id,
            'action' => 'staff_user.created',
        ]);
    }

    public function test_support_agent_can_view_customer_profile_with_wallet_cards_and_activity(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->withoutVite();

        $admin = User::factory()->create([
            'role' => UserRoles::SUPPORT_AGENT,
            'status' => 'active',
        ]);

        $customer = $this->customerWithFundedCard();

        $this->actingAs($admin)
            ->get(route('admin.customers.index'))
            ->assertOk()
            ->assertSee($customer->name, false)
            ->assertSee($customer->email, false);

        $this->actingAs($admin)
            ->get(route('admin.customers.show', $customer))
            ->assertOk()
            ->assertSee($customer->email, false)
            ->assertSee('Wallets', false)
            ->assertSee('Virtual cards', false)
            ->assertSee('Card topped up', false)
            ->assertSee('10.00', false);
    }

    public function test_operations_admin_can_freeze_customer_card(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->withoutVite();

        $admin = User::factory()->create([
            'role' => UserRoles::OPERATIONS_ADMIN,
            'status' => 'active',
        ]);

        $customer = $this->customerWithFundedCard();
        $card = $customer->virtualCards()->firstOrFail();

        $this->actingAs($admin)
            ->post(route('admin.customers.cards.freeze', [$customer, $card]))
            ->assertRedirect(route('admin.customers.show', $customer))
            ->assertSessionHas('status', 'Card frozen.');

        $this->assertDatabaseHas('virtual_cards', [
            'id' => $card->id,
            'status' => 'inactive',
        ]);

        $this->assertDatabaseHas('admin_actions', [
            'admin_user_id' => $admin->id,
            'action' => 'card.frozen',
            'subject_id' => $card->id,
        ]);
    }

    public function test_auditor_can_view_but_cannot_freeze_customer_card(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->withoutVite();

        $admin = User::factory()->create([
            'role' => UserRoles::AUDITOR,
            'status' => 'active',
        ]);

        $customer = $this->customerWithFundedCard();
        $card = $customer->virtualCards()->firstOrFail();

        $this->actingAs($admin)
            ->get(route('admin.customers.show', $customer))
            ->assertOk();

        $this->actingAs($admin)
            ->post(route('admin.customers.cards.freeze', [$customer, $card]))
            ->assertForbidden();

        $this->assertDatabaseHas('virtual_cards', [
            'id' => $card->id,
            'status' => 'active',
        ]);
    }

    public function test_compliance_officer_can_revoke_customer_kyc(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->withoutVite();

        $admin = User::factory()->create([
            'role' => UserRoles::COMPLIANCE_OFFICER,
            'status' => 'active',
        ]);

        $customer = $this->customerWithFundedCard();
        $kyc = $customer->kycProfile()->firstOrFail();

        $this->actingAs($admin)
            ->get(route('admin.customers.show', $customer))
            ->assertOk()
            ->assertSee('Review KYC', false)
            ->assertSee('Revoke KYC', false);

        $this->actingAs($admin)
            ->post(route('admin.customers.kyc.revoke', $customer), [
                'reason' => 'Document verification failed after manual review.',
            ])
            ->assertRedirect(route('admin.customers.show', $customer))
            ->assertSessionHas('status', 'Customer KYC approval revoked.');

        $this->assertDatabaseHas('kyc_profiles', [
            'id' => $kyc->id,
            'status' => KycProfileStatus::REJECTED->value,
            'reviewed_by' => $admin->id,
            'review_notes' => 'Document verification failed after manual review.',
        ]);

        $this->assertDatabaseHas('admin_actions', [
            'admin_user_id' => $admin->id,
            'action' => 'kyc.revoked',
            'subject_id' => $kyc->id,
            'reason' => 'Document verification failed after manual review.',
        ]);
    }

    public function test_support_agent_cannot_revoke_customer_kyc(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->withoutVite();

        $admin = User::factory()->create([
            'role' => UserRoles::SUPPORT_AGENT,
            'status' => 'active',
        ]);

        $customer = $this->customerWithFundedCard();
        $kyc = $customer->kycProfile()->firstOrFail();

        $this->actingAs($admin)
            ->post(route('admin.customers.kyc.revoke', $customer), [
                'reason' => 'Trying to revoke from support.',
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('kyc_profiles', [
            'id' => $kyc->id,
            'status' => KycProfileStatus::APPROVED->value,
        ]);
    }

    private function customerWithFundedCard(): User
    {
        $customer = User::factory()->create([
            'name' => 'Tadala Banda',
            'email' => 'tadala@example.com',
            'role' => UserRoles::CUSTOMER,
            'status' => 'active',
        ]);

        UserProfile::query()->create([
            'user_id' => $customer->id,
            'phone' => '+265999000111',
            'country' => 'MW',
            'status' => 'registered',
        ]);

        KycProfile::query()->create([
            'user_id' => $customer->id,
            'status' => KycProfileStatus::APPROVED->value,
            'tier' => 'basic',
            'risk_rating' => 'standard',
        ]);

        app(WalletService::class)->ensureUserWallet($customer, 'USD');
        app(LedgerService::class)->post('test_usd_credit', "admin_test_usd_credit:{$customer->id}", [
            [
                'account_code' => 'SYS:FX_POSITION:USD',
                'direction' => 'debit',
                'currency' => 'USD',
                'amount_minor' => 25_00,
            ],
            [
                'account_code' => "USER:{$customer->id}:USD:main",
                'direction' => 'credit',
                'currency' => 'USD',
                'amount_minor' => 25_00,
            ],
        ]);

        $card = app(VirtualCardService::class)->createCard($customer, 'Support test');
        app(VirtualCardService::class)->topUp($card, 10_00);

        return $customer;
    }
}
