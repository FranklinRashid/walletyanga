<?php

namespace Tests\Feature;

use App\Models\KycProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class KycSubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_submit_kyc_details(): void
    {
        $this->withoutVite();
        Storage::fake('local');

        $user = User::factory()->create([
            'role' => 'customer',
            'status' => 'active',
        ]);

        KycProfile::query()->create([
            'user_id' => $user->id,
            'status' => 'not_started',
            'tier' => 'basic',
            'risk_rating' => 'standard',
        ]);

        $response = $this->actingAs($user)->post('/kyc', [
            'date_of_birth' => '1995-04-12',
            'identity_type' => 'national_id',
            'identity_number' => 'MW-NID-123456',
            'address' => 'Area 18, Lilongwe',
            'city_district' => 'Lilongwe',
            'occupation' => 'Software developer',
            'source_of_funds' => 'salary',
            'id_document' => UploadedFile::fake()->image('national-id.jpg'),
        ]);

        $response->assertRedirect(route('wallet.dashboard'));

        $this->assertDatabaseHas('kyc_profiles', [
            'user_id' => $user->id,
            'status' => 'pending',
            'identity_type' => 'national_id',
            'identity_number' => 'MW-NID-123456',
            'city_district' => 'Lilongwe',
            'source_of_funds' => 'salary',
        ]);

        $kyc = $user->kycProfile()->firstOrFail();

        $this->assertNotNull($kyc->submitted_at);
        $this->assertNotNull($kyc->id_document_path);
        Storage::disk('local')->assertExists($kyc->id_document_path);
    }
}
