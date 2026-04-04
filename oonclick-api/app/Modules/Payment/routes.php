<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Payment\Controllers\WalletController;
use App\Modules\Payment\Controllers\PaystackWebhookController;

Route::prefix('api')->middleware('api')->group(function () {

    // Routes protégées — authentification requise
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/wallet',                                 [WalletController::class, 'show']);
        Route::get('/wallet/transactions',                    [WalletController::class, 'transactions']);
        Route::get('/wallet/withdrawals',                     [WalletController::class, 'withdrawals']);
        Route::post('/wallet/withdraw',                       [WalletController::class, 'withdraw'])->middleware('throttle:5,1');
        Route::post('/wallet/withdrawals/{id}/cancel',        [WalletController::class, 'cancelWithdrawal']); // US-041
    });

    // Webhook Paystack — public mais protégé par vérification de signature HMAC
    Route::post('/paystack/webhook', [PaystackWebhookController::class, 'handle'])
        ->middleware('paystack.webhook');
});
