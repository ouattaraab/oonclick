<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\FeatureSetting;
use Illuminate\Http\Request;

class AdminFeaturesController extends Controller
{
    public function index()
    {
        $features      = FeatureSetting::orderBy('sort_order')->get();
        $totalFeatures = $features->count();
        $enabledCount  = $features->where('is_enabled', true)->count();
        $disabledCount = $features->where('is_enabled', false)->count();

        return view('panel.admin.features', compact(
            'features', 'totalFeatures', 'enabledCount', 'disabledCount'
        ));
    }

    public function toggleActive(FeatureSetting $feature)
    {
        $feature->update(['is_enabled' => ! $feature->is_enabled]);

        FeatureSetting::clearCache($feature->feature_slug);

        $state = $feature->is_enabled ? 'activée' : 'désactivée';

        return back()->with('success', "Fonctionnalité « {$feature->label} » {$state}.");
    }

    public function updateConfig(Request $request, FeatureSetting $feature)
    {
        $request->validate([
            'config' => 'required|string',
        ]);

        // Validate JSON
        $decoded = json_decode($request->input('config'), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return back()->withErrors(['config' => 'Le JSON fourni est invalide : ' . json_last_error_msg()]);
        }

        $feature->update(['config' => $decoded]);

        FeatureSetting::clearCache($feature->feature_slug);

        return back()->with('success', "Configuration de « {$feature->label} » mise à jour.");
    }
}
