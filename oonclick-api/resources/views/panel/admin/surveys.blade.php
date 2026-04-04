@extends('layouts.panel', ['panelLabel' => 'Admin'])

@section('title', 'Sondages rémunérés')

@section('sidebar-nav')
    @include('panel.admin._sidebar', ['active' => 'surveys'])
@endsection

@section('breadcrumb')
    <a href="{{ route('panel.admin.dashboard') }}">Admin</a> &rsaquo; <span class="current">Sondages</span>
@endsection

@push('styles')
<style>
    .stats-row { display:flex; gap:14px; margin-bottom:24px; flex-wrap:wrap; }
    .stat-card { flex:1; min-width:140px; background:#fff; border:1px solid #E2E8F0; border-radius:12px; padding:16px 20px; }
    .stat-label { font-size:11px; color:#64748B; font-weight:600; text-transform:uppercase; letter-spacing:.5px; margin-bottom:6px; }
    .stat-value { font-size:24px; font-weight:800; color:#0F172A; }
    .badge-active { background:#DCFCE7; color:#15803D; padding:2px 10px; border-radius:20px; font-size:11px; font-weight:700; }
    .badge-inactive { background:#FEE2E2; color:#B91C1C; padding:2px 10px; border-radius:20px; font-size:11px; font-weight:700; }
    .toggle-form { display:inline; margin:0; }
    .toggle-btn { padding:4px 12px; border-radius:6px; font-size:11px; font-weight:600; border:none; cursor:pointer; font-family:inherit; }
    .toggle-btn.enabled  { background:#DCFCE7; color:#15803D; }
    .toggle-btn.disabled { background:#FEE2E2; color:#B91C1C; }
</style>
@endpush

@section('content')
    <div class="page-header" style="display:flex;justify-content:space-between;align-items:center;">
        <h1 class="page-title">Sondages rémunérés</h1>
        <a href="{{ route('panel.admin.surveys.create') }}" style="background:linear-gradient(135deg,#2AABF0,#0E7AB8);color:#fff;padding:10px 20px;border-radius:10px;font-size:13px;font-weight:700;text-decoration:none;">
            + Créer un sondage
        </a>
    </div>

    @if(session('success'))
    <div style="background:#DCFCE7;color:#15803D;border:1px solid #BBF7D0;border-radius:10px;padding:12px 18px;margin-bottom:20px;font-size:13px;font-weight:600;">
        &#10003; {{ session('success') }}
    </div>
    @endif

    @if(session('error'))
    <div style="background:#FEE2E2;color:#B91C1C;border:1px solid #FECACA;border-radius:10px;padding:12px 18px;margin-bottom:20px;font-size:13px;font-weight:600;">
        &#9888; {{ session('error') }}
    </div>
    @endif

    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-label">Total sondages</div>
            <div class="stat-value">{{ $totalSurveys }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Actifs</div>
            <div class="stat-value" style="color:#15803D">{{ $activeSurveys }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Total réponses</div>
            <div class="stat-value">{{ number_format($totalResponses) }}</div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            @if($surveys->isEmpty())
            <div style="text-align:center;padding:40px;color:#94A3B8;">
                <div style="font-size:32px;margin-bottom:12px;">📋</div>
                <p style="font-weight:600;">Aucun sondage pour le moment.</p>
                <a href="{{ route('panel.admin.surveys.create') }}" style="color:#0EA5E9;font-weight:600;">Créer le premier sondage</a>
            </div>
            @else
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Titre</th>
                            <th>Récompense</th>
                            <th>XP</th>
                            <th>Réponses</th>
                            <th>Quota</th>
                            <th>Expiration</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($surveys as $survey)
                        <tr>
                            <td style="color:#94A3B8;font-size:12px;">{{ $survey->id }}</td>
                            <td>
                                <div style="font-weight:700;color:#0F172A;">{{ Str::limit($survey->title, 40) }}</div>
                                @if($survey->description)
                                <div style="font-size:11px;color:#64748B;">{{ Str::limit($survey->description, 60) }}</div>
                                @endif
                            </td>
                            <td>
                                <span style="font-weight:700;color:#D97706;">{{ number_format($survey->reward_amount) }} FCFA</span>
                            </td>
                            <td>
                                <span style="color:#7C3AED;font-weight:600;">+{{ $survey->reward_xp }} XP</span>
                            </td>
                            <td>{{ number_format($survey->responses_count) }}</td>
                            <td>{{ $survey->max_responses ? number_format($survey->max_responses) : '—' }}</td>
                            <td>{{ $survey->expires_at ? $survey->expires_at->format('d/m/Y') : '—' }}</td>
                            <td>
                                @if($survey->is_active)
                                    <span class="badge-active">Actif</span>
                                @else
                                    <span class="badge-inactive">Inactif</span>
                                @endif
                            </td>
                            <td>
                                <div style="display:flex;gap:8px;align-items:center;">
                                    <a href="{{ route('panel.admin.surveys.show', $survey) }}" style="color:#0EA5E9;font-size:12px;font-weight:600;">
                                        Réponses
                                    </a>
                                    <form action="{{ route('panel.admin.surveys.toggle', $survey) }}" method="POST" class="toggle-form">
                                        @csrf
                                        <button type="submit" class="toggle-btn {{ $survey->is_active ? 'enabled' : 'disabled' }}">
                                            {{ $survey->is_active ? 'Désactiver' : 'Activer' }}
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div style="margin-top:16px;">
                {{ $surveys->links() }}
            </div>
            @endif
        </div>
    </div>
@endsection
