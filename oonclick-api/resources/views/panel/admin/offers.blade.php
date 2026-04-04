@extends('layouts.panel', ['panelLabel' => 'Admin'])

@section('title', 'Offres cashback')

@section('sidebar-nav')
    @include('panel.admin._sidebar', ['active' => 'offers'])
@endsection

@section('breadcrumb')
    <a href="{{ route('panel.admin.dashboard') }}">Admin</a> &rsaquo; <span class="current">Offres cashback</span>
@endsection

@push('styles')
<style>
    .stats-row { display:flex; gap:14px; margin-bottom:24px; flex-wrap:wrap; }
    .stat-card { flex:1; min-width:140px; background:#fff; border:1px solid #E2E8F0; border-radius:12px; padding:16px 20px; }
    .stat-label { font-size:11px; color:#64748B; font-weight:600; text-transform:uppercase; letter-spacing:.5px; margin-bottom:6px; }
    .stat-value { font-size:24px; font-weight:800; color:#0F172A; }
    .badge-active   { background:#DCFCE7; color:#15803D; padding:2px 10px; border-radius:20px; font-size:11px; font-weight:700; }
    .badge-inactive { background:#FEE2E2; color:#B91C1C; padding:2px 10px; border-radius:20px; font-size:11px; font-weight:700; }
    .badge-pending  { background:#FEF9C3; color:#A16207; padding:2px 10px; border-radius:20px; font-size:11px; font-weight:700; }
    .badge-credited { background:#DCFCE7; color:#15803D; padding:2px 10px; border-radius:20px; font-size:11px; font-weight:700; }
    .badge-rejected { background:#FEE2E2; color:#B91C1C; padding:2px 10px; border-radius:20px; font-size:11px; font-weight:700; }
    .toggle-form { display:inline; margin:0; }
    .toggle-btn { padding:4px 12px; border-radius:6px; font-size:11px; font-weight:600; border:none; cursor:pointer; font-family:inherit; }
    .toggle-btn.enabled  { background:#DCFCE7; color:#15803D; }
    .toggle-btn.disabled { background:#FEE2E2; color:#B91C1C; }
    .section-header { font-size:15px; font-weight:800; color:#0F172A; margin-bottom:16px; padding-bottom:10px; border-bottom:1px solid #E2E8F0; }
    .modal-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.45); z-index:1000; align-items:center; justify-content:center; }
    .modal-overlay.open { display:flex; }
    .modal-box { background:#fff; border-radius:18px; padding:28px; width:100%; max-width:520px; box-shadow:0 20px 60px rgba(0,0,0,.15); }
    .modal-title { font-size:17px; font-weight:800; color:#0F172A; margin-bottom:20px; }
    .form-group { margin-bottom:14px; }
    .form-label { display:block; font-size:12px; font-weight:700; color:#475569; margin-bottom:5px; }
    .form-control { width:100%; padding:9px 12px; border:1px solid #CBD5E1; border-radius:8px; font-size:13px; font-family:inherit; box-sizing:border-box; }
    .form-control:focus { outline:none; border-color:#2AABF0; box-shadow:0 0 0 3px rgba(42,171,240,.1); }
    .btn-primary { background:linear-gradient(135deg,#2AABF0,#0E7AB8); color:#fff; border:none; border-radius:10px; padding:10px 22px; font-size:13px; font-weight:700; cursor:pointer; font-family:inherit; }
    .btn-cancel  { background:#F1F5F9; color:#64748B; border:none; border-radius:10px; padding:10px 22px; font-size:13px; font-weight:700; cursor:pointer; font-family:inherit; margin-right:8px; }
</style>
@endpush

@section('content')
    <div class="page-header" style="display:flex;justify-content:space-between;align-items:center;">
        <h1 class="page-title">Offres cashback</h1>
        <button onclick="document.getElementById('modalAddOffer').classList.add('open')"
                style="background:linear-gradient(135deg,#2AABF0,#0E7AB8);color:#fff;padding:10px 20px;border-radius:10px;font-size:13px;font-weight:700;border:none;cursor:pointer;">
            + Nouvelle offre
        </button>
    </div>

    @if(session('success'))
    <div style="background:#DCFCE7;color:#15803D;border:1px solid #BBF7D0;border-radius:10px;padding:12px 18px;margin-bottom:20px;font-size:13px;font-weight:600;">
        &#10003; {{ session('success') }}
    </div>
    @endif

    @if(session('error'))
    <div style="background:#FEE2E2;color:#B91C1C;border:1px solid #FECACA;border-radius:10px;padding:12px 18px;margin-bottom:20px;font-size:13px;font-weight:600;">
        &#9888; {{ session('error') }}
    </div>
    @endif

    {{-- KPIs --}}
    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-label">Total offres</div>
            <div class="stat-value">{{ $totalOffers }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Offres actives</div>
            <div class="stat-value" style="color:#15803D">{{ $activeOffers }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Total demandes</div>
            <div class="stat-value">{{ $totalClaims }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">En attente</div>
            <div class="stat-value" style="color:#D97706">{{ $pendingCount }}</div>
        </div>
    </div>

    {{-- LISTE DES OFFRES --}}
    <div class="card" style="margin-bottom:32px;">
        <div class="card-body">
            <div class="section-header">Offres partenaires</div>
            @if($offers->isEmpty())
            <div style="text-align:center;padding:40px;color:#94A3B8;">
                <p style="font-weight:600;">Aucune offre partenaire pour le moment.</p>
            </div>
            @else
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Partenaire</th>
                            <th>Catégorie</th>
                            <th>Cashback</th>
                            <th>Code promo</th>
                            <th>Demandes</th>
                            <th>Expiration</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($offers as $offer)
                        <tr>
                            <td style="color:#94A3B8;font-size:12px;">{{ $offer->id }}</td>
                            <td>
                                <div style="display:flex;align-items:center;gap:8px;">
                                    @if($offer->logo_url)
                                    <img src="{{ $offer->logo_url }}" alt="" style="width:32px;height:32px;border-radius:8px;object-fit:contain;border:1px solid #E2E8F0;">
                                    @else
                                    <div style="width:32px;height:32px;background:#F1F5F9;border-radius:8px;display:flex;align-items:center;justify-content:center;font-weight:800;color:#64748B;font-size:12px;">
                                        {{ strtoupper(substr($offer->partner_name, 0, 1)) }}
                                    </div>
                                    @endif
                                    <div>
                                        <div style="font-weight:700;color:#0F172A;">{{ $offer->partner_name }}</div>
                                        @if($offer->description)
                                        <div style="font-size:11px;color:#64748B;">{{ Str::limit($offer->description, 50) }}</div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td>{{ $offer->category ?? '—' }}</td>
                            <td><span style="font-weight:700;color:#2AABF0;">{{ $offer->cashback_percent }}%</span></td>
                            <td>{{ $offer->promo_code ?? '—' }}</td>
                            <td>{{ number_format($offer->claims_count) }}</td>
                            <td>{{ $offer->expires_at ? $offer->expires_at->format('d/m/Y') : '—' }}</td>
                            <td>
                                @if($offer->is_active)
                                    <span class="badge-active">Actif</span>
                                @else
                                    <span class="badge-inactive">Inactif</span>
                                @endif
                            </td>
                            <td>
                                <form action="{{ route('panel.admin.offers.toggle', $offer) }}" method="POST" class="toggle-form">
                                    @csrf
                                    <button type="submit" class="toggle-btn {{ $offer->is_active ? 'enabled' : 'disabled' }}">
                                        {{ $offer->is_active ? 'Désactiver' : 'Activer' }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div style="margin-top:16px;">{{ $offers->links() }}</div>
            @endif
        </div>
    </div>

    {{-- DEMANDES DE CASHBACK EN ATTENTE --}}
    <div class="card">
        <div class="card-body">
            <div class="section-header">Demandes de cashback en attente</div>
            @if($pendingClaims->isEmpty())
            <div style="text-align:center;padding:40px;color:#94A3B8;">
                <p style="font-weight:600;">Aucune demande en attente.</p>
            </div>
            @else
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Utilisateur</th>
                            <th>Offre</th>
                            <th>Achat</th>
                            <th>Cashback</th>
                            <th>Référence</th>
                            <th>Statut</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($pendingClaims as $claim)
                        <tr>
                            <td style="color:#94A3B8;font-size:12px;">{{ $claim->id }}</td>
                            <td>
                                <div style="font-weight:700;color:#0F172A;">{{ $claim->user?->name ?? '—' }}</div>
                                <div style="font-size:11px;color:#64748B;">{{ $claim->user?->phone }}</div>
                            </td>
                            <td>{{ $claim->offer?->partner_name ?? '—' }}</td>
                            <td><span style="font-weight:600;">{{ number_format($claim->purchase_amount) }} FCFA</span></td>
                            <td><span style="font-weight:700;color:#D97706;">{{ number_format($claim->cashback_amount) }} FCFA</span></td>
                            <td style="font-size:12px;color:#64748B;">{{ $claim->receipt_reference ?? '—' }}</td>
                            <td>
                                @if($claim->status === 'pending')
                                    <span class="badge-pending">En attente</span>
                                @elseif($claim->status === 'credited')
                                    <span class="badge-credited">Crédité</span>
                                @else
                                    <span class="badge-rejected">Rejeté</span>
                                @endif
                            </td>
                            <td style="font-size:12px;color:#64748B;">{{ $claim->created_at->format('d/m/Y H:i') }}</td>
                            <td>
                                <div style="display:flex;gap:8px;">
                                    <form action="{{ route('panel.admin.offers.claims.approve', $claim) }}" method="POST" style="display:inline;margin:0;" onsubmit="return confirm('Approuver et créditer ce cashback ?')">
                                        @csrf
                                        <button type="submit" style="background:#DCFCE7;color:#15803D;border:none;border-radius:6px;padding:4px 10px;font-size:11px;font-weight:600;cursor:pointer;">
                                            Approuver
                                        </button>
                                    </form>
                                    <form action="{{ route('panel.admin.offers.claims.reject', $claim) }}" method="POST" style="display:inline;margin:0;" onsubmit="return confirm('Rejeter cette demande ?')">
                                        @csrf
                                        <button type="submit" style="background:#FEE2E2;color:#B91C1C;border:none;border-radius:6px;padding:4px 10px;font-size:11px;font-weight:600;cursor:pointer;">
                                            Rejeter
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div style="margin-top:16px;">{{ $pendingClaims->links() }}</div>
            @endif
        </div>
    </div>

    {{-- MODAL : Nouvelle offre --}}
    <div id="modalAddOffer" class="modal-overlay" onclick="if(event.target===this)this.classList.remove('open')">
        <div class="modal-box">
            <div class="modal-title">Nouvelle offre partenaire</div>
            <form method="POST" action="{{ route('panel.admin.offers.store') }}">
                @csrf
                <div class="form-group">
                    <label class="form-label">Nom du partenaire *</label>
                    <input type="text" name="partner_name" class="form-control" required placeholder="Ex: Orange CI">
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div class="form-group">
                        <label class="form-label">Cashback (%) *</label>
                        <input type="number" name="cashback_percent" class="form-control" step="0.01" min="0.01" max="100" required placeholder="5.00">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Catégorie</label>
                        <input type="text" name="category" class="form-control" placeholder="Alimentation, Telecom…">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Code promo</label>
                    <input type="text" name="promo_code" class="form-control" placeholder="SAVE20">
                </div>
                <div class="form-group">
                    <label class="form-label">URL du logo</label>
                    <input type="url" name="logo_url" class="form-control" placeholder="https://...">
                </div>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="2" placeholder="Description de l'offre…"></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Date d'expiration</label>
                    <input type="datetime-local" name="expires_at" class="form-control">
                </div>
                <div style="display:flex;justify-content:flex-end;margin-top:16px;">
                    <button type="button" class="btn-cancel" onclick="document.getElementById('modalAddOffer').classList.remove('open')">Annuler</button>
                    <button type="submit" class="btn-primary">Créer l'offre</button>
                </div>
            </form>
        </div>
    </div>
@endsection
