<?php

namespace App\Http\Controllers;

use App\Domain\Wallet\WalletTimelineService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WalletActivityController extends Controller
{
    public function __invoke(Request $request, WalletTimelineService $timeline): View
    {
        return view('wallet.activity', [
            'user' => $request->user(),
            'timelineItems' => $timeline->forUser($request->user(), 50),
        ]);
    }
}
