<?php

use App\Http\Controllers\AddMoneyController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\KycQueueController;
use App\Http\Controllers\Admin\StaffUserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\FxSpotRateController;
use App\Http\Controllers\KycSubmissionController;
use App\Http\Controllers\PaychanguCallbackController;
use App\Http\Controllers\PaychanguWebhookController;
use App\Http\Controllers\WalletConversionController;
use App\Http\Controllers\WalletDashboardController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('guest')->group(function (): void {
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store']);
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
});

Route::middleware('auth')->group(function (): void {
    Route::get('/dashboard', WalletDashboardController::class)->name('wallet.dashboard');
    Route::get('/wallet/add-money', [AddMoneyController::class, 'create'])->name('wallet.add-money');
    Route::post('/wallet/add-money', [AddMoneyController::class, 'store'])->name('wallet.add-money.store');
    Route::get('/wallet/convert', [WalletConversionController::class, 'create'])->name('wallet.convert');
    Route::post('/wallet/convert/quote', [WalletConversionController::class, 'quote'])->name('wallet.convert.quote');
    Route::post('/wallet/convert/quotes/{quote}/accept', [WalletConversionController::class, 'accept'])->name('wallet.convert.accept');
    Route::get('/api/fx/usd-mwk', FxSpotRateController::class)
        ->middleware('throttle:120,1')
        ->name('api.fx.usd-mwk');
    Route::get('/kyc', [KycSubmissionController::class, 'edit'])->name('kyc.edit');
    Route::post('/kyc', [KycSubmissionController::class, 'update'])->name('kyc.update');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});

Route::middleware(['auth', 'role:super_admin,compliance_officer,operations_admin,finance_admin,support_agent,auditor'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function (): void {
        Route::get('/', AdminDashboardController::class)->name('dashboard');

        Route::get('/kyc', [KycQueueController::class, 'index'])->name('kyc.index');
        Route::get('/kyc/{kyc_profile}', [KycQueueController::class, 'show'])->name('kyc.show');
        Route::get('/kyc/{kyc_profile}/document', [KycQueueController::class, 'document'])->name('kyc.document');
        Route::post('/kyc/{kyc_profile}/decision', [KycQueueController::class, 'decide'])
            ->middleware('role:super_admin,compliance_officer')
            ->name('kyc.decide');

        Route::middleware('role:super_admin')->group(function (): void {
            Route::get('/staff', [StaffUserController::class, 'index'])->name('staff.index');
            Route::get('/staff/create', [StaffUserController::class, 'create'])->name('staff.create');
            Route::post('/staff', [StaffUserController::class, 'store'])->name('staff.store');
        });
    });

Route::get('/payments/paychangu/callback', PaychanguCallbackController::class)->name('paychangu.callback');
/** @deprecated Browser returns used this path before paychangu.callback existed; keeps old Paychangu redirect URLs working */
Route::get('/webhooks/paychangu', function (Request $request) {
    return redirect()->route('paychangu.callback', $request->query());
});
Route::post('/webhooks/paychangu', PaychanguWebhookController::class)->name('webhooks.paychangu');
