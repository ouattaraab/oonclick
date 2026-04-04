<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\AudienceCriterion;
use App\Services\FcmService;
use Illuminate\Http\Request;

class AdminAudienceCriteriaController extends Controller
{
    public function index()
    {
        $criteria        = AudienceCriterion::orderBy('sort_order')->get();
        $totalCriteria   = $criteria->count();
        $activeCount     = $criteria->where('is_active', true)->count();
        $requiredCount   = $criteria->where('is_required_for_profile', true)->count();
        $grouped         = $criteria->groupBy(fn ($c) => $c->category ?? 'Général');

        return view('panel.admin.audience-criteria', compact(
            'criteria', 'totalCriteria', 'activeCount', 'requiredCount', 'grouped'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'                    => 'required|unique:audience_criteria,name|alpha_dash',
            'label'                   => 'required|max:100',
            'type'                    => 'required|in:text,select,multiselect,number,range,boolean',
            'options'                 => 'nullable|array',
            'category'                => 'nullable|string|max:50',
            'is_active'               => 'boolean',
            'is_required_for_profile' => 'boolean',
            'sort_order'              => 'integer',
        ]);

        // options is required when type is select or multiselect
        if (in_array($request->type, ['select', 'multiselect'])) {
            $request->validate([
                'options' => 'required|array|min:1',
            ]);
        }

        $criterion = AudienceCriterion::create([
            'name'                    => $request->name,
            'label'                   => $request->label,
            'type'                    => $request->type,
            'options'                 => $request->options,
            'category'                => $request->category,
            'is_active'               => $request->boolean('is_active'),
            'is_required_for_profile' => $request->boolean('is_required_for_profile'),
            'sort_order'              => $request->input('sort_order', 0),
        ]);

        AudienceCriterion::clearCache();

        // Push notification when criterion is active AND required for profile
        if ($criterion->is_active && $criterion->is_required_for_profile) {
            try {
                app(FcmService::class)->sendToAllSubscribers(
                    'Mettez à jour votre profil',
                    "Un nouveau champ « {$criterion->label} » est disponible.",
                    ['screen' => 'profile']
                );
            } catch (\Throwable $e) {
                // Silently fail — do not block the admin action
                \Illuminate\Support\Facades\Log::warning('FCM push failed after criterion creation: ' . $e->getMessage());
            }
        }

        return back()->with('success', "Critère « {$criterion->label} » créé avec succès.");
    }

    public function update(Request $request, AudienceCriterion $criterion)
    {
        $request->validate([
            'name'                    => 'required|alpha_dash|unique:audience_criteria,name,' . $criterion->id,
            'label'                   => 'required|max:100',
            'type'                    => 'required|in:text,select,multiselect,number,range,boolean',
            'options'                 => 'nullable|array',
            'category'                => 'nullable|string|max:50',
            'is_active'               => 'boolean',
            'is_required_for_profile' => 'boolean',
            'sort_order'              => 'integer',
        ]);

        if (in_array($request->type, ['select', 'multiselect'])) {
            $request->validate([
                'options' => 'required|array|min:1',
            ]);
        }

        $criterion->update([
            'name'                    => $request->name,
            'label'                   => $request->label,
            'type'                    => $request->type,
            'options'                 => $request->options,
            'category'                => $request->category,
            'is_active'               => $request->boolean('is_active'),
            'is_required_for_profile' => $request->boolean('is_required_for_profile'),
            'sort_order'              => $request->input('sort_order', 0),
        ]);

        AudienceCriterion::clearCache();

        return back()->with('success', "Critère « {$criterion->label} » mis à jour.");
    }

    public function toggleActive(AudienceCriterion $criterion)
    {
        $criterion->update(['is_active' => ! $criterion->is_active]);

        AudienceCriterion::clearCache();

        $state = $criterion->is_active ? 'activé' : 'désactivé';

        return back()->with('success', "Critère « {$criterion->label} » {$state}.");
    }
}
