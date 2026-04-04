<?php

use App\Http\Controllers\Panel\AdminDashboardController;
use App\Http\Controllers\Panel\AdminCampaignsController;
use App\Http\Controllers\Panel\AdminUsersController;
use App\Http\Controllers\Panel\AdminWithdrawalsController;
use App\Http\Controllers\Panel\AdminFraudController;
use App\Http\Controllers\Panel\AdminConfigController;
use App\Http\Controllers\Panel\AdminRolesController;
use App\Http\Controllers\Panel\AdminAuditController;
use App\Http\Controllers\Panel\AdminKycController;
use App\Http\Controllers\Panel\AdminNotificationsController;
use App\Http\Controllers\Panel\AdminCampaignFormatsController;
use App\Http\Controllers\Panel\AdminAudienceCriteriaController;
use App\Http\Controllers\Panel\AdminCouponsController;
use App\Http\Controllers\Panel\AdminFeaturesController;
use App\Http\Controllers\Panel\AdminOffersController;
use App\Http\Controllers\Panel\AdminMissionsController;
use App\Http\Controllers\Panel\AdminSurveysController;
use App\Http\Controllers\Panel\PanelLoginController;
use App\Http\Controllers\Panel\PanelPasswordResetController;
use App\Http\Controllers\Panel\AdvertiserDashboardController;
use App\Http\Controllers\Panel\AdvertiserCampaignsController;
use App\Http\Controllers\Panel\AdvertiserInvoicesController;
use App\Http\Controllers\Panel\AdvertiserStatsController;
use App\Http\Controllers\Panel\AdvertiserSettingsController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\LegalController;
use Illuminate\Support\Facades\Route;

// Landing page
Route::get('/', function () {
    return view('welcome');
})->name('home');

// ──────────────────────────────────────────────────────────────────────────────
// Legal pages (public)
// ──────────────────────────────────────────────────────────────────────────────
Route::get('/cgu',             [LegalController::class, 'cgu'])->name('legal.cgu');
Route::get('/confidentialite', [LegalController::class, 'privacy'])->name('legal.privacy');

// Consent management (authenticated)
Route::get('/panel/consents',  [LegalController::class, 'consents'])->middleware('auth')->name('legal.consents');
Route::post('/panel/consents', [LegalController::class, 'updateConsents'])->middleware('auth')->name('legal.consents.update');

// Google OAuth (callback MUST be registered before the {role?} wildcard)
Route::get('/auth/google/callback',   [GoogleController::class, 'callback'])->name('auth.google.callback');
Route::get('/auth/google/{role?}',    [GoogleController::class, 'redirect'])->name('auth.google');

// ──────────────────────────────────────────────────────────────────────────────
// Public registration routes
// ──────────────────────────────────────────────────────────────────────────────
Route::middleware('web')->group(function () {
    Route::get('/register',             [RegisterController::class, 'showSubscriberForm'])->name('register');
    Route::post('/register',            [RegisterController::class, 'registerSubscriber'])->name('register.submit');
    Route::get('/register/advertiser',  [RegisterController::class, 'showAdvertiserForm'])->name('register.advertiser');
    Route::post('/register/advertiser', [RegisterController::class, 'registerAdvertiser'])->name('register.advertiser.submit');
});

// Redirect old Filament routes to new custom panel
Route::get('/admin/{any?}', fn () => redirect('/panel/admin'))->where('any', '.*');
Route::get('/advertiser/{any?}', fn () => redirect('/panel/advertiser'))->where('any', '.*');

