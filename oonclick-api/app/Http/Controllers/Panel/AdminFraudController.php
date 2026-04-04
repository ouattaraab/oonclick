<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\FraudEvent;

class AdminFraudController extends Controller
{
    public function index()
    {
        $events        = FraudEvent::with('user')->latest()->paginate(25);
        $totalEvents   = FraudEvent::count();
        $criticalCount = FraudEvent::where('severity', 'critical')->count();
        $unresolvedCount = FraudEvent::where('is_resolved', false)->count();
        $resolvedCount = FraudEvent::where('is_resolved', true)->count();

        return view('panel.admin.fraud', compact(
            'events', 'totalEvents', 'criticalCount', 'unresolvedCount', 'resolvedCount'
        ));
    }
}
