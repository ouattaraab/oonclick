<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Campaign\Controllers\CampaignController;

Route::prefix('api')->middleware(['api', 'auth:sanctum'])->group(function () {
    // Routes réservées aux annonceurs — création et gestion de campagnes
    Route::middleware('role.advertiser')->group(function () {
        Route::post('/campaigns',                [CampaignController::class, 'store']);
        Route::get('/campaigns',                 [CampaignController::class, 'index']);
        Route::get('/campaigns/{id}',            [CampaignController::class, 'show']);
        Route::patch('/campaigns/{id}',          [CampaignController::class, 'update']);
        Route::delete('/campaigns/{id}',         [CampaignController::class, 'destroy']);
        Route::post('/campaigns/{id}/media',     [CampaignController::class, 'uploadMedia']);
    });

    // Routes accessibles aux annonceurs ET aux admins (pas de role.advertiser ici)
    Route::post('/campaigns/{id}/submit',        [CampaignController::class, 'submit']);
    Route::post('/campaigns/{id}/pause',         [CampaignController::class, 'pause']);
    Route::post('/campaigns/{id}/resume',        [CampaignController::class, 'resume']);
});
