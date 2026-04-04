@extends('layouts.panel-advertiser', ['walletBalance' => $walletBalance])

@section('title', 'Mes campagnes')
@section('topbar-title', 'Mes campagnes')
@section('topbar-actions')
    <a href="{{ route('panel.advertiser.campaigns.create') }}" class="btn-new">+ Nouvelle campagne</a>
@endsection

@section('sidebar-nav')
    @include('panel.advertiser._sidebar', ['active' => 'campaigns'])
@endsection

@section('content')
    <div class="card">
        <div class="card-head">
            <div class="card-title">Campagnes</div>
            <div class="tab-bar">
                <button class="tab active">Toutes</button>
                <button class="tab">Actives</button>
                <button class="tab">En attente</button>
            </div>
        </div>
        @if($campaigns->count() > 0)
        <table>
            <thead><tr><th>Campagne</th><th>Format</th><th>Budget</th><th>Progression</th><th>Statut</th><th></th></tr></thead>
            <tbody>
                @foreach($campaigns as $campaign)
                <tr>
                    <td style="font-weight:700;color:#0B1929">{{ $campaign->title }}</td>
                    <td><span class="badge {{ $campaign->format === 'video' ? 'badge-video' : ($campaign->format === 'flash' ? 'badge-flash' : 'badge-gray') }}">{{ ucfirst($campaign->format) }}</span></td>
                    <td style="font-weight:800">{{ number_format($campaign->budget, 0, ',', ' ') }} F</td>
                    <td>
                        @php $pct = $campaign->budget > 0 ? round(($campaign->views_count * $campaign->cost_per_view) / $campaign->budget * 100) : 0; @endphp
                        <div class="progress-cell">
                            <div class="mini-bar"><div class="mini-fill" style="width:{{ $pct }}%"></div></div>
                            <div class="pct">{{ $pct }}%</div>
                        </div>
                    </td>
                    <td><span class="badge {{ $campaign->status === 'active' ? 'badge-active' : ($campaign->status === 'pending_review' ? 'badge-pending' : 'badge-gray') }}">{{ $campaign->status === 'active' ? 'Actif' : ($campaign->status === 'pending_review' ? 'En attente' : ucfirst($campaign->status)) }}</span></td>
                    <td style="display:flex;gap:10px;align-items:center">
                        <a class="action-link" href="{{ route('panel.advertiser.campaigns.show', $campaign) }}">Détails →</a>
                        {{-- Bouton Dupliquer (US-025) --}}
                        <form method="POST" action="{{ route('panel.advertiser.campaigns.duplicate', $campaign) }}"
                              onsubmit="return confirm('Dupliquer cette campagne ?')" style="margin:0">
                            @csrf
                            <button type="submit"
                                    style="background:none;border:none;cursor:pointer;font-size:12px;font-weight:700;color:#6366F1;padding:0;text-decoration:underline">
                                Dupliquer
                            </button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @if($campaigns->hasPages())
        <div class="pagination">
            <span>{{ $campaigns->firstItem() }} à {{ $campaigns->lastItem() }} sur {{ $campaigns->total() }}</span>
        </div>
        @endif
        @else
        <div class="empty-state"><p>Aucune campagne pour le moment</p></div>
        @endif
    </div>
@endsection
