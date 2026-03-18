<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Diffusion\Controllers\DiffusionController;

Route::prefix('api')->middleware(['api', 'auth:sanctum'])->group(function () {
    // Routes protégées par le middleware role.subscriber et trust.score
    Route::middleware(['role.subscriber', 'trust.score'])->group(function () {
        // Liste des publicités disponibles pour l'abonné authentifié
        Route::get('/feed',               [DiffusionController::class, 'feed']);

        // Démarrer la visualisation d'une publicité
        Route::post('/ads/{id}/start',    [DiffusionController::class, 'start']);

        // Marquer une publicité comme complètement visionnée
        Route::post('/ads/{id}/complete', [DiffusionController::class, 'complete']);
    });
});
