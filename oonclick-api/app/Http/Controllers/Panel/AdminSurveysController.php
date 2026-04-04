<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Survey;
use App\Models\User;
use Illuminate\Http\Request;

class AdminSurveysController extends Controller
{
    /**
     * GET /panel/admin/surveys
     *
     * Affiche la liste de tous les sondages.
     */
    public function index()
    {
        $surveys       = Survey::with('creator')->latest()->paginate(20);
        $totalSurveys  = Survey::count();
        $activeSurveys = Survey::where('is_active', true)->count();
        $totalResponses = \App\Models\SurveyResponse::count();

        return view('panel.admin.surveys', compact(
            'surveys', 'totalSurveys', 'activeSurveys', 'totalResponses'
        ));
    }

    /**
     * GET /panel/admin/surveys/create
     *
     * Affiche le formulaire de création d'un sondage.
     */
    public function create()
    {
        return view('panel.admin.create-survey');
    }

    /**
     * POST /panel/admin/surveys
     *
     * Enregistre un nouveau sondage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title'         => 'required|string|max:255',
            'description'   => 'nullable|string',
            'reward_amount' => 'required|integer|min:1',
            'reward_xp'     => 'nullable|integer|min:0',
            'questions'     => 'required|string',
            'max_responses' => 'nullable|integer|min:1',
            'expires_at'    => 'nullable|date',
        ]);

        // Valider le JSON des questions
        $questions = json_decode($request->input('questions'), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return back()->withErrors(['questions' => 'Le JSON des questions est invalide.'])->withInput();
        }

        Survey::create([
            'title'         => $request->input('title'),
            'description'   => $request->input('description'),
            'reward_amount' => (int) $request->input('reward_amount'),
            'reward_xp'     => (int) ($request->input('reward_xp') ?? 20),
            'questions'     => $questions,
            'is_active'     => true,
            'max_responses' => $request->filled('max_responses') ? (int) $request->input('max_responses') : null,
            'expires_at'    => $request->filled('expires_at') ? $request->input('expires_at') : null,
            'created_by'    => auth()->id(),
        ]);

        return redirect()->route('panel.admin.surveys')->with('success', 'Sondage créé avec succès.');
    }

    /**
     * GET /panel/admin/surveys/{survey}
     *
     * Affiche les réponses d'un sondage.
     */
    public function show(Survey $survey)
    {
        $responses      = $survey->responses()->with('user')->latest()->paginate(30);
        $totalResponses = $survey->responses_count;

        return view('panel.admin.show-survey', compact('survey', 'responses', 'totalResponses'));
    }

    /**
     * POST /panel/admin/surveys/{survey}/toggle
     *
     * Active ou désactive un sondage.
     */
    public function toggleActive(Survey $survey)
    {
        $survey->update(['is_active' => ! $survey->is_active]);

        $state = $survey->is_active ? 'activé' : 'désactivé';

        return back()->with('success', "Sondage « {$survey->title} » {$state}.");
    }
}
