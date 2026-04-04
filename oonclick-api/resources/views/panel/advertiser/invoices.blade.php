@extends('layouts.panel-advertiser')

@section('title', 'Mes factures')
@section('topbar-title', 'Mes factures')

@section('sidebar-nav')
    @include('panel.advertiser._sidebar', ['active' => 'invoices'])
@endsection

@section('content')
    <div class="card">
        <div class="card-head">
            <div class="card-title">Historique des factures</div>
        </div>

        @if($invoices->count() > 0)
        <table>
            <thead>
                <tr>
                    <th>N° Facture</th>
                    <th>Campagne</th>
                    <th>Type</th>
                    <th>Montant total</th>
                    <th>Statut</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoices as $invoice)
                <tr>
                    <td style="font-weight:700;color:#0B1929;font-family:monospace">
                        {{ $invoice->invoice_number }}
                    </td>
                    <td>
                        @if($invoice->campaign)
                            <div style="font-weight:600;color:#0B1929">{{ $invoice->campaign->title }}</div>
                            <div style="font-size:11px;color:#94A3B8">{{ ucfirst($invoice->campaign->format) }}</div>
                        @else
                            <span style="color:#94A3B8">—</span>
                        @endif
                    </td>
                    <td>
                        <span class="badge badge-gray" style="text-transform:capitalize">
                            {{ match($invoice->type) {
                                'campaign_payment' => 'Paiement campagne',
                                'subscription'     => 'Abonnement',
                                'refund'           => 'Remboursement',
                                default            => ucfirst($invoice->type),
                            } }}
                        </span>
                    </td>
                    <td style="font-weight:800;color:#0F172A">
                        {{ number_format($invoice->total_amount, 0, ',', ' ') }} F
                    </td>
                    <td>
                        @if($invoice->status === 'paid')
                            <span class="badge badge-active">Payée</span>
                        @elseif($invoice->status === 'sent')
                            <span class="badge badge-pending">Envoyée</span>
                        @elseif($invoice->status === 'cancelled')
                            <span class="badge badge-danger">Annulée</span>
                        @else
                            <span class="badge badge-gray">Brouillon</span>
                        @endif
                    </td>
                    <td style="color:#94A3B8;font-size:12px">
                        {{ $invoice->created_at->format('d/m/Y') }}
                    </td>
                    <td>
                        <a href="{{ route('panel.advertiser.invoices.pdf', $invoice) }}"
                           class="action-link"
                           style="display:inline-flex;align-items:center;gap:4px">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                            </svg>
                            PDF
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        @if($invoices->hasPages())
        <div class="pagination">
            <span>
                Affichage de {{ $invoices->firstItem() }} à {{ $invoices->lastItem() }}
                sur {{ $invoices->total() }}
            </span>
            <div>{{ $invoices->links('pagination::simple-default') }}</div>
        </div>
        @endif

        @else
        <div class="empty-state">
            <div class="icon">🧾</div>
            <p>Aucune facture pour le moment.</p>
            <p style="font-size:12px;color:#94A3B8;margin-top:4px">
                Vos factures apparaîtront ici après chaque paiement de campagne.
            </p>
        </div>
        @endif
    </div>
@endsection
