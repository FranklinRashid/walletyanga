<?php

namespace App\Http\Controllers\Admin;

use App\Enums\KycProfileStatus;
use App\Http\Controllers\Controller;
use App\Models\AdminAction;
use App\Models\KycProfile;
use App\Support\KycFieldOptions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class KycQueueController extends Controller
{
    public function index(): View
    {
        $pending = KycProfile::query()
            ->where('status', KycProfileStatus::PENDING->value)
            ->whereNotNull('submitted_at')
            ->with(['user' => fn ($q) => $q->select('id', 'name', 'email')])
            ->latest('submitted_at')
            ->get();

        return view('admin.kyc.index', [
            'pendingProfiles' => $pending,
        ]);
    }

    public function show(KycProfile $kyc_profile): View
    {
        $kyc_profile->load([
            'user:id,name,email',
            'reviewedBy:id,name,email',
        ]);

        return view('admin.kyc.show', [
            'kyc' => $kyc_profile,
            'identityTypes' => KycFieldOptions::identityTypes(),
            'sourcesOfFunds' => KycFieldOptions::sourcesOfFunds(),
            'canDecide' => in_array(auth()->user()->role, ['super_admin', 'compliance_officer'], true),
        ]);
    }

    public function document(KycProfile $kyc_profile): StreamedResponse
    {
        if (! $kyc_profile->id_document_path || ! Storage::disk('local')->exists($kyc_profile->id_document_path)) {
            abort(404);
        }

        return Storage::disk('local')->download($kyc_profile->id_document_path);
    }

    public function decide(Request $request, KycProfile $kyc_profile): RedirectResponse
    {
        abort_unless($kyc_profile->status === KycProfileStatus::PENDING->value, 404);

        $validated = $request->validate([
            'decision' => ['required', Rule::in(['approve', 'reject'])],
            'review_notes' => ['nullable', 'string', 'max:2000', 'required_if:decision,reject'],
        ]);

        $approved = $validated['decision'] === 'approve';
        $afterStatus = $approved ? KycProfileStatus::APPROVED : KycProfileStatus::REJECTED;
        $reviewNotes = $validated['review_notes'] ?? null;

        $kyc_profile->forceFill([
            'status' => $afterStatus->value,
            'reviewed_at' => now(),
            'reviewed_by' => $request->user()->id,
            'review_notes' => $reviewNotes,
        ])->save();

        AdminAction::query()->create([
            'admin_user_id' => $request->user()->id,
            'action' => $approved ? 'kyc.approved' : 'kyc.rejected',
            'subject_type' => KycProfile::class,
            'subject_id' => $kyc_profile->id,
            'after' => [
                'user_id' => $kyc_profile->user_id,
                'status' => $afterStatus->value,
            ],
            'reason' => $reviewNotes,
            'ip_address' => $request->ip(),
        ]);

        $message = $approved ? 'KYC approved.' : 'KYC rejected.';

        return redirect()->route('admin.kyc.index')->with('status', $message);
    }
}
