@extends('layouts.panel', ['panelLabel' => 'Admin'])

@section('title', 'Missions quotidiennes')

@section('sidebar-nav')
    @include('panel.admin._sidebar', ['active' => 'missions'])
@endsection

@section('breadcrumb')
    <a href="{{ route('panel.admin.dashboard') }}">Admin</a> &rsaquo; <span class="current">Missions</span>
@endsection

@push('styles')
<style>
    .stats-row { display:flex; gap:14px; margin-bottom:24px; flex-wrap:wrap; }
    .stat-card { flex:1; min-width:140px; background:#fff; border:1px solid #E2E8F0; border-radius:12px; padding:16px 20px; }
    .stat-label { font-size:11px; color:#64748B; font-weight:600; text-transform:uppercase; letter-spacing:.5px; margin-bottom:6px; }
    .stat-value { font-size:24px; font-weight:800; color:#0F172A; }
    .mission-card { background:#fff; border:1px solid #E2E8F0; border-radius:14px; padding:20px; margin-bottom:14px; display:flex; align-items:center; gap:16px; }
    .mission-icon { width:48px; height:48px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:22px; flex-shrink:0; }
    .mission-info { flex:1; }
    .mission-title { font-size:14px; font-weight:700; color:#0F172A; margin-bottom:2px; }
    .mission-meta { font-size:12px; color:#64748B; }
    .mission-stats { display:flex; gap:20px; text-align:center; }
    .mission-stat-value { font-size:18px; font-weight:800; color:#0F172A; }
    .mission-stat-label { font-size:10px; color:#94A3B8; font-weight:600; text-transform:uppercase; }
    .badge-reward { background:#EFF6FF; color:#1E40AF; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:700; }
    .badge-xp { background:#FEF3C7; color:#92400E; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:700; }
    .status-badge { display:inline-block; padding:4px 12px; border-radius:20px; font-size:11px; font-weight:700; }
    .status-enabled { background:#DCFCE7; color:#15803D; }
    .status-disabled { background:#FEE2E2; color:#B91C1C; }
</style>
@endpush

@section('content')
    <div class="page-header" style="display:flex;justify-content:space-between;align-items:center;">
        <h1 class="page-title">Missions quotidiennes</h1>
        <span class="status-badge {{ $isEnabled ? 'status-enabled' : 'status-disabled' }}">
            {{ $isEnabled ? 'Activees' : 'Desactivees' }}
        </span>
    </div>

    @if(!$isEnabled)
    <div style="background:#FFF7ED;border:1px solid #FED7AA;border-radius:10px;padding:12px 18px;margin-bottom:20px;font-size:13px;color:#92400E;font-weight:600;">
        Les missions sont desactivees. Activez-les depuis la page <a href="{{ route('panel.admin.features') }}" style="color:#1E40AF;text-decoration:underline">Fonctionnalites</a>.
    </div>
    @endif

    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-label">Completions aujourd'hui</div>
            <div class="stat-value" style="color:#15803D">{{ $todayCompletions }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Completions totales</div>
            <div class="stat-value">{{ number_format($totalCompletions) }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Recompenses distribuees</div>
            <div class="stat-value">{{ number_format($totalRewards) }}</div>
        </div>
    </div>

    <div style="background:#fff;border:1px solid #E2E8F0;border-radius:16px;padding:20px">
        <h3 style="font-size:15px;font-weight:700;color:#0F172A;margin-bottom:16px">Missions configurees</h3>

        @forelse($missionStats as $mission)
        <div class="mission-card">
            <div class="mission-icon" style="background:{{ ['views'=>'#EFF6FF','checkin'=>'#F0FDF4','referral'=>'#FEF3C7','survey'=>'#FDF2F8'][$mission['type']] ?? '#F1F5F9' }}">
                {!! ['views'=>'&#128249;','checkin'=>'&#9989;','referral'=>'&#128101;','survey'=>'&#128203;'][$mission['type']] ?? '&#127919;' !!}
            </div>
            <div class="mission-info">
                <div class="mission-title">{{ $mission['title'] }}</div>
                <div class="mission-meta">
                    Type : <strong>{{ $mission['type'] }}</strong> &middot; Objectif : <strong>{{ $mission['target'] }}</strong>
                    &middot; <span class="badge-reward">{{ $mission['reward_fcfa'] }} F</span>
                    <span class="badge-xp">+{{ $mission['reward_xp'] }} XP</span>
                </div>
            </div>
            <div class="mission-stats">
                <div>
                    <div class="mission-stat-value">{{ $mission['completions_today'] }}</div>
                    <div class="mission-stat-label">Aujourd'hui</div>
                </div>
                <div>
                    <div class="mission-stat-value">{{ $mission['completions_total'] }}</div>
                    <div class="mission-stat-label">Total</div>
                </div>
                <div>
                    <div class="mission-stat-value">{{ $mission['rewards_total'] }}</div>
                    <div class="mission-stat-label">Recompenses</div>
                </div>
            </div>
        </div>
        @empty
        <div style="text-align:center;padding:40px;color:#94A3B8;font-size:13px">
            Aucune mission configuree. Modifiez la configuration depuis la page <a href="{{ route('panel.admin.features') }}" style="color:#1E40AF">Fonctionnalites</a>.
        </div>
        @endforelse
    </div>

    <div style="margin-top:16px;font-size:12px;color:#94A3B8;text-align:center">
        Les missions sont configurees depuis <a href="{{ route('panel.admin.features') }}" style="color:#1E40AF">Fonctionnalites</a> &rarr; Missions quotidiennes &rarr; Configurer
    </div>
@endsection
