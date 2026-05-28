<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Cards\VirtualCardService;
use App\Domain\Wallet\WalletTimelineService;
use App\Enums\KycProfileStatus;
use App\Http\Controllers\Controller;
use App\Models\AdminAction;
use App\Models\KycProfile;
use App\Models\User;
use App\Models\VirtualCard;
use App\Support\UserRoles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q'));

        $customers = User::query()
            ->where('role', UserRoles::CUSTOMER)
            ->with('kycProfile')
            ->withCount(['wallets', 'virtualCards'])
            ->when($search !== '', fn ($query) => $query
                ->where(fn ($query) => $query
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.customers.index', [
            'customers' => $customers,
            'search' => $search,
        ]);
    }

    public function show(User $customer, WalletTimelineService $timeline, VirtualCardService $cards): View
    {
        abort_unless($customer->role === UserRoles::CUSTOMER, 404);

        $customer->load([
            'profile',
            'kycProfile',
            'wallets',
            'virtualCards.transactions',
        ]);

        return view('admin.customers.show', [
            'customer' => $customer,
            'cardBalances' => $customer->virtualCards
                ->mapWithKeys(fn (VirtualCard $card): array => [$card->id => $cards->cardBalanceMinor($card)])
                ->all(),
            'timelineItems' => $timeline->forUser($customer, 20),
            'canManageCards' => in_array(auth()->user()->role, [
                UserRoles::SUPER_ADMIN,
                UserRoles::OPERATIONS_ADMIN,
                UserRoles::SUPPORT_AGENT,
            ], true),
            'canRevokeKyc' => in_array(auth()->user()->role, [
                UserRoles::SUPER_ADMIN,
                UserRoles::COMPLIANCE_OFFICER,
            ], true),
        ]);
    }

    public function revokeKyc(Request $request, User $customer): RedirectResponse
    {
        abort_unless($customer->role === UserRoles::CUSTOMER, 404);
        $this->assertCanRevokeKyc($request);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $kyc = $customer->kycProfile()->firstOrFail();
        abort_unless($kyc->status === KycProfileStatus::APPROVED->value, 404);

        $before = [
            'status' => $kyc->status,
            'tier' => $kyc->tier,
            'risk_rating' => $kyc->risk_rating,
            'reviewed_by' => $kyc->reviewed_by,
            'reviewed_at' => optional($kyc->reviewed_at)->toISOString(),
        ];

        $kyc->forceFill([
            'status' => KycProfileStatus::REJECTED->value,
            'reviewed_at' => now(),
            'reviewed_by' => $request->user()->id,
            'review_notes' => $validated['reason'],
        ])->save();

        AdminAction::query()->create([
            'admin_user_id' => $request->user()->id,
            'action' => 'kyc.revoked',
            'subject_type' => KycProfile::class,
            'subject_id' => $kyc->id,
            'before' => $before,
            'after' => [
                'user_id' => $customer->id,
                'status' => $kyc->status,
                'reviewed_by' => $kyc->reviewed_by,
                'reviewed_at' => optional($kyc->reviewed_at)->toISOString(),
            ],
            'reason' => $validated['reason'],
            'ip_address' => $request->ip(),
        ]);

        return redirect()
            ->route('admin.customers.show', $customer)
            ->with('status', 'Customer KYC approval revoked.');
    }

    public function freezeCard(Request $request, User $customer, VirtualCard $card, VirtualCardService $cards): RedirectResponse
    {
        $this->assertOwnsCard($customer, $card);
        $this->assertCanManageCards($request);

        try {
            $before = $card->status;
            $cards->freeze($card);
            $this->recordCardAction($request, 'card.frozen', $card, $before, $card->fresh()->status);
        } catch (Throwable $exception) {
            return back()->withErrors(['card' => $exception->getMessage()]);
        }

        return redirect()
            ->route('admin.customers.show', $customer)
            ->with('status', 'Card frozen.');
    }

    public function unfreezeCard(Request $request, User $customer, VirtualCard $card, VirtualCardService $cards): RedirectResponse
    {
        $this->assertOwnsCard($customer, $card);
        $this->assertCanManageCards($request);

        try {
            $before = $card->status;
            $cards->unfreeze($card);
            $this->recordCardAction($request, 'card.unfrozen', $card, $before, $card->fresh()->status);
        } catch (Throwable $exception) {
            return back()->withErrors(['card' => $exception->getMessage()]);
        }

        return redirect()
            ->route('admin.customers.show', $customer)
            ->with('status', 'Card reactivated.');
    }

    private function assertOwnsCard(User $customer, VirtualCard $card): void
    {
        abort_unless($customer->role === UserRoles::CUSTOMER && (int) $card->user_id === (int) $customer->id, 404);
    }

    private function assertCanManageCards(Request $request): void
    {
        abort_unless(in_array($request->user()->role, [
            UserRoles::SUPER_ADMIN,
            UserRoles::OPERATIONS_ADMIN,
            UserRoles::SUPPORT_AGENT,
        ], true), 403);
    }

    private function assertCanRevokeKyc(Request $request): void
    {
        abort_unless(in_array($request->user()->role, [
            UserRoles::SUPER_ADMIN,
            UserRoles::COMPLIANCE_OFFICER,
        ], true), 403);
    }

    private function recordCardAction(Request $request, string $action, VirtualCard $card, string $before, string $after): void
    {
        AdminAction::query()->create([
            'admin_user_id' => $request->user()->id,
            'action' => $action,
            'subject_type' => VirtualCard::class,
            'subject_id' => $card->id,
            'before' => ['status' => $before],
            'after' => ['status' => $after],
            'ip_address' => $request->ip(),
        ]);
    }
}
