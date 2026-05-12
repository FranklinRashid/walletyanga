<?php

namespace App\Http\Controllers\Auth;

use App\Domain\Wallet\WalletService;
use App\Http\Controllers\Controller;
use App\Models\KycProfile;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request, WalletService $wallets): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:30', 'unique:user_profiles,phone'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);

        $user = DB::transaction(function () use ($validated, $wallets): User {
            $user = User::query()->create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
                'role' => 'customer',
                'status' => 'active',
            ]);

            UserProfile::query()->create([
                'user_id' => $user->id,
                'phone' => $validated['phone'],
                'country' => 'MW',
                'status' => 'registered',
            ]);

            KycProfile::query()->create([
                'user_id' => $user->id,
                'status' => 'not_started',
                'tier' => 'basic',
                'risk_rating' => 'standard',
            ]);

            $wallets->ensureUserWallet($user, 'MWK');
            $wallets->ensureUserWallet($user, 'USD');

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('wallet.dashboard')->with('status', 'Welcome to Wallet Yanga. Your account is ready for KYC.');
    }
}
