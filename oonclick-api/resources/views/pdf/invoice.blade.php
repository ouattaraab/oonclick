<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Facture {{ $invoice->invoice_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 12px;
            color: #1a1a1a;
            line-height: 1.5;
        }

        /* ── EN-TÊTE ── */
        .header {
            background-color: #0f172a;
            color: #fff;
            padding: 28px 36px;
            margin-bottom: 32px;
        }
        .header-inner {
            display: table;
            width: 100%;
        }
        .header-left  { display: table-cell; vertical-align: middle; }
        .header-right { display: table-cell; vertical-align: middle; text-align: right; }
        .brand {
            font-size: 26px;
            font-weight: bold;
            letter-spacing: 2px;
            color: #f59e0b;
        }
        .brand-tagline {
            font-size: 11px;
            color: #94a3b8;
            margin-top: 2px;
        }
        .invoice-title {
            font-size: 18px;
            font-weight: bold;
            color: #fff;
        }
        .invoice-number {
            font-size: 13px;
            color: #f59e0b;
            margin-top: 4px;
            font-weight: bold;
        }

        /* ── CORPS ── */
        .section { margin: 0 36px 24px; }

        .two-col { display: table; width: 100%; }
        .col-left  { display: table-cell; width: 50%; vertical-align: top; }
        .col-right { display: table-cell; width: 50%; vertical-align: top; text-align: right; }

        .label {
            font-size: 10px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            font-weight: bold;
            margin-bottom: 4px;
        }
        .value { font-size: 13px; color: #0f172a; font-weight: 600; }
        .value-small { font-size: 11px; color: #475569; margin-top: 2px; }

        /* ── STATUT ── */
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
        }
        .status-paid       { background: #dcfce7; color: #15803d; }
        .status-pending    { background: #fef3c7; color: #92400e; }
        .status-cancelled  { background: #fee2e2; color: #b91c1c; }

        /* ── TABLEAU LIGNES ── */
        .divider { border: none; border-top: 1px solid #e2e8f0; margin: 20px 36px; }

        table.items {
            width: calc(100% - 72px);
            margin: 0 36px;
            border-collapse: collapse;
        }
        table.items thead tr th {
            background: #f8fafc;
            padding: 10px 12px;
            text-align: left;
            font-size: 10px;
            font-weight: bold;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #e2e8f0;
        }
        table.items thead tr th:last-child { text-align: right; }
        table.items tbody tr td {
            padding: 12px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 12px;
            vertical-align: top;
        }
        table.items tbody tr td:last-child { text-align: right; font-weight: 700; }

        /* ── TOTAUX ── */
        .totals { margin: 0 36px; }
        .totals-inner {
            margin-left: auto;
            width: 40%;
        }
        .total-row {
            display: table;
            width: 100%;
            padding: 6px 0;
            border-bottom: 1px solid #f1f5f9;
        }
        .total-label { display: table-cell; color: #64748b; font-size: 11px; }
        .total-value { display: table-cell; text-align: right; font-size: 12px; font-weight: 600; color: #0f172a; }
        .total-row.grand .total-label { font-weight: bold; font-size: 13px; color: #0f172a; }
        .total-row.grand .total-value { font-weight: bold; font-size: 16px; color: #0f172a; }

        /* ── PIED DE PAGE ── */
        .footer {
            margin-top: 40px;
            border-top: 1px solid #e2e8f0;
            padding: 20px 36px;
            font-size: 10px;
            color: #94a3b8;
            text-align: center;
        }
        .footer strong { color: #64748b; }
    </style>
</head>
<body>

    {{-- EN-TÊTE --}}
    <div class="header">
        <div class="header-inner">
            <div class="header-left">
                <div class="brand">oon.click</div>
                <div class="brand-tagline">Plateforme de publicité mobile — Côte d'Ivoire</div>
                <div style="font-size:10px;color:#94a3b8;margin-top:8px">
                    contact@oon.click &nbsp;|&nbsp; www.oon.click
                </div>
            </div>
            <div class="header-right">
                <div class="invoice-title">FACTURE</div>
                <div class="invoice-number">{{ $invoice->invoice_number }}</div>
                <div style="font-size:10px;color:#94a3b8;margin-top:6px">
                    Émise le {{ $invoice->created_at->format('d/m/Y') }}
                </div>
            </div>
        </div>
    </div>

    {{-- CLIENT & STATUT --}}
    <div class="section">
        <div class="two-col">
            <div class="col-left">
                <div class="label">Facturé à</div>
                <div class="value">{{ $user->name ?? '—' }}</div>
                <div class="value-small">{{ $user->phone }}</div>
                @if($user->email)
                <div class="value-small">{{ $user->email }}</div>
                @endif
            </div>
            <div class="col-right">
                <div class="label">Statut</div>
                <span class="status-badge {{ $invoice->status === 'paid' ? 'status-paid' : ($invoice->status === 'cancelled' ? 'status-cancelled' : 'status-pending') }}">
                    @if($invoice->status === 'paid') Payée
                    @elseif($invoice->status === 'cancelled') Annulée
                    @elseif($invoice->status === 'sent') Envoyée
                    @else Brouillon
                    @endif
                </span>
                @if($invoice->paid_at)
                <div class="value-small" style="margin-top:6px">Payée le {{ $invoice->paid_at->format('d/m/Y') }}</div>
                @endif
                @if($invoice->due_date)
                <div class="value-small">Échéance : {{ $invoice->due_date->format('d/m/Y') }}</div>
                @endif
            </div>
        </div>
    </div>

    <hr class="divider">

    {{-- LIGNES DE FACTURATION --}}
    <table class="items">
        <thead>
            <tr>
                <th style="width:40%">Description</th>
                <th>Format</th>
                <th>CPV</th>
                <th>Vues max</th>
                <th>Montant</th>
            </tr>
        </thead>
        <tbody>
            @if($campaign)
            <tr>
                <td>
                    <strong>{{ $campaign->title }}</strong><br>
                    <span style="color:#64748b;font-size:10px">Campagne publicitaire #{{ $campaign->id }}</span>
                </td>
                <td style="text-transform:capitalize">{{ $campaign->format ?? '—' }}</td>
                <td>{{ number_format($invoice->metadata['cost_per_view'] ?? 0, 0, ',', ' ') }} F</td>
                <td>{{ number_format($invoice->metadata['max_views'] ?? 0, 0, ',', ' ') }}</td>
                <td>{{ number_format($invoice->amount, 0, ',', ' ') }} FCFA</td>
            </tr>
            @else
            <tr>
                <td colspan="4">
                    <strong>{{ ucfirst(str_replace('_', ' ', $invoice->type)) }}</strong>
                </td>
                <td>{{ number_format($invoice->amount, 0, ',', ' ') }} FCFA</td>
            </tr>
            @endif
        </tbody>
    </table>

    <div style="margin-top:24px"></div>

    {{-- TOTAUX --}}
    <div class="totals">
        <div class="totals-inner">
            <div class="total-row">
                <span class="total-label">Sous-total HT</span>
                <span class="total-value">{{ number_format($invoice->amount, 0, ',', ' ') }} FCFA</span>
            </div>
            <div class="total-row">
                <span class="total-label">TVA (0%)</span>
                <span class="total-value">{{ number_format($invoice->tax_amount, 0, ',', ' ') }} FCFA</span>
            </div>
            <div class="total-row grand" style="border-top:2px solid #0f172a;margin-top:4px;padding-top:8px">
                <span class="total-label">TOTAL TTC</span>
                <span class="total-value">{{ number_format($invoice->total_amount, 0, ',', ' ') }} FCFA</span>
            </div>
        </div>
    </div>

    {{-- PIED DE PAGE --}}
    <div class="footer">
        <strong>oon.click</strong> — Société enregistrée en Côte d'Ivoire &nbsp;&bull;&nbsp;
        RCCM : CI-ABJ-2026-B-XXXXX &nbsp;&bull;&nbsp; NIF : XXXXXXXXXXXX<br>
        Cette facture a été générée automatiquement le {{ $generatedAt }} — elle ne nécessite pas de signature.
        <br>En cas de litige, contactez <strong>support@oon.click</strong>.
    </div>

</body>
</html>
