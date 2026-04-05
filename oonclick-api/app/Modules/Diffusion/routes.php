<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Diffusion\Controllers\DiffusionController;

Route::prefix('api')->middleware(['api', 'auth:sanctum'])->group(function () {
    // Routes protégées par le middleware role.subscriber et trust.score
    Route::middleware(['role.subscriber', 'trust.score'])->group(function () {
        // Liste des publicités disponibles pour l'abonné authentifié
        Route::get('/feed',               [DiffusionController::class, 'feed']);

        // Démarrer la visualisation d'une publicité
        Route::post('/ads/{id}/start',    [DiffusionController::class, 'start'])->middleware('throttle:30,1');

        // Marquer une publicité comme complètement visionnée
        Route::post('/ads/{id}/complete', [DiffusionController::class, 'complete'])->middleware('throttle:30,1');

        // Historique des pubs regardées
        Route::get('/ads/history',   [DiffusionController::class, 'history']);

        // Mode hors-ligne partiel (Feature 7)
        Route::get('/feed/preload',  [DiffusionController::class, 'preload']);
        Route::post('/feed/sync',    [DiffusionController::class, 'sync']);
    });
});
