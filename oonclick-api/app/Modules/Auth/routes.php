<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Auth\Controllers\AuthController;
use App\Modules\Auth\Controllers\KycController;

Route::prefix('api/auth')->middleware('api')->group(function () {
    // Routes publiques avec rate limiting anti-abus
    Route::middleware('throttle:10,1')->group(function () {
        Route::post('/register',   [AuthController::class, 'register'])->middleware('throttle:30,1');
        Route::post('/verify-otp', [AuthController::class, 'verifyOtp'])->middleware('throttle:10,5');
        Route::post('/resend-otp', [AuthController::class, 'resendOtp'])->middleware('throttle:3,10');
        Route::post('/login',           [AuthController::class, 'login'])->middleware('throttle:5,1');
        Route::post('/verify-firebase', [AuthController::class, 'verifyFirebase'])->middleware('throttle:10,5');
        Route::post('/google',          [AuthController::class, 'googleAuth'])->middleware('throttle:10,5');
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout',           [AuthController::class, 'logout']);
        Route::get('/me',                [AuthController::class, 'me']);
        Route::post('/complete-profile', [AuthController::class, 'completeProfile']);
        Route::patch('/profile',         [AuthController::class, 'updateProfile']);   // US-010
        Route::post('/avatar',           [AuthController::class, 'updateAvatar']);    // US-011
        Route::post('/change-phone',          [AuthController::class, 'requestPhoneChange']);  // US-012
        Route::post('/confirm-phone-change',  [AuthController::class, 'confirmPhoneChange']); // US-012
        Route::get('/export-data',            [AuthController::class, 'exportData']);
        Route::delete('/delete-account',      [AuthController::class, 'deleteAccount']);
    });
});

// ──────────────────────────────────────────────────────────────────────────────
// Routes KYC — authentification Sanctum requise
// ──────────────────────────────────────────────────────────────────────────────
Route::prefix('api/kyc')->middleware(['api', 'auth:sanctum'])->group(function () {
    Route::post('/submit',    [KycController::class, 'submit']);
    Route::get('/status',     [KycController::class, 'status']);
    Route::get('/documents',  [KycController::class, 'documents']);
});
