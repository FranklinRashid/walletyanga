<?php

namespace App\Http\Controllers;

use App\Models\DepositIntent;
use App\Models\LedgerTransaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class WalletDashboardController extends Controller
{
    public function __invoke(): View
    {
        $user = Auth::user();

        return view('dashboard', [
            'user' => $user->loadMissing('profile', 'kycProfile'),
            'wallets' => $user->wallets()->latest()->get(),
            'deposits' => DepositIntent::query()->where('user_id', $user->id)->latest()->limit(8)->get(),
            'cards' => $user->virtualCards()->latest()->limit(8)->get(),
            'ledgerTransactions' => LedgerTransaction::query()
                ->whereHas('entries.account', fn ($query) => $query->where('user_id', $user->id))
                ->latest()
                ->limit(8)
                ->get(),
        ]);
    }
}