// ──────────────────────────────────────────────────────────────────────────────
// Panel authentication (unauthenticated routes)
// ──────────────────────────────────────────────────────────────────────────────
Route::middleware('web')->group(function () {
    Route::get('/panel/login',  [PanelLoginController::class, 'showLoginForm'])->name('panel.login');
    Route::post('/panel/login', [PanelLoginController::class, 'login'])->middleware('throttle:5,1')->name('panel.login.submit');
    Route::post('/panel/logout', [PanelLoginController::class, 'logout'])->name('panel.logout');

    // Réinitialisation de mot de passe (US-008)
    Route::get('/panel/forgot-password',  [PanelPasswordResetController::class, 'showForgotForm'])->name('panel.password.request');
    Route::post('/panel/forgot-password', [PanelPasswordResetController::class, 'sendResetLink'])->name('panel.password.email');
    Route::get('/panel/reset-password',   [PanelPasswordResetController::class, 'showResetForm'])->name('panel.password.reset');
    Route::post('/panel/reset-password',  [PanelPasswordResetController::class, 'reset'])->name('panel.password.update');
});

// ──────────────────────────────────────────────────────────────────────────────
// Custom admin panel (replaces Filament /admin)
// ──────────────────────────────────────────────────────────────────────────────
Route::prefix('panel/admin')->middleware(['web', 'auth', 'admin'])->group(function () {
    Route::get('/',          AdminDashboardController::class)->name('panel.admin.dashboard');
    Route::get('/users',     [AdminUsersController::class, 'index'])->name('panel.admin.users');
    Route::get('/users/{user}', [AdminUsersController::class, 'show'])->name('panel.admin.users.show');
    Route::post('/users/{user}/suspend',   [AdminUsersController::class, 'suspend'])->name('panel.admin.users.suspend');
    Route::post('/users/{user}/unsuspend', [AdminUsersController::class, 'unsuspend'])->name('panel.admin.users.unsuspend');

    Route::get('/campaigns', [AdminCampaignsController::class, 'index'])->name('panel.admin.campaigns');
    Route::get('/campaigns/{campaign}', [AdminCampaignsController::class, 'show'])->name('panel.admin.campaigns.show');
    Route::post('/campaigns/{campaign}/approve', [AdminCampaignsController::class, 'approve'])->name('panel.admin.campaigns.approve');
    Route::post('/campaigns/{campaign}/reject',  [AdminCampaignsController::class, 'reject'])->name('panel.admin.campaigns.reject');
    Route::post('/campaigns/{campaign}/pause',   [AdminCampaignsController::class, 'pause'])->name('panel.admin.campaigns.pause');
    Route::post('/campaigns/{campaign}/resume',  [AdminCampaignsController::class, 'resume'])->name('panel.admin.campaigns.resume');

    // Withdrawals
    Route::get('/withdrawals',                   [AdminWithdrawalsController::class, 'index'])->name('panel.admin.withdrawals');
    Route::post('/withdrawals/batch-approve',    [AdminWithdrawalsController::class, 'batchApprove'])->name('panel.admin.withdrawals.batch-approve'); // US-044
    Route::post('/withdrawals/{withdrawal}/approve', [AdminWithdrawalsController::class, 'approve'])->name('panel.admin.withdrawals.approve');
    Route::post('/withdrawals/{withdrawal}/reject',  [AdminWithdrawalsController::class, 'reject'])->name('panel.admin.withdrawals.reject');

    // Fraud events
    Route::get('/fraud', [AdminFraudController::class, 'index'])->name('panel.admin.fraud');

    // Platform configuration
    Route::get('/config',          [AdminConfigController::class, 'index'])->name('panel.admin.config');
    Route::put('/config/{config}', [AdminConfigController::class, 'update'])->name('panel.admin.config.update');

    // Roles & Permissions
    Route::get('/roles', [AdminRolesController::class, 'index'])->name('panel.admin.roles');

    // Audit log
    Route::get('/audit', [AdminAuditController::class, 'index'])->name('panel.admin.audit');

    // KYC — vérification des documents d'identité
    Route::get('/kyc',                           [AdminKycController::class, 'index'])->name('panel.admin.kyc');
    Route::post('/kyc/{document}/approve',       [AdminKycController::class, 'approve'])->name('panel.admin.kyc.approve');
    Route::post('/kyc/{document}/reject',        [AdminKycController::class, 'reject'])->name('panel.admin.kyc.reject');

    // Notifications push manuelles (US-053)
    Route::get('/notifications/send',  [AdminNotificationsController::class, 'create'])->name('panel.admin.notifications.send');
    Route::post('/notifications/send', [AdminNotificationsController::class, 'send'])->name('panel.admin.notifications.dispatch');

    // Campaign formats management
    Route::get('/campaign-formats',                          [AdminCampaignFormatsController::class, 'index'])->name('panel.admin.campaign-formats');
    Route::post('/campaign-formats',                         [AdminCampaignFormatsController::class, 'store'])->name('panel.admin.campaign-formats.store');
    Route::put('/campaign-formats/{format}',                 [AdminCampaignFormatsController::class, 'update'])->name('panel.admin.campaign-formats.update');
    Route::post('/campaign-formats/{format}/toggle',         [AdminCampaignFormatsController::class, 'toggleActive'])->name('panel.admin.campaign-formats.toggle');

    // Feature settings management
    Route::get('/features',                          [AdminFeaturesController::class, 'index'])->name('panel.admin.features');
    Route::post('/features/{feature}/toggle',        [AdminFeaturesController::class, 'toggleActive'])->name('panel.admin.features.toggle');
    Route::put('/features/{feature}/config',         [AdminFeaturesController::class, 'updateConfig'])->name('panel.admin.features.config');

    // Sondages rémunérés
    Route::get('/surveys',                   [AdminSurveysController::class, 'index'])->name('panel.admin.surveys');
    Route::get('/surveys/create',            [AdminSurveysController::class, 'create'])->name('panel.admin.surveys.create');
    Route::post('/surveys',                  [AdminSurveysController::class, 'store'])->name('panel.admin.surveys.store');
    Route::get('/surveys/{survey}',          [AdminSurveysController::class, 'show'])->name('panel.admin.surveys.show');
    Route::post('/surveys/{survey}/toggle',  [AdminSurveysController::class, 'toggleActive'])->name('panel.admin.surveys.toggle');

    // Missions quotidiennes
    Route::get('/missions', [AdminMissionsController::class, 'index'])->name('panel.admin.missions');

    // Audience criteria management
    Route::get('/audience-criteria',                         [AdminAudienceCriteriaController::class, 'index'])->name('panel.admin.audience-criteria');
    Route::post('/audience-criteria',                        [AdminAudienceCriteriaController::class, 'store'])->name('panel.admin.audience-criteria.store');
    Route::put('/audience-criteria/{criterion}',             [AdminAudienceCriteriaController::class, 'update'])->name('panel.admin.audience-criteria.update');
    Route::post('/audience-criteria/{criterion}/toggle',     [AdminAudienceCriteriaController::class, 'toggleActive'])->name('panel.admin.audience-criteria.toggle');

    // Offres cashback partenaires (Phase 3 — Feature 5)
    Route::get('/offers',                               [AdminOffersController::class, 'index'])->name('panel.admin.offers');
    Route::post('/offers',                              [AdminOffersController::class, 'store'])->name('panel.admin.offers.store');
    Route::post('/offers/{offer}/toggle',               [AdminOffersController::class, 'toggleActive'])->name('panel.admin.offers.toggle');
    Route::post('/offers/claims/{claim}/approve',       [AdminOffersController::class, 'approveClaim'])->name('panel.admin.offers.claims.approve');
    Route::post('/offers/claims/{claim}/reject',        [AdminOffersController::class, 'rejectClaim'])->name('panel.admin.offers.claims.reject');

    // Coupons partenaires (Phase 3 — Feature 5)
    Route::get('/coupons',                              [AdminCouponsController::class, 'index'])->name('panel.admin.coupons');
    Route::post('/coupons',                             [AdminCouponsController::class, 'store'])->name('panel.admin.coupons.store');
    Route::post('/coupons/{coupon}/toggle',             [AdminCouponsController::class, 'toggleActive'])->name('panel.admin.coupons.toggle');
});

