@extends('layouts.panel-advertiser', ['walletBalance' => $walletBalance])

@section('title', 'Offres cashback partenaires')
@section('topbar-title', 'Offres cashback partenaires')

@section('sidebar-nav')
    @include('panel.advertiser._sidebar', ['active' => 'offers'])
@endsection

@push('styles')
<style>
    .offers-grid {
        display:grid;
        grid-template-columns:repeat(auto-fill, minmax(280px, 1fr));
        gap:18px;
        padding:22px;
    }

    .offer-card {
        border:1px solid #E5E9F0;
        border-radius:16px;
        background:#fff;
        overflow:hidden;
        transition:box-shadow .2s, transform .2s;
    }
    .offer-card:hover {
        box-shadow:0 8px 24px rgba(0,0,0,0.08);
        transform:translateY(-2px);
    }

    .offer-card-banner {
        height:6px;
        background:linear-gradient(90deg,#3B82F6,#06B6D4);
    }

    .offer-card-body {
        padding:18px 20px 20px;
    }

    .offer-logo-row {
        display:flex;
        align-items:center;
        gap:12px;
        margin-bottom:14px;
    }
    .offer-logo {
        width:44px; height:44px;
        border-radius:12px;
        background:linear-gradient(135deg,#EFF6FF,#DBEAFE);
        display:flex; align-items:center; justify-content:center;
        font-size:18px; font-weight:900; color:#2563EB;
        flex-shrink:0;
        overflow:hidden;
    }
    .offer-logo img { width:100%; height:100%; object-fit:cover; border-radius:12px; }
    .offer-partner-name {
        font-size:15px; font-weight:800; color:#0B1929;
    }
    .offer-category {
        font-size:10px; font-weight:700; color:#64748B;
        text-transform:uppercase; letter-spacing:0.5px;
        margin-top:2px;
    }

    .offer-desc {
        font-size:12px; color:#64748B; font-weight:600;
        line-height:1.5; margin-bottom:16px;
        display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical;
        overflow:hidden;
    }

    .offer-meta {
        display:flex; flex-direction:column; gap:8px;
        margin-bottom:16px;
    }
    .offer-meta-row {
        display:flex; align-items:center; justify-content:space-between;
    }
    .offer-meta-label {
        font-size:10px; font-weight:700; color:#94A3B8;
        text-transform:uppercase; letter-spacing:0.5px;
    }
    .offer-meta-value {
        font-size:13px; font-weight:800; color:#0B1929;
    }

    .cashback-badge {
        display:inline-flex; align-items:center; gap:4px;
        background:linear-gradient(135deg,#D1FAE5,#A7F3D0);
        color:#065F46; padding:5px 12px; border-radius:20px;
        font-size:13px; font-weight:800;
    }

    .promo-code-box {
        display:flex; align-items:center; justify-content:space-between;
        background:#F8FAFC; border:1.5px dashed #CBD5E1;
        border-radius:10px; padding:8px 14px;
        margin-top:6px;
    }
    .promo-code-text {
        font-family:monospace; font-size:13px; font-weight:800;
        color:#0B1929; letter-spacing:0.5px;
    }
    .promo-copy-btn {
        border:none; background:none; cursor:pointer;
        font-size:11px; font-weight:700; color:#3B82F6;
        padding:2px 6px; border-radius:6px;
        transition:background .15s;
    }
    .promo-copy-btn:hover { background:#EFF6FF; }

    .offer-expiry {
        font-size:11px; font-weight:600; color:#94A3B8; margin-top:12px;
        display:flex; align-items:center; gap:5px;
    }
    .offer-expiry svg { width:13px; height:13px; flex-shrink:0; }

    .badge-expired { background:#FEE2E2; color:#991B1B; }
</style>
@endpush

@section('content')

    <div class="card" style="margin-bottom:0;border-radius:16px 16px 0 0;border-bottom:none;">
        <div class="card-head">
            <div class="card-title">Offres cashback disponibles</div>
            <span style="font-size:12px;color:#94A3B8;font-weight:600;">{{ $offers->total() }} offre(s)</span>
        </div>
    </div>

    @if($offers->count() > 0)

    <div style="background:#fff;border:1px solid #E5E9F0;border-top:none;border-radius:0 0 16px 16px;overflow:hidden;">
        <div class="offers-grid">
            @foreach($offers as $offer)
            <div class="offer-card">
                <div class="offer-card-banner"></div>
                <div class="offer-card-body">

                    {{-- Partner logo + name --}}
                    <div class="offer-logo-row">
                        <div class="offer-logo">
                            @if($offer->logo_url)
                                <img src="{{ $offer->logo_url }}" alt="{{ $offer->partner_name }}">
                            @else
                                {{ strtoupper(substr($offer->partner_name, 0, 1)) }}
                            @endif
                        </div>
                        <div>
                            <div class="offer-partner-name">{{ $offer->partner_name }}</div>
                            @if($offer->category)
                                <div class="offer-category">{{ $offer->category }}</div>
                            @endif
                        </div>
                    </div>

                    {{-- Description --}}
                    @if($offer->description)
                    <div class="offer-desc">{{ $offer->description }}</div>
                    @endif

                    {{-- Cashback percent --}}
                    <div class="offer-meta">
                        <div class="offer-meta-row">
                            <span class="offer-meta-label">Cashback</span>
                            <span class="cashback-badge">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z"/>
                                </svg>
                                {{ number_format($offer->cashback_percent, 1) }}%
                            </span>
                        </div>
                    </div>

                    {{-- Promo code --}}
                    @if($offer->promo_code)
                    <div>
                        <div style="font-size:10px;font-weight:700;color:#94A3B8;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:6px;">Code promo</div>
                        <div class="promo-code-box">
                            <span class="promo-code-text" id="code-{{ $offer->id }}">{{ $offer->promo_code }}</span>
                            <button class="promo-copy-btn" onclick="copyCode('{{ $offer->promo_code }}', this)">Copier</button>
                        </div>
                    </div>
                    @endif

                    {{-- Expiry --}}
                    <div class="offer-expiry">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/>
                        </svg>
                        @if($offer->expires_at)
                            Expire le {{ $offer->expires_at->format('d/m/Y') }}
                        @else
                            Sans date d'expiration
                        @endif
                    </div>

                </div>
            </div>
            @endforeach
        </div>

        @if($offers->hasPages())
        <div class="pagination" style="border-top:1px solid #F0F3F7;">
            <span>{{ $offers->firstItem() }} à {{ $offers->lastItem() }} sur {{ $offers->total() }}</span>
            <div style="display:flex;gap:8px;">
                @if($offers->onFirstPage())
                    <span style="padding:5px 12px;border-radius:8px;background:#F1F5F9;color:#94A3B8;font-size:12px;font-weight:700;">Précédent</span>
                @else
                    <a href="{{ $offers->previousPageUrl() }}" style="padding:5px 12px;border-radius:8px;background:#F1F5F9;color:#334155;font-size:12px;font-weight:700;text-decoration:none;">Précédent</a>
                @endif
                @if($offers->hasMorePages())
                    <a href="{{ $offers->nextPageUrl() }}" style="padding:5px 12px;border-radius:8px;background:#3B82F6;color:#fff;font-size:12px;font-weight:700;text-decoration:none;">Suivant</a>
                @else
                    <span style="padding:5px 12px;border-radius:8px;background:#F1F5F9;color:#94A3B8;font-size:12px;font-weight:700;">Suivant</span>
                @endif
            </div>
        </div>
        @endif
    </div>

    @else
    <div style="background:#fff;border:1px solid #E5E9F0;border-top:none;border-radius:0 0 16px 16px;">
        <div class="empty-state">
            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="none" stroke="#CBD5E1" stroke-width="1.2" viewBox="0 0 24 24" style="margin-bottom:12px;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z"/>
            </svg>
            <p style="font-size:14px;font-weight:700;color:#64748B;margin-bottom:6px;">Aucune offre cashback disponible</p>
            <p style="font-size:12px;color:#94A3B8;">Les offres partenaires apparaîtront ici lorsqu'elles seront publiées par l'équipe oon.click.</p>
        </div>
    </div>
    @endif

@endsection

@push('scripts')
<script>
function copyCode(code, btn) {
    navigator.clipboard.writeText(code).then(function () {
        var original = btn.textContent;
        btn.textContent = 'Copié !';
        btn.style.color = '#059669';
        setTimeout(function () {
            btn.textContent = original;
            btn.style.color = '';
        }, 2000);
    });
}
</script>
@endpush
