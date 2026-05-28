<?php

namespace App\Http\Controllers;

use App\Domain\Wallet\WalletTimelineService;
use App\Models\DepositIntent;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class WalletDashboardController extends Controller
{
    public function __invoke(WalletTimelineService $timeline): View
    {
        $user = Auth::user();

        return view('dashboard', [
            'user' => $user->loadMissing('profile', 'kycProfile'),
            'wallets' => $user->wallets()->latest()->get(),
            'deposits' => DepositIntent::query()->where('user_id', $user->id)->latest()->limit(8)->get(),
            'cards' => $user->virtualCards()->latest()->limit(8)->get(),
            'timelineItems' => $timeline->forUser($user, 8),
        ]);
    }
}
