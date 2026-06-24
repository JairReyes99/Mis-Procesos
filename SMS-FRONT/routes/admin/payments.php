<?php

use App\Http\Controllers\Admin\PaymentController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'module.perms:pagos'])->prefix('pagos')->name('payments.')->group(function () {

    Route::get('/', [PaymentController::class, 'index'])
        ->name('index');

    Route::post('/setup-intent', [PaymentController::class, 'setupIntent'])
        ->name('setup_intent');

    Route::post('/save-card', [PaymentController::class, 'saveCard'])
        ->name('save_card');

    Route::delete('/remove-card', [PaymentController::class, 'removeCard'])
        ->name('remove_card');

    Route::post('/manual-recharge', [PaymentController::class, 'manualRecharge'])
        ->name('manual_recharge');

    Route::post('/auto-recharge-config', [PaymentController::class, 'updateAutoRecharge'])
        ->name('auto_recharge_config');

    Route::post('/paypal/create-order', [PaymentController::class, 'paypalCreateOrder'])
        ->name('paypal_create_order');

    Route::post('/paypal/capture-order', [PaymentController::class, 'paypalCaptureOrder'])
        ->name('paypal_capture_order');

    Route::get('/transactions', [PaymentController::class, 'transactions'])
        ->name('transactions');
});
