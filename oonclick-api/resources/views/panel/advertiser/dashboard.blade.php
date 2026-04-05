@extends('layouts.panel-advertiser', ['walletBalance' => $walletBalance])

@section('title', 'Tableau de bord')
@section('topbar-title', 'Tableau de bord')
@section('topbar-actions')
    <a href="{{ route('panel.advertiser.campaigns.create') }}" class="btn-new">+ Nouvelle campagne</a>
@endsection

@section('sidebar-nav')
    @include('panel.advertiser._sidebar', ['active' => 'dashboard'])
@endsection

@section('content')
    <div class="hero-stats">
        <div class="hero-stat blue">
            <div class="hs-label">Vues totales</div>
            <div class="hs-value">{{ number_format($totalViews, 0, ',', ' ') }}</div>
            <div class="hs-sub">{{ $viewsPct >= 0 ? '+' : '' }}{{ $viewsPct }}% ce mois</div>
        </div>
        <div class="hero-stat cyan">
            <div class="hs-label">Taux complétion</div>
            <div class="hs-value">{{ $completionRate }}%</div>
            <div class="hs-sub">{{ $completionDelta >= 0 ? '+' : '' }}{{ $completionDelta }} pts</div>
        </div>
        <div class="hero-stat emerald">
            <div class="hs-label">FCFA dépensés</div>
            <div class="hs-value">{{ $spentFormatted }}</div>
            <div class="hs-sub">Sur {{ $budgetFormatted }} budget</div>
        </div>
        <div class="hero-stat violet">
            <div class="hs-label">Campagnes</div>
            <div class="hs-value">{{ $totalCampaigns }}</div>
            <div class="hs-sub">{{ $pendingCampaigns }} en attente</div>
        </div>
    </div>

    <div class="card">
        <div class="card-head">
            <div class="card-title">Vues par jour — 7 derniers jours</div>
        </div>
        <div class="chart-container">
            <canvas id="viewsChart"></canvas>
        </div>
    </div>

    <div class="card">
        <div class="card-head">
            <div class="card-title">Mes campagnes</div>
            <a class="card-link" href="{{ route('panel.advertiser.campaigns') }}">Voir toutes →</a>
        </div>
        @if($campaigns->count() > 0)
        <table>
            <thead><tr><th>Campagne</th><th>Format</th><th>Budget</th><th>Progression</th><th>Statut</th><th></th></tr></thead>
            <tbody>
                @foreach($campaigns as $campaign)
                <tr>
                    <td style="font-weight:700;color:#0B1929">{{ Str::limit($campaign->title, 30) }}</td>
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
                    <td><a class="action-link" href="{{ route('panel.advertiser.campaigns.show', $campaign) }}">Détails →</a></td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <div class="empty-state"><p>Aucune campagne. Créez votre première campagne !</p></div>
        @endif
    </div>
@endsection

@push('styles')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('viewsChart').getContext('2d');
    const gradient = ctx.createLinearGradient(0, 0, 0, 250);
    gradient.addColorStop(0, 'rgba(59,130,246,0.15)');
    gradient.addColorStop(1, 'rgba(59,130,246,0)');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: @json($chartLabels),
            datasets: [{
                label: 'Vues', data: @json($chartData),
                borderColor: '#3B82F6', backgroundColor: gradient, borderWidth: 2.5,
                fill: true, tension: 0.4, pointBackgroundColor: '#3B82F6',
                pointBorderColor: '#fff', pointBorderWidth: 2, pointRadius: 4,
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { color: '#F1F5F9' }, ticks: { font: { size: 11, family: 'Nunito' }, color: '#94A3B8' } },
                x: { grid: { display: false }, ticks: { font: { size: 11, family: 'Nunito' }, color: '#94A3B8' } }
            }
        }
    });
});
</script>
@endpush
