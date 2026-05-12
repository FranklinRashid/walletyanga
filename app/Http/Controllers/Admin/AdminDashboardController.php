<?php

namespace App\Http\Controllers\Admin;

use App\Enums\KycProfileStatus;
use App\Http\Controllers\Controller;
use App\Models\DepositIntent;
use App\Models\LedgerTransaction;
use App\Models\KycProfile;
use App\Models\User;
use App\Models\VirtualCard;
use App\Models\Wallet;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.dashboard', [
            'usersCount' => User::query()->count(),
            'staffCount' => User::query()->where('role', '!=', 'customer')->count(),
            'pendingKycCount' => KycProfile::query()
                ->where('status', KycProfileStatus::PENDING->value)
                ->whereNotNull('submitted_at')
                ->count(),
            'walletsCount' => Wallet::query()->count(),
            'depositsCount' => DepositIntent::query()->count(),
            'cardsCount' => VirtualCard::query()->count(),
            'ledgerTransactions' => LedgerTransaction::query()->latest()->limit(8)->get(),
        ]);
    }
}
