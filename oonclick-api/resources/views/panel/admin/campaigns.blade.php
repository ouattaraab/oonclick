@extends('layouts.panel', ['panelLabel' => 'Admin'])

@section('title', 'Campagnes')

@section('sidebar-nav')
    @include('panel.admin._sidebar', ['active' => 'campaigns'])
@endsection

@section('breadcrumb')
    <a href="{{ route('panel.admin.dashboard') }}">Admin</a> &rsaquo; <span class="current">Campagnes</span>
@endsection

@section('content')
    <div class="page-header">
        <h1 class="page-title">Campagnes</h1>
    </div>

    <div class="kpi-grid">
        <div class="kpi-card"><div class="accent sky"></div><div class="kpi-header"><div class="kpi-icon sky">&#128202;</div><span class="kpi-label">Total campagnes</span></div><div class="kpi-value">{{ $totalCampaigns }}</div><div class="kpi-change neutral">Toutes campagnes confondues</div></div>
        <div class="kpi-card"><div class="accent amber"></div><div class="kpi-header"><div class="kpi-icon amber">&#9200;</div><span class="kpi-label">En attente de validation</span></div><div class="kpi-value">{{ $pendingCampaigns }}</div><div class="kpi-change neutral">À modérer</div></div>
        <div class="kpi-card"><div class="accent green"></div><div class="kpi-header"><div class="kpi-icon green">&#9889;</div><span class="kpi-label">Campagnes actives</span></div><div class="kpi-value">{{ $activeCampaigns }}</div><div class="kpi-change neutral">En diffusion</div></div>
        <div class="kpi-card"><div class="accent purple"></div><div class="kpi-header"><div class="kpi-icon purple">&#128176;</div><span class="kpi-label">Budget total engagé</span></div><div class="kpi-value">{{ number_format($totalBudget, 0, ',', ' ') }} F</div><div class="kpi-change neutral">Somme de tous les budgets</div></div>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="card-title">Toutes les campagnes</div>
        </div>
        @if($campaigns->count() > 0)
        <table>
            <thead>
                <tr><th>Annonceur</th><th>Campagne</th><th>Format</th><th>Budget</th><th>Statut</th><th>Créé le</th><th>Actions</th></tr>
            </thead>
            <tbody>
                @foreach($campaigns as $campaign)
                <tr>
                    <td>
                        <div class="user-cell">
                            <div class="user-avatar" style="background:{{ ['#0EA5E9','#F59E0B','#8B5CF6','#22C55E','#EF4444'][$loop->index % 5] }}">{{ strtoupper(substr($campaign->advertiser->name ?? 'A', 0, 1)) }}</div>
                            <div class="user-name">{{ Str::limit($campaign->advertiser->name ?? '—', 20) }}</div>
                        </div>
                    </td>
                    <td style="font-weight:600;color:#0F172A">{{ Str::limit($campaign->title, 30) }}</td>
                    <td><span class="badge badge-info">{{ ucfirst($campaign->format) }}</span></td>
                    <td style="font-weight:700">{{ number_format($campaign->budget, 0, ',', ' ') }} F</td>
                    <td><span class="badge {{ $campaign->status === 'active' ? 'badge-active' : ($campaign->status === 'pending_review' ? 'badge-pending' : 'badge-gray') }}">{{ $campaign->status === 'active' ? 'Actif' : ($campaign->status === 'pending_review' ? 'En attente' : ucfirst($campaign->status)) }}</span></td>
                    <td style="color:#94A3B8;font-size:12px">{{ $campaign->created_at->format('d/m/Y') }}</td>
                    <td>
                        @if($campaign->status === 'pending_review' || $campaign->status === 'pending' || $campaign->status === 'draft')
                        <form method="POST" action="{{ route('panel.admin.campaigns.approve', $campaign) }}" style="display:inline">@csrf<button type="submit" class="action-btn success btn-sm" onclick="return confirm('Approuver cette campagne ?')">Approuver</button></form>
                        <form method="POST" action="{{ route('panel.admin.campaigns.reject', $campaign) }}" style="display:inline">@csrf<button type="submit" class="action-btn danger btn-sm" onclick="return confirm('Rejeter cette campagne ?')">Rejeter</button></form>
                        @elseif($campaign->status === 'active')
                        <form method="POST" action="{{ route('panel.admin.campaigns.pause', $campaign) }}" style="display:inline">@csrf<button type="submit" class="action-btn warning btn-sm" onclick="return confirm('Mettre cette campagne en pause ?')">Pause</button></form>
                        @elseif($campaign->status === 'paused')
                        <form method="POST" action="{{ route('panel.admin.campaigns.resume', $campaign) }}" style="display:inline">@csrf<button type="submit" class="action-btn success btn-sm" onclick="return confirm('Relancer cette campagne ?')">Reprendre</button></form>
                        @endif
                        <a href="{{ route('panel.admin.campaigns.show', $campaign) }}" class="action-btn primary btn-sm" style="text-decoration:none">Détails</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div class="pagination">
            <span>Affichage de {{ $campaigns->firstItem() }} à {{ $campaigns->lastItem() }} sur {{ $campaigns->total() }}</span>
            <div>{{ $campaigns->links('pagination::simple-default') }}</div>
        </div>
        @else
        <div class="empty-state"><div class="icon">&#128226;</div><p>Aucune campagne</p></div>
        @endif
    </div>
@endsection
