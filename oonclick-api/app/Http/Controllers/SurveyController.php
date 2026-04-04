<?php

namespace App\Http\Controllers;

use App\Models\FeatureSetting;
use App\Models\Survey;
use App\Models\SurveyResponse;
use App\Modules\Payment\Services\WalletService;
use App\Services\GamificationService;
use App\Services\MissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Gère les sondages rémunérés pour les abonnés.
 */
class SurveyController extends Controller
{
    /**
     * GET /api/surveys
     *
     * Retourne la liste des sondages disponibles pour l'utilisateur authentifié.
     * Exclut les sondages déjà complétés, expirés ou ayant atteint leur quota.
     */
    public function index(Request $request): JsonResponse
    {
        if (! FeatureSetting::isEnabled('surveys')) {
            return response()->json(['data' => []]);
        }

        $user    = $request->user();
        $surveys = Survey::where('is_active', true)
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->where(fn ($q) => $q->whereNull('max_responses')->orWhereColumn('responses_count', '<', 'max_responses'))
            ->whereDoesntHave('responses', fn ($q) => $q->where('user_id', $user->id))
            ->orderByDesc('reward_amount')
            ->get();

        return response()->json(['data' => $surveys]);
    }

    /**
     * GET /api/surveys/{id}
     *
     * Retourne le détail d'un sondage avec ses questions.
     */
    public function show(int $id): JsonResponse
    {
        if (! FeatureSetting::isEnabled('surveys')) {
            return response()->json(['message' => 'Fonctionnalité désactivée.'], 403);
        }

        $survey = Survey::where('is_active', true)->findOrFail($id);

        return response()->json(['data' => $survey]);
    }

    /**
     * POST /api/surveys/{id}/submit
     *
     * Soumet les réponses d'un utilisateur à un sondage et crédite sa récompense.
     */
    public function submit(Request $request, int $id): JsonResponse
    {
        if (! FeatureSetting::isEnabled('surveys')) {
            return response()->json(['message' => 'Fonctionnalité désactivée.'], 403);
        }

        $user   = $request->user();
        $survey = Survey::findOrFail($id);

        // Vérifier que le sondage est encore actif et disponible
        if (! $survey->is_active) {
            return response()->json(['message' => 'Ce sondage n\'est plus disponible.'], 422);
        }

        if ($survey->expires_at && $survey->expires_at->isPast()) {
            return response()->json(['message' => 'Ce sondage est expiré.'], 422);
        }

        if ($survey->max_responses && $survey->responses_count >= $survey->max_responses) {
            return response()->json(['message' => 'Ce sondage a atteint son quota de réponses.'], 422);
        }

        // Vérifier que l'utilisateur n'a pas déjà répondu
        if ($survey->responses()->where('user_id', $user->id)->exists()) {
            return response()->json(['message' => 'Vous avez déjà répondu à ce sondage.'], 409);
        }

        $data = $request->validate(['answers' => 'required|array']);

        DB::transaction(function () use ($survey, $user, $data) {
            SurveyResponse::create([
                'survey_id'    => $survey->id,
                'user_id'      => $user->id,
                'answers'      => $data['answers'],
                'completed_at' => now(),
                'credited'     => true,
            ]);

            $survey->increment('responses_count');

            // Créditer le wallet
            app(WalletService::class)->credit(
                $user->id,
                $survey->reward_amount,
                'bonus',
                "Sondage : {$survey->title}"
            );

            // Attribuer les XP
            try {
                app(GamificationService::class)->awardXp($user, $survey->reward_xp, 'survey_completion');
            } catch (\Throwable $e) {
                Log::warning("GamificationService XP award failed for survey#{$survey->id} user#{$user->id}: {$e->getMessage()}");
            }
        });

        // Incrémenter la progression des missions de type 'survey' — hors transaction
        try {
            app(MissionService::class)->incrementProgress($user, 'survey');
        } catch (\Throwable $e) {
            Log::warning("MissionService incrementProgress failed for survey user#{$user->id}: {$e->getMessage()}");
        }

        return response()->json([
            'message' => 'Merci pour votre participation !',
            'reward'  => $survey->reward_amount,
            'xp'      => $survey->reward_xp,
        ]);
    }
}
