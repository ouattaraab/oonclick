<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\FeatureSetting;
use App\Models\UserMission;

class AdminMissionsController extends Controller
{
    public function index()
    {
        $config   = FeatureSetting::getConfig('missions');
        $missions = $config['missions'] ?? [];
        $today    = now()->toDateString();

        // Stats globales
        $totalCompletions = UserMission::where('completed', true)->count();
        $todayCompletions = UserMission::where('completed', true)->where('date', $today)->count();
        $totalRewards     = UserMission::whereNotNull('rewarded_at')->count();

        // Stats par mission
        $missionStats = collect($missions)->map(function ($mission) use ($today) {
            $slug = $mission['slug'];

            return array_merge($mission, [
                'completions_today' => UserMission::where('mission_slug', $slug)->where('date', $today)->where('completed', true)->count(),
                'completions_total' => UserMission::where('mission_slug', $slug)->where('completed', true)->count(),
                'rewards_total'     => UserMission::where('mission_slug', $slug)->whereNotNull('rewarded_at')->count(),
            ]);
        });

        $isEnabled = FeatureSetting::isEnabled('missions');

        return view('panel.admin.missions', compact(
            'missionStats', 'totalCompletions', 'todayCompletions', 'totalRewards', 'isEnabled'
        ));
    }
}
