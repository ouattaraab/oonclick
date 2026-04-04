<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\CampaignFormat;
use Illuminate\Http\Request;

class AdminCampaignFormatsController extends Controller
{
    public function index()
    {
        $formats       = CampaignFormat::orderBy('sort_order')->get();
        $totalFormats  = $formats->count();
        $activeCount   = $formats->where('is_active', true)->count();
        $inactiveCount = $formats->where('is_active', false)->count();

        return view('panel.admin.campaign-formats', compact(
            'formats', 'totalFormats', 'activeCount', 'inactiveCount'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'slug'             => 'required|unique:campaign_formats,slug|alpha_dash',
            'label'            => 'required|max:100',
            'description'      => 'nullable|string',
            'icon'             => 'nullable|string|max:10',
            'multiplier'       => 'required|numeric|min:0.1|max:5.0',
            'default_duration' => 'nullable|integer',
            'accepted_media'   => 'required|array',
            'is_active'        => 'boolean',
            'sort_order'       => 'integer',
        ]);

        CampaignFormat::create([
            'slug'             => $request->slug,
            'label'            => $request->label,
            'description'      => $request->description,
            'icon'             => $request->icon,
            'multiplier'       => $request->multiplier,
            'default_duration' => $request->default_duration,
            'accepted_media'   => $request->accepted_media,
            'is_active'        => $request->boolean('is_active'),
            'sort_order'       => $request->input('sort_order', 0),
        ]);

        CampaignFormat::clearCache();

        return back()->with('success', "Format « {$request->label} » créé avec succès.");
    }

    public function update(Request $request, CampaignFormat $format)
    {
        $request->validate([
            'slug'             => 'required|alpha_dash|unique:campaign_formats,slug,' . $format->id,
            'label'            => 'required|max:100',
            'description'      => 'nullable|string',
            'icon'             => 'nullable|string|max:10',
            'multiplier'       => 'required|numeric|min:0.1|max:5.0',
            'default_duration' => 'nullable|integer',
            'accepted_media'   => 'required|array',
            'is_active'        => 'boolean',
            'sort_order'       => 'integer',
        ]);

        $format->update([
            'slug'             => $request->slug,
            'label'            => $request->label,
            'description'      => $request->description,
            'icon'             => $request->icon,
            'multiplier'       => $request->multiplier,
            'default_duration' => $request->default_duration,
            'accepted_media'   => $request->accepted_media,
            'is_active'        => $request->boolean('is_active'),
            'sort_order'       => $request->input('sort_order', 0),
        ]);

        CampaignFormat::clearCache();

        return back()->with('success', "Format « {$format->label} » mis à jour.");
    }

    public function toggleActive(CampaignFormat $format)
    {
        $format->update(['is_active' => ! $format->is_active]);

        CampaignFormat::clearCache();

        $state = $format->is_active ? 'activé' : 'désactivé';

        return back()->with('success', "Format « {$format->label} » {$state}.");
    }
}
