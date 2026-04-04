<?php

namespace App\Http\Controllers;

use App\Services\MissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Gère les missions quotidiennes des abonnés.
 */
class MissionController extends Controller
{
    /**
     * GET /api/missions
     *
     * Retourne les missions du jour pour l'utilisateur authentifié.
     * Crée les enregistrements UserMission si nécessaire.
     */
    public function index(Request $request): JsonResponse
    {
        $missions = app(MissionService::class)->getTodayMissions($request->user());

        return response()->json(['data' => $missions]);
    }

    /**
     * POST /api/missions/{id}/claim
     *
     * Réclame la récompense d'une mission complétée.
     */
    public function claim(Request $request, int $id): JsonResponse
    {
        $result = app(MissionService::class)->claimReward($request->user(), $id);

        return response()->json([
            'message'    => 'Récompense réclamée !',
            'reward_fcfa' => $result['reward_fcfa'],
            'reward_xp'  => $result['reward_xp'],
            'mission'    => $result['mission'],
        ]);
    }
}
