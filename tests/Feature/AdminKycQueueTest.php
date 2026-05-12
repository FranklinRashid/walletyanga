<?php

namespace Tests\Feature;

use App\Enums\KycProfileStatus;
use App\Models\KycProfile;
use App\Models\User;
use App\Support\UserRoles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminKycQueueTest extends TestCase
{
    use RefreshDatabase;

    public function test_compliance_officer_sees_pending_kyc_in_queue(): void
    {
        $this->withoutVite();

        $customer = User::factory()->create([
            'role' => UserRoles::CUSTOMER,
            'status' => 'active',
        ]);

        KycProfile::query()->create([
            'user_id' => $customer->id,
            'status' => KycProfileStatus::PENDING->value,
            'tier' => 'basic',
            'risk_rating' => 'standard',
            'date_of_birth' => '1990-01-15',
            'identity_type' => 'national_id',
            'identity_number' => 'AB123',
            'address' => '1 Main St',
            'city_district' => 'Lilongwe',
            'occupation' => 'Engineer',
            'source_of_funds' => 'salary',
            'submitted_at' => now(),
        ]);

        $officer = User::factory()->create([
            'role' => UserRoles::COMPLIANCE_OFFICER,
            'status' => 'active',
        ]);

        $response = $this->actingAs($officer)->get('/admin/kyc');

        $response->assertOk();
        $response->assertSee($customer->email, false);
    }

    public function test_compliance_officer_can_approve_pending_kyc(): void
    {
        $this->withoutVite();

        $customer = User::factory()->create([
            'role' => UserRoles::CUSTOMER,
            'status' => 'active',
        ]);

        $kyc = KycProfile::query()->create([
            'user_id' => $customer->id,
            'status' => KycProfileStatus::PENDING->value,
            'tier' => 'basic',
            'risk_rating' => 'standard',
            'date_of_birth' => '1990-01-15',
            'identity_type' => 'national_id',
            'identity_number' => 'AB123',
            'address' => '1 Main St',
            'city_district' => 'Lilongwe',
            'occupation' => 'Engineer',
            'source_of_funds' => 'salary',
            'submitted_at' => now(),
        ]);

        $officer = User::factory()->create([
            'role' => UserRoles::COMPLIANCE_OFFICER,
            'status' => 'active',
        ]);

        $this->actingAs($officer)->post("/admin/kyc/{$kyc->id}/decision", [
            'decision' => 'approve',
        ])->assertRedirect(route('admin.kyc.index'));

        $kyc->refresh();
        $this->assertSame(KycProfileStatus::APPROVED->value, $kyc->status);
        $this->assertNotNull($kyc->reviewed_at);
        $this->assertSame($officer->id, $kyc->reviewed_by);
    }

    public function test_auditor_cannot_post_kyc_decision(): void
    {
        $this->withoutVite();

        $customer = User::factory()->create([
            'role' => UserRoles::CUSTOMER,
            'status' => 'active',
        ]);

        $kyc = KycProfile::query()->create([
            'user_id' => $customer->id,
            'status' => KycProfileStatus::PENDING->value,
            'tier' => 'basic',
            'risk_rating' => 'standard',
            'date_of_birth' => '1990-01-15',
            'identity_type' => 'national_id',
            'identity_number' => 'AB123',
            'address' => '1 Main St',
            'city_district' => 'Lilongwe',
            'occupation' => 'Engineer',
            'source_of_funds' => 'salary',
            'submitted_at' => now(),
        ]);

        $auditor = User::factory()->create([
            'role' => UserRoles::AUDITOR,
            'status' => 'active',
        ]);

        $this->actingAs($auditor)->post("/admin/kyc/{$kyc->id}/decision", [
            'decision' => 'approve',
        ])->assertForbidden();
    }
}
