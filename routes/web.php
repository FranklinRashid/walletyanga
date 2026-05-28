<?php

use App\Http\Controllers\AddMoneyController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\KycQueueController;
use App\Http\Controllers\Admin\StaffUserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\FxSpotRateController;
use App\Http\Controllers\KycSubmissionController;
use App\Http\Controllers\PaychanguCallbackController;
use App\Http\Controllers\PaychanguWebhookController;
use App\Http\Controllers\VirtualCardController;
use App\Http\Controllers\WalletActivityController;
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

Route::middleware(['auth', 'active'])->group(function (): void {
    Route::get('/dashboard', WalletDashboardController::class)->name('wallet.dashboard');
    Route::get('/wallet/activity', WalletActivityController::class)->name('wallet.activity');
    Route::get('/wallet/add-money', [AddMoneyController::class, 'create'])->name('wallet.add-money');
    Route::post('/wallet/add-money', [AddMoneyController::class, 'store'])
        ->middleware('throttle:20,1')
        ->name('wallet.add-money.store');
    Route::get('/wallet/convert', [WalletConversionController::class, 'create'])->name('wallet.convert');
    Route::post('/wallet/convert/quote', [WalletConversionController::class, 'quote'])
        ->middleware('throttle:20,1')
        ->name('wallet.convert.quote');
    Route::post('/wallet/convert/quotes/{quote}/accept', [WalletConversionController::class, 'accept'])
        ->middleware('throttle:20,1')
        ->name('wallet.convert.accept');
    Route::get('/wallet/cards', [VirtualCardController::class, 'index'])->name('wallet.cards');
    Route::post('/wallet/cards', [VirtualCardController::class, 'store'])->name('wallet.cards.store');
    Route::post('/wallet/cards/{virtualCard}/reveal', [VirtualCardController::class, 'reveal'])
        ->middleware('throttle:6,1')
        ->name('wallet.cards.reveal');
    Route::post('/wallet/cards/{virtualCard}/top-up', [VirtualCardController::class, 'topUp'])
        ->middleware('throttle:20,1')
        ->name('wallet.cards.top-up');
    Route::post('/wallet/cards/{virtualCard}/freeze', [VirtualCardController::class, 'freeze'])
        ->middleware('throttle:20,1')
        ->name('wallet.cards.freeze');
    Route::post('/wallet/cards/{virtualCard}/unfreeze', [VirtualCardController::class, 'unfreeze'])
        ->middleware('throttle:20,1')
        ->name('wallet.cards.unfreeze');
    Route::get('/api/fx/usd-mwk', FxSpotRateController::class)
        ->middleware('throttle:120,1')
        ->name('api.fx.usd-mwk');
    Route::get('/kyc', [KycSubmissionController::class, 'edit'])->name('kyc.edit');
    Route::post('/kyc', [KycSubmissionController::class, 'update'])->name('kyc.update');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});

Route::middleware(['auth', 'active', 'role:super_admin,compliance_officer,operations_admin,finance_admin,support_agent,auditor'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function (): void {
        Route::get('/', AdminDashboardController::class)->name('dashboard');
        Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
        Route::get('/customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');
        Route::post('/customers/{customer}/suspend', [CustomerController::class, 'suspend'])
            ->middleware('throttle:20,1')
            ->name('customers.suspend');
        Route::post('/customers/{customer}/activate', [CustomerController::class, 'activate'])
            ->middleware('throttle:20,1')
            ->name('customers.activate');
        Route::post('/customers/{customer}/kyc/revoke', [CustomerController::class, 'revokeKyc'])
            ->middleware('throttle:20,1')
            ->name('customers.kyc.revoke');
        Route::post('/customers/{customer}/cards/{card}/freeze', [CustomerController::class, 'freezeCard'])
            ->middleware('throttle:20,1')
            ->name('customers.cards.freeze');
        Route::post('/customers/{customer}/cards/{card}/unfreeze', [CustomerController::class, 'unfreezeCard'])
            ->middleware('throttle:20,1')
            ->name('customers.cards.unfreeze');

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
Route::post('/webhooks/paychangu', PaychanguWebhookController::class)
    ->middleware('throttle:60,1')
    ->name('webhooks.paychangu');
