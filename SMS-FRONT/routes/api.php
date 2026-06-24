<?php

use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Api\CampaignProgressController;
use App\Http\Controllers\Api\TestSmsResultController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Webhooks del worker Python — rate limited para prevenir DoS
Route::middleware('throttle:120,1')->group(function () {
    Route::post('/campaign/{id}/progress', [CampaignProgressController::class, 'update']);
    Route::post('/sms/test/{id}/result', [TestSmsResultController::class, 'update']);
});

// Webhook Stripe — SIN auth, verificación por firma HMAC
Route::post('/webhook/stripe', [PaymentController::class, 'webhookStripe'])
    ->name('webhook.stripe');

// Webhook PayPal — SIN auth, verificación por firma PayPal
Route::post('/webhook/paypal', [PaymentController::class, 'webhookPaypal'])
    ->name('webhook.paypal');