// ──────────────────────────────────────────────────────────────────────────────
// Custom advertiser panel
// ──────────────────────────────────────────────────────────────────────────────
Route::prefix('panel/advertiser')->middleware(['web', 'auth', 'advertiser'])->group(function () {
    Route::get('/',                 AdvertiserDashboardController::class)->name('panel.advertiser.dashboard');
    Route::get('/campaigns',        [AdvertiserCampaignsController::class, 'index'])->name('panel.advertiser.campaigns');
    Route::get('/campaigns/create', [AdvertiserCampaignsController::class, 'create'])->name('panel.advertiser.campaigns.create');
    Route::post('/campaigns',       [AdvertiserCampaignsController::class, 'store'])->name('panel.advertiser.campaigns.store');

    // Coupons annonceur — must be before {campaign} wildcard
    Route::get('/coupons',  [AdvertiserCampaignsController::class, 'coupons'])->name('panel.advertiser.coupons');
    Route::post('/coupons', [AdvertiserCampaignsController::class, 'storeCoupon'])->name('panel.advertiser.coupons.store');

    // Offres cashback partenaires — must be before {campaign} wildcard
    Route::get('/offers', [AdvertiserCampaignsController::class, 'offers'])->name('panel.advertiser.offers');

    // Audience estimation (AJAX) — must be before {campaign} wildcard
    Route::post('/campaigns/estimate-audience', [AdvertiserCampaignsController::class, 'estimateAudience'])->name('panel.advertiser.campaigns.estimate');

    // Payment callback (GET) — must be before {campaign} wildcard
    Route::get('/campaigns/payment-callback', [AdvertiserCampaignsController::class, 'paymentCallback'])->name('panel.advertiser.campaigns.payment-callback');

    Route::get('/campaigns/{campaign}',            [AdvertiserCampaignsController::class, 'show'])->name('panel.advertiser.campaigns.show');
    Route::get('/campaigns/{campaign}/progress',   [AdvertiserCampaignsController::class, 'progress'])->name('panel.advertiser.campaigns.progress');
    Route::post('/campaigns/{campaign}/duplicate', [AdvertiserCampaignsController::class, 'duplicate'])->name('panel.advertiser.campaigns.duplicate');
    Route::get('/campaigns/{campaign}/pdf',        [AdvertiserCampaignsController::class, 'downloadPdf'])->name('panel.advertiser.campaigns.pdf');

    // Payment initiation — after {campaign} binding is available
    Route::post('/campaigns/{campaign}/pay', [AdvertiserCampaignsController::class, 'initiatePayment'])->name('panel.advertiser.campaigns.pay');

    // Factures annonceur (US-047)
    Route::get('/invoices',          [AdvertiserInvoicesController::class, 'index'])->name('panel.advertiser.invoices');
    Route::get('/invoices/{invoice}/pdf', [AdvertiserInvoicesController::class, 'downloadPdf'])->name('panel.advertiser.invoices.pdf');

    // Statistiques annonceur
    Route::get('/stats', AdvertiserStatsController::class)->name('panel.advertiser.stats');

    // Paramètres annonceur
    Route::get('/settings',          [AdvertiserSettingsController::class, 'show'])->name('panel.advertiser.settings');
    Route::put('/settings/profile',  [AdvertiserSettingsController::class, 'updateProfile'])->name('panel.advertiser.settings.profile');
    Route::post('/settings/avatar', [AdvertiserSettingsController::class, 'updateAvatar'])->name('panel.advertiser.settings.avatar');
    Route::put('/settings/password', [AdvertiserSettingsController::class, 'updatePassword'])->name('panel.advertiser.settings.password');
});
