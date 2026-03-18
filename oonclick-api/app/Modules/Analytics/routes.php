<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Analytics\Controllers\AnalyticsController;

Route::prefix('api')->middleware(['api', 'auth:sanctum'])->group(function () {
    // Tableau de bord statistiques pour l'annonceur
    Route::get('/analytics/campaigns/{id}',        [AnalyticsController::class, 'campaignStats']);

    // Export du rapport de campagne en PDF
    Route::get('/analytics/campaigns/{id}/export', [AnalyticsController::class, 'exportPdf']);
});
