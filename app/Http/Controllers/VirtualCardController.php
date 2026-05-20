<?php

namespace App\Http\Controllers;

use App\Domain\Cards\VirtualCardService;
use App\Domain\Wallet\WalletService;
use App\Enums\KycProfileStatus;
use App\Models\VirtualCard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class VirtualCardController extends Controller
{
    public function __construct(
        private readonly WalletService $wallets,
        private readonly VirtualCardService $cards,
    ) {
    }

    public function index(): View|RedirectResponse
    {
        $user = Auth::user()->loadMissing('profile', 'kycProfile');

        if ($user->kycProfile?->status !== KycProfileStatus::APPROVED->value) {
            return redirect()
                ->route('wallet.dashboard')
                ->with('status', 'Your KYC must be approved before you can create a virtual card.');
        }

        $mwkWallet = $this->wallets->ensureUserWallet($user, 'MWK');
        $usdWallet = $this->wallets->ensureUserWallet($user, 'USD');

        return view('wallet.cards', [
            'user' => $user,
            'mwkWallet' => $mwkWallet,
            'usdWallet' => $usdWallet,
            'cards' => $user->virtualCards()->latest()->get(),
            'issuer' => config('services.cards.issuer', 'sandbox'),
            'revealedCard' => null,
            'revealedDetails' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user()->loadMissing('profile', 'kycProfile');

        if ($user->kycProfile?->status !== KycProfileStatus::APPROVED->value) {
            return redirect()
                ->route('wallet.dashboard')
                ->with('status', 'Your KYC must be approved before you can create a virtual card.');
        }

        $validated = $request->validate([
            'nickname' => ['nullable', 'string', 'max:60'],
        ]);

        $usdWallet = $this->wallets->ensureUserWallet($user, 'USD');

        if ($usdWallet->cached_balance_minor <= 0) {
            return back()
                ->withErrors(['card' => 'Convert MWK to USD first. Virtual cards spend from your USD wallet.'])
                ->withInput();
        }

        if (in_array(config('services.cards.issuer'), ['sudo', 'sudo_africa'], true) && ! $user->profile?->phone) {
            return back()
                ->withErrors(['card' => 'Add a verified phone number before issuing a Sudo Africa virtual card.'])
                ->withInput();
        }

        try {
            $card = $this->cards->createCard($user, $validated['nickname'] ?? null);
        } catch (Throwable $exception) {
            Log::warning('Virtual card creation failed.', [
                'user_id' => $user->id,
                'provider' => config('services.cards.issuer'),
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            return back()
                ->withErrors(['card' => $exception->getMessage()])
                ->withInput();
        }

        return redirect()
            ->route('wallet.cards')
            ->with('status', "Virtual card {$card->masked_pan} has been created.");
    }

    public function reveal(Request $request, int $virtualCard): View|RedirectResponse
    {
        $user = $request->user()->loadMissing('profile', 'kycProfile');

        if ($user->kycProfile?->status !== KycProfileStatus::APPROVED->value) {
            return redirect()
                ->route('wallet.dashboard')
                ->with('status', 'Your KYC must be approved before you can view virtual card details.');
        }

        $card = $user->virtualCards()->whereKey($virtualCard)->firstOrFail();

        $validated = $request->validate([
            'password' => ['required', 'string'],
        ]);

        if (! Hash::check($validated['password'], $user->password)) {
            $this->recordRevealEvent($request, $card, false);

            return back()->withErrors(['password' => 'The password is incorrect.']);
        }

        try {
            $details = $this->cards->revealDetails($card);
            $this->recordRevealEvent($request, $card, true);
        } catch (Throwable $exception) {
            $this->recordRevealEvent($request, $card, false);

            Log::warning('Virtual card detail reveal failed.', [
                'user_id' => $user->id,
                'virtual_card_id' => $card->id,
                'provider' => $card->provider,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            return back()->withErrors(['card' => $exception->getMessage()]);
        }

        $mwkWallet = $this->wallets->ensureUserWallet($user, 'MWK');
        $usdWallet = $this->wallets->ensureUserWallet($user, 'USD');

        return view('wallet.cards', [
            'user' => $user,
            'mwkWallet' => $mwkWallet,
            'usdWallet' => $usdWallet,
            'cards' => $user->virtualCards()->latest()->get(),
            'issuer' => config('services.cards.issuer', 'sandbox'),
            'revealedCard' => $card,
            'revealedDetails' => $details,
        ])->with('status', 'Card details are shown once. They are not saved by Wallet Yanga.');
    }

    private function recordRevealEvent(Request $request, VirtualCard $card, bool $successful): void
    {
        DB::table('card_detail_reveal_events')->insert([
            'user_id' => $request->user()->id,
            'virtual_card_id' => $card->id,
            'provider' => $card->provider,
            'successful' => $successful,
            'ip_address' => $request->ip(),
            'user_agent_hash' => $request->userAgent() ? hash('sha256', Str::limit($request->userAgent(), 500, '')) : null,
            'revealed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
