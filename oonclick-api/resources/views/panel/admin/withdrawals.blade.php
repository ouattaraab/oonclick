@extends('layouts.panel', ['panelLabel' => 'Admin'])

@section('title', 'Retraits')

@section('sidebar-nav')
    @include('panel.admin._sidebar', ['active' => 'withdrawals'])
@endsection

@section('breadcrumb')
    <a href="{{ route('panel.admin.dashboard') }}">Admin</a> &rsaquo; <span class="current">Retraits</span>
@endsection

@section('content')
    <div class="page-header">
        <h1 class="page-title">Retraits</h1>
    </div>

    @if(session('success'))
    <div style="background:#DCFCE7;color:#15803D;border:1px solid #BBF7D0;border-radius:10px;padding:12px 18px;margin-bottom:20px;font-size:13px;font-weight:600;">
        ✓ {{ session('success') }}
    </div>
    @endif

    @if(session('error'))
    <div style="background:#FEE2E2;color:#B91C1C;border:1px solid #FECACA;border-radius:10px;padding:12px 18px;margin-bottom:20px;font-size:13px;font-weight:600;">
        ✗ {{ session('error') }}
    </div>
    @endif

    {{-- KPI CARDS --}}
    <div class="kpi-grid">
        <div class="kpi-card">
            <div class="accent sky"></div>
            <div class="kpi-header">
                <div class="kpi-icon sky">💸</div>
                <span class="kpi-label">Total retraits</span>
            </div>
            <div class="kpi-value">{{ number_format($totalWithdrawals, 0, ',', ' ') }}</div>
            <div class="kpi-change neutral">Toutes demandes confondues</div>
        </div>
        <div class="kpi-card">
            <div class="accent amber"></div>
            <div class="kpi-header">
                <div class="kpi-icon amber">⏳</div>
                <span class="kpi-label">En attente</span>
            </div>
            <div class="kpi-value">{{ $pendingCount }}</div>
            <div class="kpi-change neutral">À traiter</div>
        </div>
        <div class="kpi-card">
            <div class="accent green"></div>
            <div class="kpi-header">
                <div class="kpi-icon green">✅</div>
                <span class="kpi-label">Approuvés</span>
            </div>
            <div class="kpi-value">{{ $completedCount }}</div>
            <div class="kpi-change neutral">Traités avec succès</div>
        </div>
        <div class="kpi-card">
            <div class="accent purple"></div>
            <div class="kpi-header">
                <div class="kpi-icon purple">💰</div>
                <span class="kpi-label">Montant total versé</span>
            </div>
            <div class="kpi-value">{{ number_format($totalAmount, 0, ',', ' ') }} F</div>
            <div class="kpi-change neutral">Net décaissé (FCFA)</div>
        </div>
    </div>

    {{-- APPROUVER TOUT (US-044) --}}
    @if($pendingCount > 0)
    <div style="margin-bottom:16px">
        <form method="POST" action="{{ route('panel.admin.withdrawals.batch-approve') }}"
              id="batch-form"
              onsubmit="return confirm('Approuver tous les retraits en attente sélectionnés ?')">
            @csrf
            <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap">
                <button type="button"
                        onclick="toggleSelectAll()"
                        style="background:#F1F5F9;border:1px solid #CBD5E1;border-radius:8px;padding:8px 16px;font-size:12px;font-weight:600;cursor:pointer;color:#475569">
                    Tout sélectionner
                </button>
                <button type="submit"
                        style="background:#22C55E;color:#fff;border:none;border-radius:8px;padding:8px 18px;font-size:12px;font-weight:700;cursor:pointer">
                    ✓ Approuver tout (en attente sélectionnés)
                </button>
                <span style="font-size:11px;color:#94A3B8">{{ $pendingCount }} en attente</span>
            </div>
        </form>
    </div>
    @endif

    {{-- TABLE --}}
    <div class="card">
        <div class="card-header">
            <div class="card-title">Toutes les demandes de retrait</div>
        </div>
        @if($withdrawals->count() > 0)
        <table>
            <thead>
                <tr>
                    <th style="width:36px"><input type="checkbox" id="check-all" onchange="toggleSelectAll(this.checked)"></th>
                    <th>Utilisateur</th>
                    <th>Montant</th>
                    <th>Frais</th>
                    <th>Net</th>
                    <th>Opérateur</th>
                    <th>Statut</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($withdrawals as $withdrawal)
                <tr>
                    <td>
                        @if($withdrawal->status === 'pending')
                        <input type="checkbox" name="withdrawal_ids[]" value="{{ $withdrawal->id }}"
                               form="batch-form" class="pending-check">
                        @endif
                    </td>
                    <td>
                        <div class="user-cell">
                            <div class="user-avatar" style="background:linear-gradient(135deg,#0EA5E9,#0284C7)">
                                {{ strtoupper(substr($withdrawal->user->name ?? $withdrawal->user->phone ?? 'U', 0, 1)) }}{{ strtoupper(substr(explode(' ', $withdrawal->user->name ?? '')[1] ?? '', 0, 1)) }}
                            </div>
                            <div>
                                <div class="user-name">{{ $withdrawal->user->name ?? '—' }}</div>
                                <div class="user-sub">{{ $withdrawal->mobile_phone ?? $withdrawal->user->phone }}</div>
                            </div>
                        </div>
                    </td>
                    <td style="font-weight:700;color:#0F172A">{{ number_format($withdrawal->amount, 0, ',', ' ') }} F</td>
                    <td style="color:#94A3B8;font-size:12px">{{ number_format($withdrawal->fee, 0, ',', ' ') }} F</td>
                    <td style="font-weight:700;color:#22C55E">{{ number_format($withdrawal->net_amount, 0, ',', ' ') }} F</td>
                    <td>
                        @php
                            $op = strtolower($withdrawal->mobile_operator ?? '');
                            $opColor = match($op) {
                                'mtn'    => 'background:#FEF3C7;color:#92400E',
                                'moov'   => 'background:#DBEAFE;color:#1D4ED8',
                                'orange' => 'background:#FEE2E2;color:#B91C1C',
                                default  => 'background:#F1F5F9;color:#475569',
                            };
                        @endphp
                        <span class="badge" style="{{ $opColor }}">
                            {{ strtoupper($withdrawal->mobile_operator ?? '—') }}
                        </span>
                    </td>
                    <td>
                        @if($withdrawal->status === 'completed')
                            <span class="badge badge-active">Approuvé</span>
                        @elseif($withdrawal->status === 'pending')
                            <span class="badge badge-pending">En attente</span>
                        @elseif($withdrawal->status === 'failed')
                            <span class="badge badge-danger">Échoué</span>
                        @else
                            <span class="badge badge-gray">{{ ucfirst($withdrawal->status) }}</span>
                        @endif
                    </td>
                    <td style="color:#94A3B8;font-size:12px">{{ $withdrawal->created_at->format('d/m/Y H:i') }}</td>
                    <td>
                        @if($withdrawal->status === 'pending')
                        <div style="display:flex;gap:4px;flex-wrap:nowrap;">
                            <form method="POST" action="{{ route('panel.admin.withdrawals.approve', $withdrawal) }}" style="display:inline">
                                @csrf
                                <button type="submit" class="action-btn success btn-sm"
                                    onclick="return confirm('Approuver ce retrait de {{ number_format($withdrawal->net_amount) }} F ?')">
                                    Approuver
                                </button>
                            </form>
                            <form method="POST" action="{{ route('panel.admin.withdrawals.reject', $withdrawal) }}" style="display:inline">
                                @csrf
                                <button type="submit" class="action-btn danger btn-sm"
                                    onclick="return confirm('Rejeter ce retrait ?')">
                                    Rejeter
                                </button>
                            </form>
                        </div>
                        @else
                            <span style="color:#CBD5E1;font-size:12px">—</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @if($withdrawals->hasPages())
        <div class="pagination">
            <span>Affichage de {{ $withdrawals->firstItem() }} à {{ $withdrawals->lastItem() }} sur {{ $withdrawals->total() }}</span>
            <div>{{ $withdrawals->links('pagination::simple-default') }}</div>
        </div>
        @endif
        @else
        <div class="empty-state">
            <div class="icon">💸</div>
            <p>Aucune demande de retrait pour le moment</p>
        </div>
        @endif
    </div>

<script>
function toggleSelectAll(checked) {
    var boxes = document.querySelectorAll('.pending-check');
    var masterCheck = document.getElementById('check-all');
    if (typeof checked === 'undefined') {
        // Appelé depuis le bouton texte — inverser l'état
        var anyUnchecked = Array.from(boxes).some(function(b) { return !b.checked; });
        boxes.forEach(function(b) { b.checked = anyUnchecked; });
        if (masterCheck) masterCheck.checked = anyUnchecked;
    } else {
        boxes.forEach(function(b) { b.checked = checked; });
    }
}
</script>
@endsection
