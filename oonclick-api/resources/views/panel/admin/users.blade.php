@extends('layouts.panel', ['panelLabel' => 'Admin'])

@section('title', 'Utilisateurs')

@section('sidebar-nav')
    @include('panel.admin._sidebar', ['active' => 'users'])
@endsection

@section('breadcrumb')
    <a href="{{ route('panel.admin.dashboard') }}">Admin</a> &rsaquo; <span class="current">Utilisateurs</span>
@endsection

@section('content')
    <div class="page-header">
        <h1 class="page-title">Utilisateurs</h1>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="card-title">{{ $users->total() }} utilisateurs</div>
            <div style="display:flex;gap:8px;align-items:center;">
                <input class="topbar-search" placeholder="Rechercher..." style="width:180px" />
            </div>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Utilisateur</th>
                    <th>Rôle</th>
                    <th>KYC</th>
                    <th>Score confiance</th>
                    <th>Statut</th>
                    <th>Rôles</th>
                    <th>Inscrit le</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $user)
                <tr>
                    <td>
                        <div class="user-cell">
                            <div class="user-avatar" style="background:{{ $user->role === 'admin' ? 'linear-gradient(135deg,#0F172A,#334155)' : ($user->role === 'advertiser' ? 'linear-gradient(135deg,#F59E0B,#D97706)' : 'linear-gradient(135deg,#0EA5E9,#0284C7)') }}">
                                {{ strtoupper(substr($user->name ?? $user->phone ?? 'U', 0, 1)) }}{{ strtoupper(substr(explode(' ', $user->name ?? '')[1] ?? '', 0, 1)) }}
                            </div>
                            <div>
                                <div class="user-name">{{ $user->name ?? '—' }}</div>
                                <div class="user-sub">{{ $user->phone }}</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="badge {{ $user->role === 'admin' ? 'badge-admin' : ($user->role === 'advertiser' ? 'badge-adv' : 'badge-sub') }}">
                            {{ $user->role === 'admin' ? 'Admin' : ($user->role === 'advertiser' ? 'Annonceur' : 'Abonné') }}
                        </span>
                    </td>
                    <td style="font-weight:600">Niv. {{ $user->kyc_level }}</td>
                    <td>
                        <div class="trust-bar">
                            <div class="trust-track">
                                <div class="trust-fill {{ $user->trust_score >= 70 ? 'high' : ($user->trust_score >= 40 ? 'med' : 'low') }}" style="width:{{ $user->trust_score }}%"></div>
                            </div>
                            <span style="font-size:12px;font-weight:700;color:{{ $user->trust_score >= 70 ? '#22C55E' : ($user->trust_score >= 40 ? '#F59E0B' : '#EF4444') }}">{{ $user->trust_score }}</span>
                        </div>
                    </td>
                    <td>
                        @if($user->is_suspended)
                            <span class="badge badge-danger">Suspendu</span>
                        @elseif($user->is_active)
                            <span class="badge badge-active">Actif</span>
                        @else
                            <span class="badge badge-gray">Inactif</span>
                        @endif
                    </td>
                    <td>
                        @if($user->roles->count() > 0)
                            @foreach($user->roles as $role)
                                <span class="badge badge-info" style="font-size:10px">{{ $role->name }}</span>
                            @endforeach
                        @else
                            <span style="color:#CBD5E1">—</span>
                        @endif
                    </td>
                    <td style="color:#94A3B8;font-size:12px">{{ $user->created_at->format('d/m/Y') }}</td>
                    <td>
                        <div style="display:flex;gap:4px;flex-wrap:nowrap;">
                            @if($user->is_suspended)
                            <form method="POST" action="{{ route('panel.admin.users.unsuspend', $user) }}" style="display:inline">@csrf<button type="submit" class="action-btn success btn-sm">Réactiver</button></form>
                            @elseif($user->role !== 'admin')
                            <form method="POST" action="{{ route('panel.admin.users.suspend', $user) }}" style="display:inline">@csrf<button type="submit" class="action-btn danger btn-sm" onclick="return confirm('Suspendre cet utilisateur ?')">Suspendre</button></form>
                            @endif
                            <a href="{{ route('panel.admin.users.show', $user) }}" class="action-btn primary btn-sm" style="text-decoration:none">Voir</a>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @if($users->hasPages())
        <div class="pagination">
            <span>Affichage de {{ $users->firstItem() }} à {{ $users->lastItem() }} sur {{ $users->total() }}</span>
            <div>{{ $users->links('pagination::simple-default') }}</div>
        </div>
        @endif
    </div>
@endsection
