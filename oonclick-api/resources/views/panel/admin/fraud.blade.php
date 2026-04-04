@extends('layouts.panel', ['panelLabel' => 'Admin'])

@section('title', 'Anti-fraude')

@section('sidebar-nav')
    @include('panel.admin._sidebar', ['active' => 'fraud'])
@endsection

@section('breadcrumb')
    <a href="{{ route('panel.admin.dashboard') }}">Admin</a> &rsaquo; <span class="current">Anti-fraude</span>
@endsection

@push('styles')
<style>
    .oon-pill { display:inline-block; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:700; letter-spacing:0.3px; }
    .oon-pill-critical { background:#FEE2E2; color:#7F1D1D; border:1px solid #FECACA; }
    .oon-pill-high     { background:#FEF3C7; color:#78350F; border:1px solid #FDE68A; }
    .oon-pill-medium   { background:#DBEAFE; color:#1E3A5F; border:1px solid #BFDBFE; }
    .oon-pill-low      { background:#F0FDF4; color:#14532D; border:1px solid #BBF7D0; }
</style>
@endpush

@section('content')
    <div class="page-header">
        <h1 class="page-title">Anti-fraude</h1>
    </div>

    {{-- KPI CARDS --}}
    <div class="kpi-grid">
        <div class="kpi-card">
            <div class="accent sky"></div>
            <div class="kpi-header">
                <div class="kpi-icon sky">🛡️</div>
                <span class="kpi-label">Total événements</span>
            </div>
            <div class="kpi-value">{{ number_format($totalEvents, 0, ',', ' ') }}</div>
            <div class="kpi-change neutral">Depuis le début</div>
        </div>
        <div class="kpi-card">
            <div class="accent rose"></div>
            <div class="kpi-header">
                <div class="kpi-icon" style="background:rgba(244,63,94,0.1);color:#F43F5E">🚨</div>
                <span class="kpi-label">Critiques</span>
            </div>
            <div class="kpi-value" style="color:#F43F5E">{{ $criticalCount }}</div>
            <div class="kpi-change down">Sévérité critique</div>
        </div>
        <div class="kpi-card">
            <div class="accent amber"></div>
            <div class="kpi-header">
                <div class="kpi-icon amber">⚠️</div>
                <span class="kpi-label">Non résolus</span>
            </div>
            <div class="kpi-value">{{ $unresolvedCount }}</div>
            <div class="kpi-change neutral">En attente de traitement</div>
        </div>
        <div class="kpi-card">
            <div class="accent green"></div>
            <div class="kpi-header">
                <div class="kpi-icon green">✅</div>
                <span class="kpi-label">Résolus</span>
            </div>
            <div class="kpi-value">{{ $resolvedCount }}</div>
            <div class="kpi-change neutral">Cas clôturés</div>
        </div>
    </div>

    {{-- TABLE --}}
    <div class="card">
        <div class="card-header">
            <div class="card-title">Événements de fraude détectés</div>
        </div>
        @if($events->count() > 0)
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Utilisateur</th>
                    <th>Impact score</th>
                    <th>Sévérité</th>
                    <th>Description</th>
                    <th>IP</th>
                    <th>Statut</th>
                </tr>
            </thead>
            <tbody>
                @foreach($events as $event)
                <tr>
                    <td style="color:#94A3B8;font-size:12px;white-space:nowrap">
                        {{ $event->created_at->format('d/m/Y H:i') }}
                    </td>
                    <td>
                        <span class="badge badge-info" style="white-space:nowrap">
                            {{ str_replace('_', ' ', ucfirst($event->type)) }}
                        </span>
                    </td>
                    <td>
                        @if($event->user)
                        <div class="user-cell">
                            <div class="user-avatar" style="background:linear-gradient(135deg,#EF4444,#B91C1C);width:28px;height:28px;font-size:10px">
                                {{ strtoupper(substr($event->user->name ?? $event->user->phone ?? 'U', 0, 1)) }}
                            </div>
                            <div>
                                <div class="user-name" style="font-size:12px">{{ Str::limit($event->user->name ?? '—', 18) }}</div>
                                <div class="user-sub">{{ $event->user->phone }}</div>
                            </div>
                        </div>
                        @else
                            <span style="color:#CBD5E1">—</span>
                        @endif
                    </td>
                    <td>
                        @php $impact = $event->trust_score_impact ?? 0; @endphp
                        <span style="font-weight:700;color:{{ $impact < 0 ? '#EF4444' : '#22C55E' }};font-size:13px">
                            {{ $impact > 0 ? '+' : '' }}{{ $impact }}
                        </span>
                    </td>
                    <td>
                        @php
                            $sev = strtolower($event->severity ?? 'low');
                        @endphp
                        <span class="oon-pill oon-pill-{{ $sev }}">
                            {{ ucfirst($sev) }}
                        </span>
                    </td>
                    <td style="max-width:220px">
                        <span style="font-size:12px;color:#475569;line-height:1.4">
                            {{ Str::limit($event->description ?? '—', 60) }}
                        </span>
                    </td>
                    <td style="font-family:monospace;font-size:11px;color:#94A3B8">
                        @php
                            $ip = $event->metadata['ip_address'] ?? ($event->metadata['ip'] ?? null);
                        @endphp
                        {{ $ip ?? '—' }}
                    </td>
                    <td>
                        @if($event->is_resolved)
                            <span class="badge badge-active">Résolu</span>
                        @else
                            <span class="badge badge-pending">Ouvert</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @if($events->hasPages())
        <div class="pagination">
            <span>Affichage de {{ $events->firstItem() }} à {{ $events->lastItem() }} sur {{ $events->total() }}</span>
            <div>{{ $events->links('pagination::simple-default') }}</div>
        </div>
        @endif
        @else
        <div class="empty-state">
            <div class="icon">🛡️</div>
            <p>Aucun événement de fraude détecté</p>
        </div>
        @endif
    </div>
@endsection
