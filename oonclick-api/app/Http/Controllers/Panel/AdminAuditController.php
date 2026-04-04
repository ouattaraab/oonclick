<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class AdminAuditController extends Controller
{
    public function index(Request $request)
    {
        $query = AuditLog::with('user')->orderBy('created_at', 'desc');

        if ($request->filled('module')) {
            $query->where('module', $request->module);
        }

        $logs      = $query->paginate(30)->withQueryString();
        $totalLogs = AuditLog::count();
        $todayLogs = AuditLog::whereDate('created_at', today())->count();
        $modules   = AuditLog::select('module')->distinct()->whereNotNull('module')->orderBy('module')->pluck('module');

        return view('panel.admin.audit', compact('logs', 'totalLogs', 'todayLogs', 'modules'));
    }
}
