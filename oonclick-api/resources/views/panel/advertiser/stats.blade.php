@extends('layouts.panel-advertiser', ['walletBalance' => $walletBalance])

@section('title', 'Statistiques')
@section('topbar-title', 'Statistiques')

@section('sidebar-nav')
    @include('panel.advertiser._sidebar', ['active' => 'stats'])
@endsection

@section('content')
    <div class="hero-stats">
        <div class="hero-stat blue">
            <div class="hs-label">Vues totales</div>
            <div class="hs-value">{{ number_format($totalViews, 0, ',', ' ') }}</div>
            <div class="hs-sub">Toutes campagnes confondues</div>
        </div>
        <div class="hero-stat emerald">
            <div class="hs-label">Budget total</div>
            <div class="hs-value">{{ number_format($totalBudget, 0, ',', ' ') }} F</div>
            <div class="hs-sub">Alloué sur {{ $totalCampaigns }} campagne(s)</div>
        </div>
        <div class="hero-stat amber">
            <div class="hs-label">FCFA dépensés</div>
            <div class="hs-value">{{ number_format($totalSpent, 0, ',', ' ') }} F</div>
            <div class="hs-sub">Sur le budget alloué</div>
        </div>
        <div class="hero-stat violet">
            <div class="hs-label">Campagnes actives</div>
            <div class="hs-value">{{ $activeCampaigns }}</div>
            <div class="hs-sub">Sur {{ $totalCampaigns }} au total</div>
        </div>
    </div>

    <div class="card">
        <div class="card-head">
            <div class="card-title">Vues par jour — 30 derniers jours</div>
        </div>
        <div class="chart-container">
            <canvas id="viewsChart"></canvas>
        </div>
    </div>

    <div class="card">
        <div class="card-head">
            <div class="card-title">Performance par campagne</div>
            <a class="card-link" href="{{ route('panel.advertiser.campaigns') }}">Voir toutes →</a>
        </div>
        @if($campaigns->count() > 0)
        <table>
            <thead>
                <tr>
                    <th>Campagne</th>
                    <th>Format</th>
                    <th>Statut</th>
                    <th>Vues</th>
                    <th>Budget</th>
                    <th>Dépensé</th>
                    <th>Progression</th>
                </tr>
            </thead>
            <tbody>
                @foreach($campaigns as $campaign)
                <tr>
                    <td style="font-weight:700;color:#0B1929">{{ Str::limit($campaign->title, 30) }}</td>
                    <td>
                        <span class="badge {{ $campaign->format === 'video' ? 'badge-video' : ($campaign->format === 'flash' ? 'badge-flash' : 'badge-gray') }}">
                            {{ ucfirst($campaign->format) }}
                        </span>
                    </td>
                    <td>
                        <span class="badge {{ $campaign->status === 'active' ? 'badge-active' : ($campaign->status === 'pending_review' ? 'badge-pending' : 'badge-gray') }}">
                            {{ $campaign->status === 'active' ? 'Actif' : ($campaign->status === 'pending_review' ? 'En attente' : ucfirst($campaign->status)) }}
                        </span>
                    </td>
                    <td style="font-weight:700">{{ number_format($campaign->views_count, 0, ',', ' ') }} / {{ number_format($campaign->max_views, 0, ',', ' ') }}</td>
                    <td style="font-weight:800">{{ number_format($campaign->budget, 0, ',', ' ') }} F</td>
                    <td style="font-weight:700">{{ number_format($campaign->spent, 0, ',', ' ') }} F</td>
                    <td>
                        <div class="progress-cell">
                            <div class="mini-bar"><div class="mini-fill" style="width:{{ $campaign->completion }}%"></div></div>
                            <div class="pct">{{ $campaign->completion }}%</div>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <div class="empty-state"><p>Aucune campagne trouvée. Créez votre première campagne pour voir les statistiques.</p></div>
        @endif
    </div>
@endsection

@push('styles')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const ctx = document.getElementById('viewsChart').getContext('2d');
    const gradient = ctx.createLinearGradient(0, 0, 0, 250);
    gradient.addColorStop(0, 'rgba(59,130,246,0.15)');
    gradient.addColorStop(1, 'rgba(59,130,246,0)');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: {!! json_encode($chartLabels) !!},
            datasets: [{
                label: 'Vues',
                data: {!! json_encode($chartData) !!},
                borderColor: '#3B82F6',
                backgroundColor: gradient,
                borderWidth: 2.5,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#3B82F6',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 4,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: '#F1F5F9' },
                    ticks: { font: { size: 11, family: 'Nunito' }, color: '#94A3B8' }
                },
                x: {
                    grid: { display: false },
                    ticks: { font: { size: 11, family: 'Nunito' }, color: '#94A3B8' }
                }
            }
        }
    });
});
</script>
@endpush
