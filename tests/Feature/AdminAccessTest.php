<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\UserRoles;
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
}
