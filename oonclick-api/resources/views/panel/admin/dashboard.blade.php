@extends('layouts.panel', ['panelLabel' => 'Admin'])

@section('title', 'Tableau de bord')

@section('sidebar-nav')
    @include('panel.admin._sidebar', ['active' => 'dashboard'])
@endsection

@section('breadcrumb')
    <span class="current">Tableau de bord</span>
@endsection

@section('content')
    <div class="page-header">
        <h1 class="page-title">Tableau de bord</h1>
        <a href="{{ route('panel.admin.campaigns') }}" class="btn-primary">+ Nouvelle campagne</a>
    </div>

    {{-- KPI CARDS --}}
    <div class="kpi-grid">
        <div class="kpi-card">
            <div class="accent sky"></div>
            <div class="kpi-header">
                <div class="kpi-icon sky">&#128101;</div>
                <span class="kpi-label">Abonnés actifs</span>
            </div>
            <div class="kpi-value">{{ number_format($activeSubscribers, 0, ',', ' ') }}</div>
            <div class="kpi-change {{ $subscribersDelta >= 0 ? '' : 'down' }}">
                {{ $subscribersDelta >= 0 ? '↗' : '↘' }}
                {{ ($subscribersDelta >= 0 ? '+' : '') . $subscribersDelta }} cette semaine
            </div>
        </div>
        <div class="kpi-card">
            <div class="accent green"></div>
            <div class="kpi-header">
                <div class="kpi-icon green">&#128226;</div>
                <span class="kpi-label">Campagnes actives</span>
            </div>
            <div class="kpi-value">{{ $activeCampaigns }}</div>
            <div class="kpi-change neutral">+{{ $pendingCampaigns }} en attente</div>
        </div>
        <div class="kpi-card">
            <div class="accent amber"></div>
            <div class="kpi-header">
                <div class="kpi-icon amber">&#128065;</div>
                <span class="kpi-label">Vues cette semaine</span>
            </div>
            <div class="kpi-value">{{ number_format($viewsThisWeek, 0, ',', ' ') }}</div>
            <div class="kpi-change {{ $viewsPct >= 0 ? '' : 'down' }}">
                {{ $viewsPct >= 0 ? '↗' : '↘' }}
                {{ ($viewsPct >= 0 ? '+' : '') . $viewsPct }}% vs semaine passée
            </div>
        </div>
        <div class="kpi-card">
            <div class="accent purple"></div>
            <div class="kpi-header">
                <div class="kpi-icon purple">&#128176;</div>
                <span class="kpi-label">Revenus FCFA</span>
            </div>
            <div class="kpi-value">{{ $revenueFormatted }}</div>
            <div class="kpi-change {{ $revenuePct >= 0 ? '' : 'down' }}">
                {{ $revenuePct >= 0 ? '↗' : '↘' }}
                {{ ($revenuePct >= 0 ? '+' : '') . $revenuePct }}% vs mois passé
            </div>
        </div>
    </div>

    {{-- GRID: Table + Activity --}}
    <div class="grid-2">
        {{-- Recent campaigns --}}
        <div class="card">
            <div class="card-header">
                <div class="card-title">Campagnes récentes</div>
                <a class="card-link" href="{{ route('panel.admin.campaigns') }}">Voir toutes &rarr;</a>
            </div>
            @if($recentCampaigns->count() > 0)
            <table>
                <thead>
                    <tr>
                        <th>Annonceur</th>
                        <th>Campagne</th>
                        <th>Format</th>
                        <th>Budget</th>
                        <th>Statut</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentCampaigns as $campaign)
                    <tr>
                        <td>
                            <div class="user-cell">
                                <div class="user-avatar" style="background:{{ ['#0EA5E9','#F59E0B','#8B5CF6','#22C55E','#EF4444'][$loop->index % 5] }}">
                                    {{ strtoupper(substr($campaign->advertiser->name ?? 'A', 0, 1)) }}{{ strtoupper(substr(explode(' ', $campaign->advertiser->name ?? 'U')[1] ?? '', 0, 1)) }}
                                </div>
                                <div>
                                    <div class="user-name">{{ Str::limit($campaign->advertiser->name ?? 'Inconnu', 20) }}</div>
                                </div>
                            </div>
                        </td>
                        <td style="font-weight:600;color:#0F172A">{{ Str::limit($campaign->title, 25) }}</td>
                        <td><span class="badge badge-info">{{ ucfirst($campaign->format) }}</span></td>
                        <td style="font-weight:700">{{ number_format($campaign->budget, 0, ',', ' ') }} F</td>
                        <td>
                            <span class="badge {{ $campaign->status === 'active' ? 'badge-active' : ($campaign->status === 'pending_review' ? 'badge-pending' : 'badge-gray') }}">
                                {{ $campaign->status === 'active' ? 'Actif' : ($campaign->status === 'pending_review' ? 'En attente' : ucfirst($campaign->status)) }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @else
            <div class="empty-state">
                <div class="icon">&#128226;</div>
                <p>Aucune campagne pour le moment</p>
            </div>
            @endif
        </div>

        {{-- Activity feed --}}
        <div class="card">
            <div class="card-header">
                <div class="card-title">Activité récente</div>
            </div>
            <div class="card-body">
                @forelse($recentActivity as $activity)
                <div class="activity-item">
                    <div class="activity-dot {{ $activity['color'] }}"></div>
                    <div>
                        <div class="activity-text">{{ $activity['text'] }}</div>
                        <div class="activity-time">{{ $activity['time'] }}</div>
                    </div>
                </div>
                @empty
                <div class="empty-state" style="padding:24px">
                    <p>Aucune activité récente</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Chart --}}
    <div class="card">
        <div class="card-header">
            <div class="card-title">Vues publicitaires — 7 derniers jours</div>
        </div>
        <div class="chart-container">
            <canvas id="viewsChart"></canvas>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    const ctx = document.getElementById('viewsChart').getContext('2d');
    const gradient = ctx.createLinearGradient(0, 0, 0, 250);
    gradient.addColorStop(0, 'rgba(14,165,233,0.15)');
    gradient.addColorStop(1, 'rgba(14,165,233,0)');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: @json($chartLabels),
            datasets: [{
                label: 'Vues',
                data: @json($chartData),
                borderColor: '#0EA5E9',
                backgroundColor: gradient,
                borderWidth: 2.5,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#0EA5E9',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { color: '#F1F5F9' }, ticks: { font: { size: 11, family: 'Inter' }, color: '#94A3B8' } },
                x: { grid: { display: false }, ticks: { font: { size: 11, family: 'Inter' }, color: '#94A3B8' } }
            }
        }
    });
</script>
@endpush
