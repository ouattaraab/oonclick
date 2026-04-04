@extends('layouts.panel-advertiser', ['walletBalance' => $walletBalance])

@section('title', 'Mes coupons')
@section('topbar-title', 'Mes coupons')
@section('topbar-actions')
    <button class="btn-new" onclick="document.getElementById('modal-coupon').style.display='flex'">+ Nouveau coupon</button>
@endsection

@section('sidebar-nav')
    @include('panel.advertiser._sidebar', ['active' => 'coupons'])
@endsection

@push('styles')
<style>
    .badge-percent  { background:#DBEAFE; color:#1E40AF; }
    .badge-fixed    { background:#FEF3C7; color:#78350F; }
    .badge-inactive { background:#FEE2E2; color:#991B1B; }

    /* Modal overlay */
    .modal-overlay {
        display:none; position:fixed; inset:0; background:rgba(11,25,41,0.55);
        z-index:100; align-items:center; justify-content:center;
    }
    .modal-box {
        background:#fff; border-radius:20px; width:100%; max-width:560px;
        max-height:90vh; overflow-y:auto;
        box-shadow:0 20px 60px rgba(0,0,0,0.2);
        animation:slideUp .2s ease;
    }
    @keyframes slideUp { from { transform:translateY(20px); opacity:0; } to { transform:translateY(0); opacity:1; } }
    .modal-header {
        padding:22px 26px 18px; border-bottom:1px solid #F0F3F7;
        display:flex; justify-content:space-between; align-items:center;
    }
    .modal-title { font-size:16px; font-weight:800; color:#0B1929; }
    .modal-close {
        width:32px; height:32px; border-radius:8px; border:none; background:#F1F5F9;
        cursor:pointer; display:flex; align-items:center; justify-content:center;
        font-size:18px; color:#64748B; line-height:1;
    }
    .modal-close:hover { background:#E2E8F0; }
</style>
@endpush

@section('content')

    {{-- Flash message --}}
    @if(session('success'))
    <div style="background:#D1FAE5;border:1px solid #6EE7B7;color:#065F46;padding:12px 18px;border-radius:12px;font-size:13px;font-weight:700;margin-bottom:18px;">
        {{ session('success') }}
    </div>
    @endif

    @if($errors->any())
    <div style="background:#FEE2E2;border:1px solid #FCA5A5;color:#991B1B;padding:12px 18px;border-radius:12px;font-size:13px;font-weight:700;margin-bottom:18px;">
        <ul style="margin:0;padding-left:16px;">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="card">
        <div class="card-head">
            <div class="card-title">Coupons liés à mes campagnes</div>
            <span style="font-size:12px;color:#94A3B8;font-weight:600;">{{ $coupons->total() }} coupon(s)</span>
        </div>

        @if($coupons->count() > 0)
        <table>
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Partenaire</th>
                    <th>Type</th>
                    <th>Valeur</th>
                    <th>Campagne</th>
                    <th>Utilisations</th>
                    <th>Statut</th>
                    <th>Expire le</th>
                </tr>
            </thead>
            <tbody>
                @foreach($coupons as $coupon)
                <tr>
                    <td style="font-family:monospace;font-size:13px;font-weight:800;color:#0B1929;letter-spacing:0.5px;">
                        {{ $coupon->code }}
                    </td>
                    <td style="font-weight:700;color:#334155;">{{ $coupon->partner_name }}</td>
                    <td>
                        <span class="badge {{ $coupon->discount_type === 'percent' ? 'badge-percent' : 'badge-fixed' }}">
                            {{ $coupon->discount_type === 'percent' ? 'Pourcentage' : 'Montant fixe' }}
                        </span>
                    </td>
                    <td style="font-weight:800;color:#0B1929;">
                        @if($coupon->discount_type === 'percent')
                            {{ $coupon->discount_value }}%
                        @else
                            {{ number_format($coupon->discount_value, 0, ',', ' ') }} F
                        @endif
                    </td>
                    <td style="font-weight:600;color:#475569;font-size:12px;">
                        {{ $coupon->campaign?->title ?? '—' }}
                    </td>
                    <td style="font-weight:700;color:#334155;">
                        {{ $coupon->uses_count }}
                        @if($coupon->max_uses)
                            <span style="color:#94A3B8;font-weight:600;">/ {{ $coupon->max_uses }}</span>
                        @endif
                    </td>
                    <td>
                        @if($coupon->is_active)
                            <span class="badge badge-active">Actif</span>
                        @else
                            <span class="badge badge-inactive">Inactif</span>
                        @endif
                    </td>
                    <td style="font-size:12px;font-weight:600;color:#64748B;">
                        {{ $coupon->expires_at ? $coupon->expires_at->format('d/m/Y') : '—' }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        @if($coupons->hasPages())
        <div class="pagination">
            <span>{{ $coupons->firstItem() }} à {{ $coupons->lastItem() }} sur {{ $coupons->total() }}</span>
            <div style="display:flex;gap:8px;">
                @if($coupons->onFirstPage())
                    <span style="padding:5px 12px;border-radius:8px;background:#F1F5F9;color:#94A3B8;font-size:12px;font-weight:700;">Précédent</span>
                @else
                    <a href="{{ $coupons->previousPageUrl() }}" style="padding:5px 12px;border-radius:8px;background:#F1F5F9;color:#334155;font-size:12px;font-weight:700;text-decoration:none;">Précédent</a>
                @endif
                @if($coupons->hasMorePages())
                    <a href="{{ $coupons->nextPageUrl() }}" style="padding:5px 12px;border-radius:8px;background:#3B82F6;color:#fff;font-size:12px;font-weight:700;text-decoration:none;">Suivant</a>
                @else
                    <span style="padding:5px 12px;border-radius:8px;background:#F1F5F9;color:#94A3B8;font-size:12px;font-weight:700;">Suivant</span>
                @endif
            </div>
        </div>
        @endif

        @else
        <div class="empty-state">
            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="none" stroke="#CBD5E1" stroke-width="1.2" viewBox="0 0 24 24" style="margin-bottom:12px;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-9-5.25h5.25M7.5 15h3M3.375 5.25c-.621 0-1.125.504-1.125 1.125v3.026a2.999 2.999 0 010 5.198v3.026c0 .621.504 1.125 1.125 1.125h17.25c.621 0 1.125-.504 1.125-1.125v-3.026a2.999 2.999 0 010-5.198V6.375c0-.621-.504-1.125-1.125-1.125H3.375z"/>
            </svg>
            <p style="font-size:14px;font-weight:700;color:#64748B;margin-bottom:6px;">Aucun coupon pour le moment</p>
            <p style="font-size:12px;color:#94A3B8;">Créez votre premier coupon en cliquant sur "+ Nouveau coupon".</p>
        </div>
        @endif
    </div>

@endsection

{{-- ── Create coupon modal ─────────────────────────────────────────────────── --}}
@push('scripts')
<div id="modal-coupon" class="modal-overlay" onclick="if(event.target===this)this.style.display='none'">
    <div class="modal-box">
        <div class="modal-header">
            <div class="modal-title">Nouveau coupon</div>
            <button class="modal-close" onclick="document.getElementById('modal-coupon').style.display='none'">&times;</button>
        </div>

        <form method="POST" action="{{ route('panel.advertiser.coupons.store') }}">
            @csrf
            <div class="form-section">
                <div class="form-grid">

                    {{-- Campagne --}}
                    <div class="form-group full">
                        <label class="form-label">Campagne <span style="color:#EF4444">*</span></label>
                        @if($campaigns->count() > 0)
                        <select name="campaign_id" class="form-input" required>
                            <option value="">— Choisir une campagne —</option>
                            @foreach($campaigns as $campaign)
                                <option value="{{ $campaign->id }}" {{ old('campaign_id') == $campaign->id ? 'selected' : '' }}>
                                    {{ $campaign->title }}
                                </option>
                            @endforeach
                        </select>
                        @else
                        <div style="padding:11px 14px;border:2px dashed #E5E9F0;border-radius:10px;font-size:13px;color:#94A3B8;font-weight:600;">
                            Aucune campagne disponible. Créez d'abord une campagne.
                        </div>
                        @endif
                    </div>

                    {{-- Code --}}
                    <div class="form-group">
                        <label class="form-label">Code coupon <span style="color:#EF4444">*</span></label>
                        <input type="text" name="code" class="form-input" value="{{ old('code') }}"
                               placeholder="ex : PROMO20" maxlength="50" required
                               style="text-transform:uppercase;font-family:monospace;letter-spacing:0.5px;">
                    </div>

                    {{-- Partenaire --}}
                    <div class="form-group">
                        <label class="form-label">Nom du partenaire <span style="color:#EF4444">*</span></label>
                        <input type="text" name="partner_name" class="form-input" value="{{ old('partner_name') }}"
                               placeholder="ex : Jumia" maxlength="100" required>
                    </div>

                    {{-- Type de remise --}}
                    <div class="form-group">
                        <label class="form-label">Type de remise <span style="color:#EF4444">*</span></label>
                        <select name="discount_type" class="form-input" required>
                            <option value="percent"  {{ old('discount_type') === 'percent'  ? 'selected' : '' }}>Pourcentage (%)</option>
                            <option value="fixed"    {{ old('discount_type') === 'fixed'    ? 'selected' : '' }}>Montant fixe (FCFA)</option>
                        </select>
                    </div>

                    {{-- Valeur --}}
                    <div class="form-group">
                        <label class="form-label">Valeur <span style="color:#EF4444">*</span></label>
                        <input type="number" name="discount_value" class="form-input" value="{{ old('discount_value') }}"
                               placeholder="ex : 20" min="1" required>
                    </div>

                    {{-- Description --}}
                    <div class="form-group full">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-input" placeholder="Détails du coupon…" maxlength="255">{{ old('description') }}</textarea>
                    </div>

                    {{-- Date d'expiration --}}
                    <div class="form-group">
                        <label class="form-label">Date d'expiration</label>
                        <input type="date" name="expires_at" class="form-input" value="{{ old('expires_at') }}">
                    </div>

                    {{-- Max utilisations --}}
                    <div class="form-group">
                        <label class="form-label">Nombre max d'utilisations</label>
                        <input type="number" name="max_uses" class="form-input" value="{{ old('max_uses') }}"
                               placeholder="Illimité si vide" min="1">
                    </div>

                </div>

                <div class="form-actions">
                    <button type="button" class="btn-cancel"
                            onclick="document.getElementById('modal-coupon').style.display='none'">
                        Annuler
                    </button>
                    <button type="submit" class="btn-submit">Créer le coupon</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    // Re-open modal on validation error so the user sees the error messages
    @if($errors->any())
        document.getElementById('modal-coupon').style.display = 'flex';
    @endif
</script>
@endpush
