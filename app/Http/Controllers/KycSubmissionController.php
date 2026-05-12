<?php

namespace App\Http\Controllers;

use App\Enums\KycProfileStatus;
use App\Models\KycProfile;
use App\Support\KycFieldOptions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class KycSubmissionController extends Controller
{
    public function edit(Request $request): View
    {
        $kyc = $request->user()->kycProfile()->firstOrCreate([
            'user_id' => $request->user()->id,
        ], [
            'status' => KycProfileStatus::NOT_STARTED->value,
            'tier' => 'basic',
            'risk_rating' => 'standard',
        ]);

        return view('kyc.edit', [
            'user' => $request->user(),
            'kyc' => $kyc,
            'identityTypes' => KycFieldOptions::identityTypes(),
            'sourcesOfFunds' => KycFieldOptions::sourcesOfFunds(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'date_of_birth' => ['required', 'date', 'before:-18 years'],
            'identity_type' => ['required', Rule::in(array_keys(KycFieldOptions::identityTypes()))],
            'identity_number' => ['required', 'string', 'max:80'],
            'address' => ['required', 'string', 'max:255'],
            'city_district' => ['required', 'string', 'max:120'],
            'occupation' => ['required', 'string', 'max:120'],
            'source_of_funds' => ['required', Rule::in(array_keys(KycFieldOptions::sourcesOfFunds()))],
            'id_document' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);

        $kyc = $request->user()->kycProfile()->firstOrCreate([
            'user_id' => $request->user()->id,
        ], [
            'tier' => 'basic',
            'risk_rating' => 'standard',
        ]);

        if ($request->hasFile('id_document')) {
            $validated['id_document_path'] = $request->file('id_document')->store('kyc-documents', 'local');
        }

        unset($validated['id_document']);

        $kyc->fill($validated);
        $kyc->forceFill([
            'status' => KycProfileStatus::PENDING->value,
            'submitted_at' => now(),
            'reviewed_at' => null,
            'reviewed_by' => null,
            'review_notes' => null,
        ])->save();

        return redirect()->route('wallet.dashboard')->with('status', 'KYC submitted for compliance review.');
    }
}
