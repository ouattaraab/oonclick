@extends('layouts.panel', ['panelLabel' => 'Admin'])

@section('title', 'Coupons partenaires')

@section('sidebar-nav')
    @include('panel.admin._sidebar', ['active' => 'coupons'])
@endsection

@section('breadcrumb')
    <a href="{{ route('panel.admin.dashboard') }}">Admin</a> &rsaquo; <span class="current">Coupons</span>
@endsection

@push('styles')
<style>
    .stats-row { display:flex; gap:14px; margin-bottom:24px; flex-wrap:wrap; }
    .stat-card { flex:1; min-width:140px; background:#fff; border:1px solid #E2E8F0; border-radius:12px; padding:16px 20px; }
    .stat-label { font-size:11px; color:#64748B; font-weight:600; text-transform:uppercase; letter-spacing:.5px; margin-bottom:6px; }
    .stat-value { font-size:24px; font-weight:800; color:#0F172A; }
    .badge-active   { background:#DCFCE7; color:#15803D; padding:2px 10px; border-radius:20px; font-size:11px; font-weight:700; }
    .badge-inactive { background:#FEE2E2; color:#B91C1C; padding:2px 10px; border-radius:20px; font-size:11px; font-weight:700; }
    .badge-percent  { background:#EDE9FE; color:#7C3AED; padding:2px 10px; border-radius:20px; font-size:11px; font-weight:700; }
    .badge-fixed    { background:#FEF3C7; color:#D97706; padding:2px 10px; border-radius:20px; font-size:11px; font-weight:700; }
    .toggle-form { display:inline; margin:0; }
    .toggle-btn { padding:4px 12px; border-radius:6px; font-size:11px; font-weight:600; border:none; cursor:pointer; font-family:inherit; }
    .toggle-btn.enabled  { background:#DCFCE7; color:#15803D; }
    .toggle-btn.disabled { background:#FEE2E2; color:#B91C1C; }
    .modal-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.45); z-index:1000; align-items:center; justify-content:center; }
    .modal-overlay.open { display:flex; }
    .modal-box { background:#fff; border-radius:18px; padding:28px; width:100%; max-width:540px; box-shadow:0 20px 60px rgba(0,0,0,.15); max-height:90vh; overflow-y:auto; }
    .modal-title { font-size:17px; font-weight:800; color:#0F172A; margin-bottom:20px; }
    .form-group { margin-bottom:14px; }
    .form-label { display:block; font-size:12px; font-weight:700; color:#475569; margin-bottom:5px; }
    .form-control { width:100%; padding:9px 12px; border:1px solid #CBD5E1; border-radius:8px; font-size:13px; font-family:inherit; box-sizing:border-box; }
    .form-control:focus { outline:none; border-color:#2AABF0; box-shadow:0 0 0 3px rgba(42,171,240,.1); }
    .btn-primary { background:linear-gradient(135deg,#2AABF0,#0E7AB8); color:#fff; border:none; border-radius:10px; padding:10px 22px; font-size:13px; font-weight:700; cursor:pointer; font-family:inherit; }
    .btn-cancel  { background:#F1F5F9; color:#64748B; border:none; border-radius:10px; padding:10px 22px; font-size:13px; font-weight:700; cursor:pointer; font-family:inherit; margin-right:8px; }
    .code-chip { font-family:monospace; background:#F1F5F9; padding:3px 8px; border-radius:6px; font-size:12px; font-weight:700; color:#0F172A; letter-spacing:.5px; }
</style>
@endpush

@section('content')
    <div class="page-header" style="display:flex;justify-content:space-between;align-items:center;">
        <h1 class="page-title">Coupons partenaires</h1>
        <button onclick="document.getElementById('modalAddCoupon').classList.add('open')"
                style="background:linear-gradient(135deg,#2AABF0,#0E7AB8);color:#fff;padding:10px 20px;border-radius:10px;font-size:13px;font-weight:700;border:none;cursor:pointer;">
            + Nouveau coupon
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

    @if($errors->any())
    <div style="background:#FEE2E2;color:#B91C1C;border:1px solid #FECACA;border-radius:10px;padding:12px 18px;margin-bottom:20px;font-size:13px;">
        <ul style="margin:0;padding-left:16px;">
            @foreach($errors->all() as $err)
            <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    {{-- KPIs --}}
    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-label">Total coupons</div>
            <div class="stat-value">{{ $totalCoupons }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Actifs</div>
            <div class="stat-value" style="color:#15803D">{{ $activeCoupons }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Total utilisations</div>
            <div class="stat-value">{{ number_format($totalUses) }}</div>
        </div>
    </div>

    {{-- LISTE DES COUPONS --}}
    <div class="card">
        <div class="card-body">
            @if($coupons->isEmpty())
            <div style="text-align:center;padding:40px;color:#94A3B8;">
                <p style="font-weight:600;">Aucun coupon pour le moment.</p>
                <button onclick="document.getElementById('modalAddCoupon').classList.add('open')"
                        style="color:#0EA5E9;font-weight:600;background:none;border:none;cursor:pointer;font-size:14px;">
                    Créer le premier coupon
                </button>
            </div>
            @else
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Code</th>
                            <th>Partenaire</th>
                            <th>Réduction</th>
                            <th>Campagne</th>
                            <th>Utilisations</th>
                            <th>Quota</th>
                            <th>Expiration</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($coupons as $coupon)
                        <tr>
                            <td style="color:#94A3B8;font-size:12px;">{{ $coupon->id }}</td>
                            <td>
                                <span class="code-chip">{{ $coupon->code }}</span>
                                @if($coupon->description)
                                <div style="font-size:11px;color:#64748B;margin-top:3px;">{{ Str::limit($coupon->description, 45) }}</div>
                                @endif
                            </td>
                            <td style="font-weight:600;color:#0F172A;">{{ $coupon->partner_name }}</td>
                            <td>
                                @if($coupon->discount_type === 'percent')
                                    <span class="badge-percent">-{{ $coupon->discount_value }}%</span>
                                @else
                                    <span class="badge-fixed">-{{ number_format($coupon->discount_value) }} FCFA</span>
                                @endif
                            </td>
                            <td>
                                @if($coupon->campaign)
                                    <span style="font-size:12px;color:#2AABF0;font-weight:600;">
                                        #{{ $coupon->campaign->id }} — {{ Str::limit($coupon->campaign->title, 25) }}
                                    </span>
                                @else
                                    <span style="color:#94A3B8;font-size:12px;">—</span>
                                @endif
                            </td>
                            <td>{{ number_format($coupon->uses_count) }}</td>
                            <td>{{ $coupon->max_uses ? number_format($coupon->max_uses) : '∞' }}</td>
                            <td style="font-size:12px;color:#64748B;">{{ $coupon->expires_at ? $coupon->expires_at->format('d/m/Y') : '—' }}</td>
                            <td>
                                @if($coupon->is_active)
                                    <span class="badge-active">Actif</span>
                                @else
                                    <span class="badge-inactive">Inactif</span>
                                @endif
                            </td>
                            <td>
                                <form action="{{ route('panel.admin.coupons.toggle', $coupon) }}" method="POST" class="toggle-form">
                                    @csrf
                                    <button type="submit" class="toggle-btn {{ $coupon->is_active ? 'enabled' : 'disabled' }}">
                                        {{ $coupon->is_active ? 'Désactiver' : 'Activer' }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div style="margin-top:16px;">{{ $coupons->links() }}</div>
            @endif
        </div>
    </div>

    {{-- MODAL : Nouveau coupon --}}
    <div id="modalAddCoupon" class="modal-overlay" onclick="if(event.target===this)this.classList.remove('open')">
        <div class="modal-box">
            <div class="modal-title">Nouveau coupon</div>
            <form method="POST" action="{{ route('panel.admin.coupons.store') }}">
                @csrf
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div class="form-group" style="grid-column:1/-1;">
                        <label class="form-label">Code coupon *</label>
                        <input type="text" name="code" class="form-control" required placeholder="PROMO2026" style="text-transform:uppercase;" value="{{ old('code') }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nom du partenaire *</label>
                        <input type="text" name="partner_name" class="form-control" required placeholder="Ex: Jumia CI" value="{{ old('partner_name') }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Type de réduction *</label>
                        <select name="discount_type" class="form-control" required>
                            <option value="percent" {{ old('discount_type') === 'percent' ? 'selected' : '' }}>Pourcentage (%)</option>
                            <option value="fixed" {{ old('discount_type') === 'fixed' ? 'selected' : '' }}>Montant fixe (FCFA)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Valeur de réduction *</label>
                        <input type="number" name="discount_value" class="form-control" required min="1" placeholder="10" value="{{ old('discount_value') }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Campagne liée</label>
                        <select name="campaign_id" class="form-control">
                            <option value="">— Aucune —</option>
                            @foreach($campaigns as $campaign)
                            <option value="{{ $campaign->id }}" {{ old('campaign_id') == $campaign->id ? 'selected' : '' }}>
                                #{{ $campaign->id }} — {{ Str::limit($campaign->title, 40) }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Utilisations max</label>
                        <input type="number" name="max_uses" class="form-control" min="1" placeholder="100 (vide = illimité)" value="{{ old('max_uses') }}">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="2" placeholder="Description du coupon…">{{ old('description') }}</textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Date d'expiration</label>
                    <input type="datetime-local" name="expires_at" class="form-control" value="{{ old('expires_at') }}">
                </div>
                <div style="display:flex;justify-content:flex-end;margin-top:16px;">
                    <button type="button" class="btn-cancel" onclick="document.getElementById('modalAddCoupon').classList.remove('open')">Annuler</button>
                    <button type="submit" class="btn-primary">Créer le coupon</button>
                </div>
            </form>
        </div>
    </div>
@endsection
