@extends('layouts.panel', ['panelLabel' => 'Admin'])

@section('title', 'Utilisateur — ' . ($user->name ?? $user->phone))

@section('sidebar-nav')
    @include('panel.admin._sidebar', ['active' => 'users'])
@endsection

@section('breadcrumb')
    <a href="{{ route('panel.admin.dashboard') }}">Admin</a> &rsaquo;
    <a href="{{ route('panel.admin.users') }}">Utilisateurs</a> &rsaquo;
    <span class="current">{{ $user->name ?? $user->phone }}</span>
@endsection

@section('content')
    <div class="page-header">
        <h1 class="page-title">{{ $user->name ?? $user->phone }}</h1>
        <div style="display:flex;gap:8px;align-items:center">
            @if($user->is_suspended)
                <form method="POST" action="{{ route('panel.admin.users.unsuspend', $user) }}" style="display:inline">
                    @csrf
                    <button type="submit" class="btn-primary">&#10003; Réactiver</button>
                </form>
            @elseif($user->role !== 'admin')
                <form method="POST" action="{{ route('panel.admin.users.suspend', $user) }}" style="display:inline">
                    @csrf
                    <button type="submit" class="btn-danger" onclick="return confirm('Suspendre cet utilisateur ?')">Suspendre</button>
                </form>
            @endif
            <a href="{{ route('panel.admin.users') }}" class="btn-secondary">&#8592; Retour</a>
        </div>
    </div>

    @if(session('success'))
    <div style="background:#DCFCE7;border:1px solid #86EFAC;color:#166534;padding:12px 16px;border-radius:10px;margin-bottom:20px;font-size:13px;font-weight:600">
        {{ session('success') }}
    </div>
    @endif

    <div style="display:grid;grid-template-columns:2fr 1fr;gap:20px">

        {{-- LEFT COLUMN --}}
        <div>

            {{-- Identity --}}
            <div class="card" style="margin-bottom:20px">
                <div class="card-header">
                    <div class="card-title">Identité</div>
                    <div style="display:flex;gap:6px;align-items:center">
                        @if($user->is_suspended)
                            <span class="badge badge-danger">Suspendu</span>
                        @elseif($user->is_active)
                            <span class="badge badge-active">Actif</span>
                        @else
                            <span class="badge badge-gray">Inactif</span>
                        @endif
                        <span class="badge {{ $user->role === 'admin' ? 'badge-admin' : ($user->role === 'advertiser' ? 'badge-adv' : 'badge-sub') }}">
                            {{ $user->role === 'admin' ? 'Admin' : ($user->role === 'advertiser' ? 'Annonceur' : 'Abonné') }}
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <div style="display:flex;align-items:center;gap:14px;margin-bottom:20px">
                        <div class="user-avatar" style="width:52px;height:52px;font-size:20px;background:{{ $user->role === 'admin' ? 'linear-gradient(135deg,#0F172A,#334155)' : ($user->role === 'advertiser' ? 'linear-gradient(135deg,#F59E0B,#D97706)' : 'linear-gradient(135deg,#0EA5E9,#0284C7)') }}">
                            {{ strtoupper(substr($user->name ?? $user->phone ?? 'U', 0, 1)) }}{{ strtoupper(substr(explode(' ', $user->name ?? '')[1] ?? '', 0, 1)) }}
                        </div>
                        <div>
                            <div style="font-weight:800;color:#0F172A;font-size:18px">{{ $user->name ?? '—' }}</div>
                            <div style="font-size:13px;color:#64748B;font-weight:500">{{ $user->phone }}</div>
                        </div>
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
                        <div>
                            <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.8px;color:#94A3B8;margin-bottom:4px">Email</div>
                            <div style="font-size:13px;font-weight:600;color:#334155">{{ $user->email ?? '—' }}</div>
                        </div>
                        <div>
                            <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.8px;color:#94A3B8;margin-bottom:4px">Niveau KYC</div>
                            <div style="font-size:13px;font-weight:700;color:#0F172A">Niveau {{ $user->kyc_level }}</div>
                        </div>
                        <div>
                            <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.8px;color:#94A3B8;margin-bottom:4px">Score de confiance</div>
                            <div class="trust-bar" style="margin-top:4px">
                                <div class="trust-track" style="width:80px">
                                    <div class="trust-fill {{ $user->trust_score >= 70 ? 'high' : ($user->trust_score >= 40 ? 'med' : 'low') }}" style="width:{{ $user->trust_score }}%"></div>
                                </div>
                                <span style="font-size:14px;font-weight:800;color:{{ $user->trust_score >= 70 ? '#22C55E' : ($user->trust_score >= 40 ? '#F59E0B' : '#EF4444') }}">{{ $user->trust_score }}</span>
                            </div>
                        </div>
                        <div>
                            <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.8px;color:#94A3B8;margin-bottom:4px">Inscrit le</div>
                            <div style="font-size:13px;font-weight:600;color:#334155">{{ $user->created_at->format('d/m/Y à H:i') }}</div>
                        </div>
                        @if($user->roles->count() > 0)
                        <div style="grid-column:span 2">
                            <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.8px;color:#94A3B8;margin-bottom:6px">Rôles Spatie</div>
                            <div style="display:flex;gap:6px;flex-wrap:wrap">
                                @foreach($user->roles as $role)
                                    <span class="badge badge-info">{{ $role->name }}</span>
                                @endforeach
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Subscriber profile --}}
            @if($user->role === 'subscriber' && $user->profile)
            <div class="card" style="margin-bottom:20px">
                <div class="card-header">
                    <div class="card-title">Profil abonné</div>
                </div>
                <div class="card-body">
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
                        <div>
                            <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.8px;color:#94A3B8;margin-bottom:4px">Prénom</div>
                            <div style="font-size:13px;font-weight:600;color:#334155">{{ $user->profile->first_name ?? '—' }}</div>
                        </div>
                        <div>
                            <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.8px;color:#94A3B8;margin-bottom:4px">Nom</div>
                            <div style="font-size:13px;font-weight:600;color:#334155">{{ $user->profile->last_name ?? '—' }}</div>
                        </div>
                        <div>
                            <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.8px;color:#94A3B8;margin-bottom:4px">Ville</div>
                            <div style="font-size:13px;font-weight:600;color:#334155">{{ $user->profile->city ?? '—' }}</div>
                        </div>
                        <div>
                            <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.8px;color:#94A3B8;margin-bottom:4px">Opérateur</div>
                            <div style="font-size:13px;font-weight:600;color:#334155">{{ $user->profile->operator ?? '—' }}</div>
                        </div>
                        @if($user->profile->gender)
                        <div>
                            <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.8px;color:#94A3B8;margin-bottom:4px">Genre</div>
                            <div style="font-size:13px;font-weight:600;color:#334155">{{ ucfirst($user->profile->gender) }}</div>
                        </div>
                        @endif
                        @if($user->profile->date_of_birth)
                        <div>
                            <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.8px;color:#94A3B8;margin-bottom:4px">Date de naissance</div>
                            <div style="font-size:13px;font-weight:600;color:#334155">{{ \Carbon\Carbon::parse($user->profile->date_of_birth)->format('d/m/Y') }}</div>
                        </div>
                        @endif
                        @if($user->profile->referral_code)
                        <div>
                            <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.8px;color:#94A3B8;margin-bottom:4px">Code parrainage</div>
                            <code style="font-size:13px;font-weight:700;color:#0EA5E9;background:#EFF6FF;padding:2px 8px;border-radius:6px">{{ $user->profile->referral_code }}</code>
                        </div>
                        @endif
                        @if(!empty($user->profile->interests))
                        <div style="grid-column:span 2">
                            <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.8px;color:#94A3B8;margin-bottom:6px">Centres d'intérêt</div>
                            <div style="display:flex;gap:6px;flex-wrap:wrap">
                                @foreach((is_array($user->profile->interests) ? $user->profile->interests : json_decode($user->profile->interests, true) ?? []) as $interest)
                                    <span class="badge badge-info" style="font-size:11px">{{ $interest }}</span>
                                @endforeach
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            @endif

            {{-- Recent transactions --}}
            @if($transactions->count() > 0)
            <div class="card">
                <div class="card-header">
                    <div class="card-title">10 dernières transactions</div>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Montant</th>
                            <th>Solde après</th>
                            <th>Statut</th>
                            <th>Description</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($transactions as $tx)
                        <tr>
                            <td>
                                <span class="badge {{ in_array($tx->type, ['earn','credit']) ? 'badge-active' : 'badge-pending' }}" style="font-size:10px">
                                    {{ ucfirst($tx->type) }}
                                </span>
                            </td>
                            <td style="font-weight:700;color:{{ in_array($tx->type, ['earn','credit']) ? '#22C55E' : '#EF4444' }}">
                                {{ in_array($tx->type, ['earn','credit']) ? '+' : '-' }}{{ number_format($tx->amount, 0, ',', ' ') }} F
                            </td>
                            <td style="font-weight:600">{{ number_format($tx->balance_after, 0, ',', ' ') }} F</td>
                            <td>
                                <span class="badge {{ $tx->status === 'completed' ? 'badge-active' : ($tx->status === 'pending' ? 'badge-pending' : 'badge-danger') }}" style="font-size:10px">
                                    {{ ucfirst($tx->status) }}
                                </span>
                            </td>
                            <td style="color:#64748B;font-size:12px">{{ Str::limit($tx->description ?? $tx->reference ?? '—', 30) }}</td>
                            <td style="color:#94A3B8;font-size:12px">{{ $tx->created_at->format('d/m/Y H:i') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif

        </div>

        {{-- RIGHT COLUMN --}}
        <div>

            {{-- Wallet --}}
            <div class="card" style="margin-bottom:20px">
                <div class="card-header">
                    <div class="card-title">Portefeuille</div>
                </div>
                <div class="card-body">
                    @if($user->wallet)
                    <div style="display:flex;flex-direction:column;gap:12px">
                        <div style="padding:14px;background:linear-gradient(135deg,#0EA5E9,#0284C7);border-radius:12px;color:#fff">
                            <div style="font-size:10px;font-weight:700;opacity:0.8;text-transform:uppercase;letter-spacing:0.5px">Solde disponible</div>
                            <div style="font-size:24px;font-weight:900;margin-top:4px">{{ number_format($user->wallet->balance, 0, ',', ' ') }} <span style="font-size:14px;font-weight:600;opacity:0.8">F</span></div>
                        </div>
                        @if(isset($user->wallet->pending_balance) && $user->wallet->pending_balance > 0)
                        <div style="padding:12px;background:#FFF7ED;border-radius:10px;border:1px solid #FED7AA">
                            <div style="font-size:10px;font-weight:700;color:#C2410C;text-transform:uppercase;letter-spacing:0.5px">En attente</div>
                            <div style="font-size:18px;font-weight:800;color:#C2410C">{{ number_format($user->wallet->pending_balance, 0, ',', ' ') }} F</div>
                        </div>
                        @endif
                        <div style="padding:12px;background:#F0FDF4;border-radius:10px;border:1px solid #BBF7D0">
                            <div style="font-size:10px;font-weight:700;color:#15803D;text-transform:uppercase;letter-spacing:0.5px">Total gagné</div>
                            <div style="font-size:18px;font-weight:800;color:#15803D">{{ number_format($user->wallet->total_earned, 0, ',', ' ') }} F</div>
                        </div>
                        <div style="padding:12px;background:#F8FAFC;border-radius:10px;border:1px solid #E2E8F0">
                            <div style="font-size:10px;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:0.5px">Total retiré</div>
                            <div style="font-size:18px;font-weight:800;color:#475569">{{ number_format($user->wallet->total_withdrawn, 0, ',', ' ') }} F</div>
                        </div>
                    </div>
                    @else
                    <div style="text-align:center;padding:24px;color:#94A3B8">
                        <div style="font-size:32px;margin-bottom:8px;opacity:0.3">&#128181;</div>
                        <div style="font-size:13px;font-weight:500">Aucun portefeuille associé</div>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Account flags --}}
            <div class="card">
                <div class="card-header">
                    <div class="card-title">Flags du compte</div>
                </div>
                <div class="card-body">
                    <div style="display:flex;flex-direction:column;gap:10px">
                        <div style="display:flex;justify-content:space-between;align-items:center;padding:10px;background:#F8FAFC;border-radius:8px">
                            <span style="font-size:13px;font-weight:600;color:#334155">Compte actif</span>
                            <span class="badge {{ $user->is_active ? 'badge-active' : 'badge-gray' }}">{{ $user->is_active ? 'Oui' : 'Non' }}</span>
                        </div>
                        <div style="display:flex;justify-content:space-between;align-items:center;padding:10px;background:#F8FAFC;border-radius:8px">
                            <span style="font-size:13px;font-weight:600;color:#334155">Suspendu</span>
                            <span class="badge {{ $user->is_suspended ? 'badge-danger' : 'badge-active' }}">{{ $user->is_suspended ? 'Oui' : 'Non' }}</span>
                        </div>
                        <div style="display:flex;justify-content:space-between;align-items:center;padding:10px;background:#F8FAFC;border-radius:8px">
                            <span style="font-size:13px;font-weight:600;color:#334155">Rôle</span>
                            <span class="badge {{ $user->role === 'admin' ? 'badge-admin' : ($user->role === 'advertiser' ? 'badge-adv' : 'badge-sub') }}">
                                {{ $user->role === 'admin' ? 'Admin' : ($user->role === 'advertiser' ? 'Annonceur' : 'Abonné') }}
                            </span>
                        </div>
                        <div style="display:flex;justify-content:space-between;align-items:center;padding:10px;background:#F8FAFC;border-radius:8px">
                            <span style="font-size:13px;font-weight:600;color:#334155">KYC</span>
                            <span class="badge badge-info">Niveau {{ $user->kyc_level }}</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection
