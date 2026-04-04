@extends('layouts.panel', ['panelLabel' => 'Admin'])

@section('title', 'Journal d\'audit')

@section('sidebar-nav')
    @include('panel.admin._sidebar', ['active' => 'audit'])
@endsection

@section('breadcrumb')
    <a href="{{ route('panel.admin.dashboard') }}">Admin</a> &rsaquo; <span class="current">Journal d'audit</span>
@endsection

@section('content')
    <div class="page-header">
        <h1 class="page-title">Journal d'audit</h1>
    </div>

    {{-- KPI STRIP --}}
    <div class="kpi-grid" style="grid-template-columns:repeat(3,1fr)">
        <div class="kpi-card">
            <div class="accent sky"></div>
            <div class="kpi-header">
                <div class="kpi-icon sky">📋</div>
                <span class="kpi-label">Total entrées</span>
            </div>
            <div class="kpi-value">{{ number_format($totalLogs, 0, ',', ' ') }}</div>
            <div class="kpi-change neutral">Logs enregistrés</div>
        </div>
        <div class="kpi-card">
            <div class="accent green"></div>
            <div class="kpi-header">
                <div class="kpi-icon green">📅</div>
                <span class="kpi-label">Aujourd'hui</span>
            </div>
            <div class="kpi-value">{{ $todayLogs }}</div>
            <div class="kpi-change neutral">Actions aujourd'hui</div>
        </div>
        <div class="kpi-card">
            <div class="accent purple"></div>
            <div class="kpi-header">
                <div class="kpi-icon purple">🗂️</div>
                <span class="kpi-label">Modules trackés</span>
            </div>
            <div class="kpi-value">{{ $modules->count() }}</div>
            <div class="kpi-change neutral">Modules distincts</div>
        </div>
    </div>

    {{-- TABLE --}}
    <div class="card">
        <div class="card-header">
            <div class="card-title">Entrées d'audit</div>
            <div style="display:flex;gap:8px;align-items:center">
                <select class="form-select" id="moduleFilter" onchange="filterByModule()"
                    style="border:1px solid #E2E8F0;border-radius:8px;padding:6px 12px;font-size:12px;font-family:inherit;color:#64748B;background:#F8FAFC;outline:none;cursor:pointer">
                    <option value="">Tous les modules</option>
                    @foreach($modules as $mod)
                        <option value="{{ $mod }}">{{ ucfirst($mod) }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        @if($logs->count() > 0)
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Action</th>
                    <th>Module</th>
                    <th>Plateforme</th>
                    <th>Utilisateur</th>
                    <th>IP</th>
                    <th>Nouvelles valeurs</th>
                </tr>
            </thead>
            <tbody>
                @foreach($logs as $log)
                <tr>
                    <td style="color:#94A3B8;font-size:11px;white-space:nowrap">
                        {{ $log->created_at?->format('d/m/Y H:i:s') ?? '—' }}
                    </td>
                    <td>
                        @php
                            $actionParts = explode('.', $log->action ?? '');
                            $actionVerb  = end($actionParts);
                            $actionColor = match($actionVerb) {
                                'created', 'registered' => 'badge-active',
                                'updated', 'modified'   => 'badge-info',
                                'deleted', 'suspended'  => 'badge-danger',
                                'approved', 'completed' => 'badge-active',
                                'rejected', 'failed'    => 'badge-danger',
                                default                  => 'badge-gray',
                            };
                        @endphp
                        <span class="badge {{ $actionColor }}" style="white-space:nowrap;font-size:10px">
                            {{ $log->action ?? '—' }}
                        </span>
                    </td>
                    <td>
                        @if($log->module)
                            <span class="badge badge-gray" style="font-size:10px">{{ ucfirst($log->module) }}</span>
                        @else
                            <span style="color:#CBD5E1">—</span>
                        @endif
                    </td>
                    <td>
                        @if($log->platform)
                            @php
                                $platColor = match(strtolower($log->platform ?? '')) {
                                    'mobile'  => 'badge-sub',
                                    'web'     => 'badge-info',
                                    'api'     => 'badge-adv',
                                    'admin'   => 'badge-admin',
                                    default   => 'badge-gray',
                                };
                            @endphp
                            <span class="badge {{ $platColor }}" style="font-size:10px">{{ ucfirst($log->platform) }}</span>
                        @else
                            <span style="color:#CBD5E1">—</span>
                        @endif
                    </td>
                    <td>
                        @if($log->user)
                        <div class="user-cell">
                            <div class="user-avatar" style="background:linear-gradient(135deg,#334155,#475569);width:26px;height:26px;font-size:9px">
                                {{ strtoupper(substr($log->user->name ?? $log->user->phone ?? 'U', 0, 1)) }}
                            </div>
                            <div>
                                <div class="user-name" style="font-size:12px">{{ Str::limit($log->user->name ?? '—', 16) }}</div>
                                <div class="user-sub" style="font-size:10px">{{ $log->user->phone }}</div>
                            </div>
                        </div>
                        @else
                            <span style="color:#CBD5E1;font-size:12px">Système</span>
                        @endif
                    </td>
                    <td style="font-family:monospace;font-size:11px;color:#94A3B8">
                        {{ $log->ip_address ?? '—' }}
                    </td>
                    <td style="max-width:200px">
                        @if(!empty($log->new_values))
                            <details style="cursor:pointer">
                                <summary style="font-size:11px;color:#0EA5E9;font-weight:600;list-style:none">
                                    Voir ({{ count($log->new_values) }} champ{{ count($log->new_values) > 1 ? 's' : '' }})
                                </summary>
                                <pre style="font-size:9px;color:#475569;background:#F8FAFC;padding:6px;border-radius:6px;margin-top:4px;overflow:auto;max-height:100px;white-space:pre-wrap;word-break:break-all">{{ json_encode($log->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                            </details>
                        @else
                            <span style="color:#CBD5E1;font-size:11px">—</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @if($logs->hasPages())
        <div class="pagination">
            <span>Affichage de {{ $logs->firstItem() }} à {{ $logs->lastItem() }} sur {{ $logs->total() }}</span>
            <div>{{ $logs->links('pagination::simple-default') }}</div>
        </div>
        @endif
        @else
        <div class="empty-state">
            <div class="icon">📋</div>
            <p>Aucune entrée d'audit enregistrée</p>
        </div>
        @endif
    </div>
@endsection

@push('scripts')
<script>
    function filterByModule() {
        const module = document.getElementById('moduleFilter').value;
        const url    = new URL(window.location.href);
        if (module) {
            url.searchParams.set('module', module);
        } else {
            url.searchParams.delete('module');
        }
        url.searchParams.delete('page');
        window.location.href = url.toString();
    }

    // Restore selected module from URL param
    document.addEventListener('DOMContentLoaded', function () {
        const params = new URLSearchParams(window.location.search);
        const mod    = params.get('module');
        if (mod) {
            const sel = document.getElementById('moduleFilter');
            if (sel) sel.value = mod;
        }
    });
</script>
@endpush
