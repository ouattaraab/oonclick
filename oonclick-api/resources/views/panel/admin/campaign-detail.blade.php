@extends('layouts.panel', ['panelLabel' => 'Admin'])

@section('title', 'Campagne — ' . $campaign->title)

@section('sidebar-nav')
    @include('panel.admin._sidebar', ['active' => 'campaigns'])
@endsection

@section('breadcrumb')
    <a href="{{ route('panel.admin.dashboard') }}">Admin</a> &rsaquo;
    <a href="{{ route('panel.admin.campaigns') }}">Campagnes</a> &rsaquo;
    <span class="current">{{ Str::limit($campaign->title, 40) }}</span>
@endsection

@section('content')
    <div class="page-header">
        <h1 class="page-title">{{ $campaign->title }}</h1>
        <div style="display:flex;gap:8px;align-items:center">
            @if($campaign->status === 'pending_review')
                <form method="POST" action="{{ route('panel.admin.campaigns.approve', $campaign) }}" style="display:inline">
                    @csrf
                    <button type="submit" class="btn-primary" onclick="return confirm('Approuver cette campagne ?')">
                        &#10003; Approuver
                    </button>
                </form>
                <form method="POST" action="{{ route('panel.admin.campaigns.reject', $campaign) }}" style="display:inline">
                    @csrf
                    <button type="submit" class="btn-danger" onclick="return confirm('Rejeter cette campagne ?')">
                        &#10007; Rejeter
                    </button>
                </form>
            @endif
            <a href="{{ route('panel.admin.campaigns') }}" class="btn-secondary">&#8592; Retour</a>
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

            {{-- Campaign Info --}}
            <div class="card" style="margin-bottom:20px">
                <div class="card-header">
                    <div class="card-title">Informations de la campagne</div>
                    <span class="badge {{ $campaign->status === 'active' ? 'badge-active' : ($campaign->status === 'pending_review' ? 'badge-pending' : ($campaign->status === 'rejected' ? 'badge-danger' : 'badge-gray')) }}">
                        {{ $campaign->status === 'active' ? 'Actif' : ($campaign->status === 'pending_review' ? 'En attente' : ($campaign->status === 'rejected' ? 'Rejeté' : ucfirst($campaign->status))) }}
                    </span>
                </div>
                <div class="card-body">

                    @if($campaign->description)
                    <div style="margin-bottom:20px;padding:14px;background:#F8FAFC;border-radius:10px;border-left:3px solid #0EA5E9">
                        <div style="font-size:11px;font-weight:700;color:#64748B;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:6px">Description</div>
                        <div style="font-size:13px;color:#334155;line-height:1.6">{{ $campaign->description }}</div>
                    </div>
                    @endif

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
                        <div>
                            <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.8px;color:#94A3B8;margin-bottom:4px">Format</div>
                            <span class="badge badge-info">{{ ucfirst($campaign->format) }}</span>
                        </div>
                        <div>
                            <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.8px;color:#94A3B8;margin-bottom:4px">Durée</div>
                            <div style="font-size:14px;font-weight:700;color:#0F172A">{{ $campaign->duration_seconds ?? '—' }} s</div>
                        </div>
                        <div>
                            <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.8px;color:#94A3B8;margin-bottom:4px">Budget total</div>
                            <div style="font-size:22px;font-weight:800;color:#0F172A">{{ number_format($campaign->budget, 0, ',', ' ') }} <span style="font-size:13px;color:#64748B;font-weight:600">FCFA</span></div>
                        </div>
                        <div>
                            <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.8px;color:#94A3B8;margin-bottom:4px">Coût par vue</div>
                            <div style="font-size:22px;font-weight:800;color:#0F172A">{{ number_format($campaign->cost_per_view, 0, ',', ' ') }} <span style="font-size:13px;color:#64748B;font-weight:600">FCFA</span></div>
                        </div>
                        <div>
                            <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.8px;color:#94A3B8;margin-bottom:4px">Vues max</div>
                            <div style="font-size:18px;font-weight:700;color:#0F172A">{{ number_format($campaign->max_views, 0, ',', ' ') }}</div>
                        </div>
                        <div>
                            <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.8px;color:#94A3B8;margin-bottom:4px">Vues réalisées</div>
                            <div style="font-size:18px;font-weight:700;color:#22C55E">{{ number_format($campaign->views_count, 0, ',', ' ') }}</div>
                        </div>
                    </div>

                    {{-- Progress bar --}}
                    @php
                        $pct = ($campaign->max_views > 0) ? round($campaign->views_count / $campaign->max_views * 100) : 0;
                    @endphp
                    <div style="margin-top:20px">
                        <div style="display:flex;justify-content:space-between;font-size:12px;font-weight:600;color:#64748B;margin-bottom:6px">
                            <span>Progression des vues</span>
                            <span>{{ $pct }}%</span>
                        </div>
                        <div style="height:8px;background:#E2E8F0;border-radius:4px;overflow:hidden">
                            <div style="height:100%;width:{{ $pct }}%;background:linear-gradient(90deg,#0EA5E9,#22C55E);border-radius:4px;transition:width .3s"></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Dates --}}
            <div class="card" style="margin-bottom:20px">
                <div class="card-header">
                    <div class="card-title">Calendrier</div>
                </div>
                <div class="card-body">
                    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px">
                        <div>
                            <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.8px;color:#94A3B8;margin-bottom:4px">Date de création</div>
                            <div style="font-size:14px;font-weight:600;color:#0F172A">{{ $campaign->created_at->format('d/m/Y') }}</div>
                            <div style="font-size:11px;color:#94A3B8">{{ $campaign->created_at->format('H:i') }}</div>
                        </div>
                        <div>
                            <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.8px;color:#94A3B8;margin-bottom:4px">Début</div>
                            <div style="font-size:14px;font-weight:600;color:#0F172A">{{ $campaign->starts_at ? $campaign->starts_at->format('d/m/Y') : '—' }}</div>
                            @if($campaign->starts_at)<div style="font-size:11px;color:#94A3B8">{{ $campaign->starts_at->format('H:i') }}</div>@endif
                        </div>
                        <div>
                            <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.8px;color:#94A3B8;margin-bottom:4px">Fin</div>
                            <div style="font-size:14px;font-weight:600;color:#0F172A">{{ $campaign->ends_at ? $campaign->ends_at->format('d/m/Y') : '—' }}</div>
                            @if($campaign->ends_at)<div style="font-size:11px;color:#94A3B8">{{ $campaign->ends_at->format('H:i') }}</div>@endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Media --}}
            @if($campaign->media_url || $campaign->thumbnail_url)
            <div class="card">
                <div class="card-header">
                    <div class="card-title">Medias</div>
                </div>
                <div class="card-body">
                    @if($campaign->thumbnail_url)
                    <div style="margin-bottom:12px">
                        <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.8px;color:#94A3B8;margin-bottom:8px">Miniature</div>
                        <img src="{{ $campaign->thumbnail_url }}" alt="Miniature" style="max-width:100%;max-height:200px;border-radius:10px;border:1px solid #E2E8F0;object-fit:cover">
                    </div>
                    @endif
                    @if($campaign->media_url)
                    <div>
                        <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.8px;color:#94A3B8;margin-bottom:8px">URL du media</div>
                        <a href="{{ $campaign->media_url }}" target="_blank" style="font-size:12px;color:#0EA5E9;word-break:break-all;font-weight:600">{{ $campaign->media_url }}</a>
                    </div>
                    @endif
                </div>
            </div>
            @endif

        </div>

        {{-- RIGHT COLUMN --}}
        <div>

            {{-- Advertiser --}}
            <div class="card" style="margin-bottom:20px">
                <div class="card-header">
                    <div class="card-title">Annonceur</div>
                </div>
                <div class="card-body">
                    @if($campaign->advertiser)
                    <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px">
                        <div class="user-avatar" style="width:44px;height:44px;background:linear-gradient(135deg,#F59E0B,#D97706);font-size:16px">
                            {{ strtoupper(substr($campaign->advertiser->name ?? 'A', 0, 1)) }}
                        </div>
                        <div>
                            <div style="font-weight:700;color:#0F172A;font-size:14px">{{ $campaign->advertiser->name ?? '—' }}</div>
                            <div style="font-size:11px;color:#94A3B8">{{ $campaign->advertiser->phone }}</div>
                        </div>
                    </div>
                    <div style="display:flex;flex-direction:column;gap:10px">
                        <div>
                            <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.8px;color:#94A3B8;margin-bottom:3px">Email</div>
                            <div style="font-size:13px;color:#334155;font-weight:600">{{ $campaign->advertiser->email ?? '—' }}</div>
                        </div>
                        <div>
                            <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.8px;color:#94A3B8;margin-bottom:3px">Statut</div>
                            @if($campaign->advertiser->is_suspended)
                                <span class="badge badge-danger">Suspendu</span>
                            @elseif($campaign->advertiser->is_active)
                                <span class="badge badge-active">Actif</span>
                            @else
                                <span class="badge badge-gray">Inactif</span>
                            @endif
                        </div>
                        <div>
                            <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.8px;color:#94A3B8;margin-bottom:3px">Inscrit le</div>
                            <div style="font-size:13px;color:#334155;font-weight:600">{{ $campaign->advertiser->created_at->format('d/m/Y') }}</div>
                        </div>
                        <div style="margin-top:4px">
                            <a href="{{ route('panel.admin.users.show', $campaign->advertiser) }}" class="action-btn primary btn-sm" style="text-decoration:none;display:inline-block">Voir profil annonceur</a>
                        </div>
                    </div>
                    @else
                    <div style="color:#94A3B8;font-size:13px">Annonceur introuvable</div>
                    @endif
                </div>
            </div>

            {{-- KPI recap --}}
            <div class="card">
                <div class="card-header">
                    <div class="card-title">Recap financier</div>
                </div>
                <div class="card-body">
                    @php
                        $spent = $campaign->views_count * $campaign->cost_per_view;
                        $remaining = max(0, $campaign->budget - $spent);
                    @endphp
                    <div style="display:flex;flex-direction:column;gap:12px">
                        <div style="padding:12px;background:#F0FDF4;border-radius:10px;border:1px solid #BBF7D0">
                            <div style="font-size:10px;font-weight:700;color:#15803D;text-transform:uppercase;letter-spacing:0.5px">Budget total</div>
                            <div style="font-size:20px;font-weight:800;color:#15803D">{{ number_format($campaign->budget, 0, ',', ' ') }} F</div>
                        </div>
                        <div style="padding:12px;background:#FFF7ED;border-radius:10px;border:1px solid #FED7AA">
                            <div style="font-size:10px;font-weight:700;color:#C2410C;text-transform:uppercase;letter-spacing:0.5px">Dépensé</div>
                            <div style="font-size:20px;font-weight:800;color:#C2410C">{{ number_format($spent, 0, ',', ' ') }} F</div>
                        </div>
                        <div style="padding:12px;background:#EFF6FF;border-radius:10px;border:1px solid #BFDBFE">
                            <div style="font-size:10px;font-weight:700;color:#1D4ED8;text-transform:uppercase;letter-spacing:0.5px">Restant</div>
                            <div style="font-size:20px;font-weight:800;color:#1D4ED8">{{ number_format($remaining, 0, ',', ' ') }} F</div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection
