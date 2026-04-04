<?php

use App\Http\Controllers\AppController;
use App\Http\Controllers\ConfigController;
use App\Http\Controllers\ConsentController;
use App\Http\Controllers\CouponController;
use App\Http\Controllers\DailyCheckinController;
use App\Http\Controllers\FcmTokenController;
use App\Http\Controllers\GamificationController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\MissionController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OfferController;
use App\Http\Controllers\OfflineFeedController;
use App\Http\Controllers\ReferralController;
use App\Http\Controllers\SurveyController;
use Illuminate\Support\Facades\Route;

// Routes publiques (sans authentification) avec rate limiting
Route::middleware(['api', 'throttle:30,1'])->group(function () {
    Route::get('/app/version', [AppController::class, 'version']);
    Route::post('/app/register-install', [AppController::class, 'registerInstall']);

    // Configuration dynamique (formats de campagne, critères d'audience et fonctionnalités)
    Route::get('/config/campaign-formats', [ConfigController::class, 'campaignFormats']);
    Route::get('/config/audience-criteria', [ConfigController::class, 'audienceCriteria']);
    Route::get('/config/features', [ConfigController::class, 'features']);
});

// Health check (US-068) — public, sans authentification
Route::get('/health', function () {
    $checks = [
        'app'      => true,
        'database' => true,
        'cache'    => true,
        'queue'    => true,
        'storage'  => true,
    ];

    try { \DB::connection()->getPdo(); } catch (\Exception $e) { $checks['database'] = false; }
    try { \Cache::store()->put('health_check', true, 10); } catch (\Exception $e) { $checks['cache'] = false; }
    try { \Storage::disk('local')->exists('.gitignore'); } catch (\Exception $e) { $checks['storage'] = false; }

    $healthy = ! in_array(false, $checks);

    return response()->json(
        ['status' => $healthy ? 'healthy' : 'degraded', 'checks' => $checks],
        $healthy ? 200 : 503
    );
});

// Routes protégées (auth Sanctum)
Route::middleware(['api', 'auth:sanctum'])->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::patch('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
    Route::patch('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);

    Route::post('/app/audit-event', [AppController::class, 'auditEvent']);

    // Check-in quotidien (US-049)
    Route::post('/checkin',          [DailyCheckinController::class, 'checkin']);
    Route::get('/checkin/status',    [DailyCheckinController::class, 'status']);
    Route::get('/checkin/calendar',  [DailyCheckinController::class, 'calendar']);

    // Firebase Cloud Messaging — gestion des tokens (US-053)
    Route::post('/fcm/register',   [FcmTokenController::class, 'register']);
    Route::post('/fcm/unregister', [FcmTokenController::class, 'unregister']);

    // Gamification — niveaux & badges (US-050)
    Route::prefix('gamification')->group(function () {
        Route::get('/profile',     [GamificationController::class, 'profile']);
        Route::get('/badges',      [GamificationController::class, 'badges']);
        Route::get('/leaderboard', [GamificationController::class, 'leaderboard']);
    });

    // Factures annonceur (US-047)
    Route::prefix('invoices')->group(function () {
        Route::get('/',         [InvoiceController::class, 'index']);
        Route::get('/{id}/pdf', [InvoiceController::class, 'downloadPdf']);
    });

    // Consentements granulaires (C1–C6)
    Route::get('/consents',  [ConsentController::class, 'index']);
    Route::post('/consents', [ConsentController::class, 'update']);

    // Sondages rémunérés
    Route::get('/surveys',              [SurveyController::class, 'index']);
    Route::get('/surveys/{id}',         [SurveyController::class, 'show']);
    Route::post('/surveys/{id}/submit', [SurveyController::class, 'submit']);

    // Missions quotidiennes
    Route::get('/missions',               [MissionController::class, 'index']);
    Route::post('/missions/{id}/claim',   [MissionController::class, 'claim']);

    // Parrainage multi-niveaux (Feature 6)
    Route::get('/referrals/tree', [ReferralController::class, 'tree']);

    // Offres partenaires / cashback (Phase 3 — Feature 5)
    Route::get('/offers',               [OfferController::class, 'index']);
    Route::post('/offers/{id}/claim',   [OfferController::class, 'claim']);

    // Coupons collectés (Phase 3 — Feature 5)
    Route::get('/coupons',              [CouponController::class, 'index']);
    Route::post('/coupons/{id}/use',    [CouponController::class, 'markUsed']);

    // Offline mode — pre-download & sync (Phase 5)
    Route::get('/feed/preload', [OfflineFeedController::class, 'preload']);
    Route::post('/feed/sync',   [OfflineFeedController::class, 'sync']);
});
