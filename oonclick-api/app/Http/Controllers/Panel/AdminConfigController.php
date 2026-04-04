<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\PlatformConfig;
use Illuminate\Http\Request;

class AdminConfigController extends Controller
{
    public function index()
    {
        $configs      = PlatformConfig::orderBy('key')->paginate(30);
        $totalConfigs = PlatformConfig::count();
        $publicCount  = PlatformConfig::where('is_public', true)->count();

        return view('panel.admin.config', compact('configs', 'totalConfigs', 'publicCount'));
    }

    public function update(Request $request, PlatformConfig $config)
    {
        $request->validate([
            'value' => 'required|string|max:10000',
        ]);

        $config->update(['value' => $request->value]);

        \Illuminate\Support\Facades\Cache::driver('file')->forget("platform_config.{$config->key}");

        return back()->with('success', "Configuration « {$config->key} » mise à jour.");
    }
}
