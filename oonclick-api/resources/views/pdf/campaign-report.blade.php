<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rapport de campagne - {{ $stats['title'] }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 12px;
            color: #1a1a1a;
            line-height: 1.5;
        }
        .header {
            background-color: #0f172a;
            color: #ffffff;
            padding: 24px 32px;
            margin-bottom: 28px;
        }
        .header .brand {
            font-size: 22px;
            font-weight: bold;
            letter-spacing: 1px;
            color: #f59e0b;
        }
        .header .subtitle {
            font-size: 13px;
            color: #94a3b8;
            margin-top: 4px;
        }
        .header .meta {
            margin-top: 12px;
            font-size: 11px;
            color: #cbd5e1;
        }
        .section {
            margin: 0 32px 24px 32px;
        }
        .section-title {
            font-size: 13px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #0f172a;
            border-bottom: 2px solid #f59e0b;
            padding-bottom: 4px;
            margin-bottom: 14px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table th {
            background-color: #f1f5f9;
            text-align: left;
            padding: 8px 10px;
            font-size: 11px;
            font-weight: bold;
            color: #475569;
            border: 1px solid #e2e8f0;
        }
        table td {
            padding: 8px 10px;
            font-size: 11px;
            border: 1px solid #e2e8f0;
            color: #1e293b;
        }
        table tr:nth-child(even) td {
            background-color: #f8fafc;
        }
        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: bold;
        }
        .badge-active    { background-color: #dcfce7; color: #166534; }
        .badge-pending   { background-color: #fef9c3; color: #854d0e; }
        .badge-paused    { background-color: #f1f5f9; color: #475569; }
        .badge-completed { background-color: #dbeafe; color: #1e40af; }
        .badge-rejected  { background-color: #fee2e2; color: #991b1b; }
        .badge-default   { background-color: #f1f5f9; color: #334155; }
        .kpi-grid {
            width: 100%;
            border-collapse: collapse;
        }
        .kpi-grid td {
            width: 25%;
            padding: 12px;
            text-align: center;
            border: 1px solid #e2e8f0;
            background-color: #f8fafc;
        }
        .kpi-grid .kpi-value {
            font-size: 18px;
            font-weight: bold;
            color: #0f172a;
            display: block;
        }
        .kpi-grid .kpi-label {
            font-size: 10px;
            color: #64748b;
            margin-top: 4px;
            display: block;
        }
        .distrib-grid {
            width: 100%;
        }
        .distrib-grid td {
            width: 50%;
            vertical-align: top;
            padding-right: 12px;
        }
        .distrib-grid td:last-child {
            padding-right: 0;
        }
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 12px 32px;
            border-top: 1px solid #e2e8f0;
            background-color: #f8fafc;
            font-size: 9px;
            color: #94a3b8;
            text-align: center;
        }
        .progress-bar-bg {
            background-color: #e2e8f0;
            border-radius: 4px;
            height: 8px;
            width: 100%;
        }
        .progress-bar-fill {
            background-color: #f59e0b;
            border-radius: 4px;
            height: 8px;
        }
        .two-col {
            width: 100%;
            border-collapse: collapse;
        }
        .two-col td {
            width: 50%;
            padding: 6px 0;
            vertical-align: top;
        }
        .label-cell {
            color: #64748b;
            font-size: 11px;
        }
        .value-cell {
            font-weight: bold;
            color: #0f172a;
            font-size: 11px;
        }
    </style>
</head>
<body>

    {{-- HEADER --}}
    <div class="header">
        <div class="brand">oon.click</div>
        <div class="subtitle">Rapport de performance de campagne</div>
        <div class="meta">
            Généré le {{ $generatedAt }} &nbsp;|&nbsp; Campagne #{{ $stats['campaign_id'] }}
        </div>
    </div>

    {{-- SECTION 1 — INFORMATIONS CAMPAGNE --}}
    <div class="section">
        <div class="section-title">Informations de la campagne</div>
        <table class="two-col">
            <tr>
                <td class="label-cell">Titre</td>
                <td class="value-cell">{{ $stats['title'] }}</td>
                <td class="label-cell">Format</td>
                <td class="value-cell">{{ strtoupper($campaign->format ?? '-') }}</td>
            </tr>
            <tr>
                <td class="label-cell">Statut</td>
                <td class="value-cell">
                    @php
                        $statusLabels = [
                            'active'         => 'Actif',
                            'pending_review' => 'En attente',
                            'paused'         => 'En pause',
                            'completed'      => 'Terminé',
                            'rejected'       => 'Rejeté',
                            'draft'          => 'Brouillon',
                            'approved'       => 'Approuvé',
                        ];
                        $statusClass = [
                            'active'         => 'badge-active',
                            'pending_review' => 'badge-pending',
                            'paused'         => 'badge-paused',
                            'completed'      => 'badge-completed',
                            'rejected'       => 'badge-rejected',
                        ][$stats['status']] ?? 'badge-default';
                    @endphp
                    <span class="badge {{ $statusClass }}">
                        {{ $statusLabels[$stats['status']] ?? $stats['status'] }}
                    </span>
                </td>
                <td class="label-cell">Budget total</td>
                <td class="value-cell">{{ number_format($stats['budget'], 0, ',', ' ') }} FCFA</td>
            </tr>
            <tr>
                <td class="label-cell">Début</td>
                <td class="value-cell">{{ $campaign->starts_at ? $campaign->starts_at->format('d/m/Y') : 'N/A' }}</td>
                <td class="label-cell">Fin</td>
                <td class="value-cell">{{ $campaign->ends_at ? $campaign->ends_at->format('d/m/Y') : 'N/A' }}</td>
            </tr>
            <tr>
                <td class="label-cell">Coût par vue</td>
                <td class="value-cell">{{ number_format($stats['cost_per_view'], 0, ',', ' ') }} FCFA</td>
                <td class="label-cell">Vues maximum</td>
                <td class="value-cell">{{ number_format($stats['max_views'], 0, ',', ' ') }}</td>
            </tr>
        </table>
    </div>

    {{-- SECTION 2 — MÉTRIQUES CLÉS --}}
    <div class="section">
        <div class="section-title">Métriques clés</div>
        <table class="kpi-grid">
            <tr>
                <td>
                    <span class="kpi-value">{{ number_format($stats['views_count'], 0, ',', ' ') }}</span>
                    <span class="kpi-label">Vues complètes</span>
                </td>
                <td>
                    <span class="kpi-value">{{ $stats['completion_rate'] }}%</span>
                    <span class="kpi-label">Taux de complétion</span>
                </td>
                <td>
                    <span class="kpi-value">{{ number_format($stats['budget_used'], 0, ',', ' ') }} F</span>
                    <span class="kpi-label">Budget utilisé</span>
                </td>
                <td>
                    <span class="kpi-value">{{ number_format($stats['budget_remaining'], 0, ',', ' ') }} F</span>
                    <span class="kpi-label">Budget restant</span>
                </td>
            </tr>
        </table>

        <br>

        <table>
            <thead>
                <tr>
                    <th>Indicateur</th>
                    <th>Valeur</th>
                    <th>Indicateur</th>
                    <th>Valeur</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Taux de crédit</td>
                    <td>{{ $stats['credit_rate'] }}%</td>
                    <td>Durée moyenne de visionnage</td>
                    <td>{{ $stats['avg_watch_duration'] }}s</td>
                </tr>
                <tr>
                    <td>Budget escrow verrouillé</td>
                    <td>{{ number_format($stats['escrow']['amount_locked'], 0, ',', ' ') }} FCFA</td>
                    <td>Escrow libéré</td>
                    <td>{{ number_format($stats['escrow']['amount_released'], 0, ',', ' ') }} FCFA</td>
                </tr>
                <tr>
                    <td>Escrow restant</td>
                    <td>{{ number_format($stats['escrow']['remaining'], 0, ',', ' ') }} FCFA</td>
                    <td>Progression vues</td>
                    <td>{{ $stats['views_count'] }} / {{ $stats['max_views'] }}</td>
                </tr>
            </tbody>
        </table>

        @php
            $progressPct = $stats['max_views'] > 0
                ? min(100, round(($stats['views_count'] / $stats['max_views']) * 100))
                : 0;
        @endphp
        <br>
        <div style="font-size:10px; color:#64748b; margin-bottom:4px;">
            Progression : {{ $progressPct }}%
        </div>
        <div class="progress-bar-bg">
            <div class="progress-bar-fill" style="width: {{ $progressPct }}%;"></div>
        </div>
    </div>

    {{-- SECTION 3 — RÉPARTITION AUDIENCES --}}
    <div class="section">
        <div class="section-title">Répartition de l'audience</div>
        <table class="distrib-grid">
            <tr>
                {{-- Genre --}}
                <td>
                    <table>
                        <thead>
                            <tr>
                                <th colspan="2">Par genre</th>
                            </tr>
                            <tr>
                                <th>Genre</th>
                                <th>Vues</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($stats['views_by_gender'] as $gender => $count)
                                <tr>
                                    <td>{{ ucfirst($gender) }}</td>
                                    <td>{{ number_format($count, 0, ',', ' ') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" style="color:#94a3b8; text-align:center;">Aucune donnée</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </td>
                {{-- Opérateur --}}
                <td>
                    <table>
                        <thead>
                            <tr>
                                <th colspan="2">Par opérateur</th>
                            </tr>
                            <tr>
                                <th>Opérateur</th>
                                <th>Vues</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($stats['views_by_operator'] as $operator => $count)
                                <tr>
                                    <td>{{ ucfirst($operator) }}</td>
                                    <td>{{ number_format($count, 0, ',', ' ') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" style="color:#94a3b8; text-align:center;">Aucune donnée</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </td>
            </tr>
        </table>

        <br>

        {{-- Top 5 villes --}}
        <table>
            <thead>
                <tr>
                    <th colspan="2">Top 5 villes</th>
                </tr>
                <tr>
                    <th>Ville</th>
                    <th>Vues</th>
                </tr>
            </thead>
            <tbody>
                @forelse($stats['views_by_city'] as $city => $count)
                    <tr>
                        <td>{{ ucfirst($city) }}</td>
                        <td>{{ number_format($count, 0, ',', ' ') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2" style="color:#94a3b8; text-align:center;">Aucune donnée</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- FOOTER --}}
    <div class="footer">
        Ce rapport est généré automatiquement par la plateforme oon.click et est confidentiel.
        Il est destiné exclusivement à l'annonceur propriétaire de la campagne.
        &copy; {{ date('Y') }} oon.click — Tous droits réservés.
    </div>

</body>
</html>
