@extends('layouts.panel-advertiser', ['walletBalance' => $walletBalance])

@section('title', $campaign->title)
@section('topbar-title', $campaign->title)
@section('topbar-actions')
    <a href="{{ route('panel.advertiser.campaigns.pdf', $campaign) }}"
       style="font-size:13px;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:5px;background:#0f172a;color:#f59e0b;padding:8px 16px;border-radius:8px">
        Telecharger PDF
    </a>
    <a href="{{ route('panel.advertiser.campaigns') }}" class="btn-cancel" style="font-size:13px;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:5px">
        &#8592; Mes campagnes
    </a>
@endsection

@section('sidebar-nav')
    @include('panel.advertiser._sidebar', ['active' => 'campaigns'])
@endsection

@section('content')

    @if(session('success'))
    <div style="background:#D1FAE5;border:1px solid #6EE7B7;color:#065F46;padding:12px 16px;border-radius:12px;margin-bottom:20px;font-size:13px;font-weight:700">
        {{ session('success') }}
    </div>
    @endif

    {{-- Header card --}}
    <div style="background:linear-gradient(135deg,#0B1929,#0F2744);border-radius:20px;padding:28px;margin-bottom:22px;position:relative;overflow:hidden">
        <div style="position:absolute;top:-40px;right:-40px;width:200px;height:200px;background:rgba(59,130,246,0.08);border-radius:50%"></div>
        <div style="position:absolute;bottom:-30px;right:80px;width:120px;height:120px;background:rgba(6,182,212,0.06);border-radius:50%"></div>
        <div style="position:relative">
            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;margin-bottom:20px">
                <div>
                    <div style="font-size:11px;font-weight:700;color:#7B9BC5;text-transform:uppercase;letter-spacing:1px;margin-bottom:6px">Campagne</div>
                    <h2 style="font-size:24px;font-weight:900;color:#fff;margin-bottom:6px;letter-spacing:-0.3px">{{ $campaign->title }}</h2>
                    @if($campaign->description)
                    <p style="font-size:13px;color:#7B9BC5;line-height:1.5;max-width:500px">{{ $campaign->description }}</p>
                    @endif
                </div>
                <div style="display:flex;gap:8px;align-items:center;flex-shrink:0">
                    <span class="badge campaign-status {{ $campaign->status === 'active' ? 'badge-active' : ($campaign->status === 'pending_review' ? 'badge-pending' : ($campaign->status === 'rejected' ? '' : 'badge-gray')) }}"
                        @if($campaign->status === 'rejected') style="background:#FEE2E2;color:#B91C1C" @endif>
                        {{ $campaign->status === 'active' ? 'Actif' : ($campaign->status === 'pending_review' ? 'En attente de validation' : ($campaign->status === 'rejected' ? 'Rejeté' : ucfirst($campaign->status))) }}
                    </span>
                    <span class="badge badge-video">{{ ucfirst($campaign->format) }}</span>
                </div>
            </div>

            {{-- KPI row --}}
            <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px">
                <div style="background:rgba(255,255,255,0.06);border-radius:14px;padding:16px;border:1px solid rgba(255,255,255,0.08)">
                    <div style="font-size:10px;font-weight:700;color:#7B9BC5;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:6px">Budget</div>
                    <div style="font-size:20px;font-weight:900;color:#fff">{{ number_format($campaign->budget, 0, ',', ' ') }}<span style="font-size:11px;font-weight:600;color:#7B9BC5;margin-left:3px">F</span></div>
                </div>
                <div style="background:rgba(255,255,255,0.06);border-radius:14px;padding:16px;border:1px solid rgba(255,255,255,0.08)">
                    <div style="font-size:10px;font-weight:700;color:#7B9BC5;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:6px">Coût / vue</div>
                    <div style="font-size:20px;font-weight:900;color:#fff">{{ number_format($campaign->cost_per_view, 0, ',', ' ') }}<span style="font-size:11px;font-weight:600;color:#7B9BC5;margin-left:3px">F</span></div>
                </div>
                <div style="background:rgba(255,255,255,0.06);border-radius:14px;padding:16px;border:1px solid rgba(255,255,255,0.08)">
                    <div style="font-size:10px;font-weight:700;color:#7B9BC5;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:6px">Vues réalisées</div>
                    <div id="live-views" style="font-size:20px;font-weight:900;color:#38BDF8">{{ number_format($campaign->views_count, 0, ',', ' ') }}</div>
                </div>
                <div style="background:rgba(255,255,255,0.06);border-radius:14px;padding:16px;border:1px solid rgba(255,255,255,0.08)">
                    <div style="font-size:10px;font-weight:700;color:#7B9BC5;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:6px">Vues max</div>
                    <div style="font-size:20px;font-weight:900;color:#fff">{{ number_format($campaign->max_views, 0, ',', ' ') }}</div>
                </div>
            </div>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:2fr 1fr;gap:20px">

        {{-- LEFT --}}
        <div>

            {{-- Progress --}}
            <div class="card" style="margin-bottom:20px">
                <div class="card-head">
                    <div class="card-title">Progression des vues</div>
                    @php
                        $pct = ($campaign->max_views > 0) ? round($campaign->views_count / $campaign->max_views * 100) : 0;
                    @endphp
                    <span id="live-progress-text" style="font-size:22px;font-weight:900;color:#0B1929">{{ $pct }}%</span>
                </div>
                <div class="card-body">
                    <div style="height:14px;background:#E5E9F0;border-radius:7px;overflow:hidden;margin-bottom:12px">
                        <div id="live-progress-bar" style="height:100%;width:{{ $pct }}%;background:linear-gradient(90deg,#3B82F6,#06B6D4);border-radius:7px;transition:width .4s"></div>
                    </div>
                    <div style="display:flex;justify-content:space-between;font-size:12px;font-weight:700;color:#8BA4C4">
                        <span><span id="live-remaining-views">{{ number_format($campaign->views_count, 0, ',', ' ') }}</span> vues réalisées</span>
                        <span>{{ number_format($campaign->max_views, 0, ',', ' ') }} vues max</span>
                    </div>

                    @php
                        $spent = $campaign->views_count * $campaign->cost_per_view;
                        $remaining = max(0, $campaign->budget - $spent);
                        $spentPct = ($campaign->budget > 0) ? round($spent / $campaign->budget * 100) : 0;
                    @endphp
                    <div style="margin-top:20px;padding-top:20px;border-top:1px solid #F0F3F7">
                        <div style="font-size:11px;font-weight:700;color:#8BA4C4;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:10px">Budget consommé — {{ $spentPct }}%</div>
                        <div style="height:8px;background:#E5E9F0;border-radius:4px;overflow:hidden;margin-bottom:10px">
                            <div style="height:100%;width:{{ $spentPct }}%;background:linear-gradient(90deg,#10B981,#F59E0B);border-radius:4px"></div>
                        </div>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:10px">
                            <div style="padding:10px 14px;background:#F0FDF4;border-radius:10px">
                                <div style="font-size:10px;font-weight:700;color:#15803D;text-transform:uppercase;letter-spacing:0.3px">Dépensé</div>
                                <div id="live-spent" style="font-size:16px;font-weight:800;color:#15803D">{{ number_format($spent, 0, ',', ' ') }} F</div>
                            </div>
                            <div style="padding:10px 14px;background:#EFF6FF;border-radius:10px">
                                <div style="font-size:10px;font-weight:700;color:#1D4ED8;text-transform:uppercase;letter-spacing:0.3px">Restant</div>
                                <div id="live-remaining" style="font-size:16px;font-weight:800;color:#1D4ED8">{{ number_format($remaining, 0, ',', ' ') }} F</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Media --}}
            @if($campaign->media_url || $campaign->thumbnail_url)
            <div class="card">
                <div class="card-head">
                    <div class="card-title">Medias</div>
                </div>
                <div class="card-body">
                    @if($campaign->thumbnail_url)
                    <div style="margin-bottom:16px">
                        <div style="font-size:11px;font-weight:700;color:#8BA4C4;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:8px">Miniature</div>
                        <img src="{{ $campaign->thumbnail_url }}" alt="Miniature" style="max-width:100%;max-height:220px;border-radius:12px;border:1px solid #E5E9F0;object-fit:cover">
                    </div>
                    @endif
                    @if($campaign->media_url)
                    <div>
                        <div style="font-size:11px;font-weight:700;color:#8BA4C4;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:6px">URL du media</div>
                        <a href="{{ $campaign->media_url }}" target="_blank" style="font-size:12px;color:#3B82F6;word-break:break-all;font-weight:700">{{ $campaign->media_url }}</a>
                    </div>
                    @endif
                </div>
            </div>
            @endif

        </div>

        {{-- RIGHT --}}
        <div>

            {{-- Dates --}}
            <div class="card" style="margin-bottom:20px">
                <div class="card-head">
                    <div class="card-title">Calendrier</div>
                </div>
                <div class="card-body">
                    <div style="display:flex;flex-direction:column;gap:14px">
                        <div style="padding:12px;background:#F8FAFC;border-radius:10px">
                            <div style="font-size:10px;font-weight:700;color:#8BA4C4;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:4px">Créé le</div>
                            <div style="font-size:14px;font-weight:700;color:#0B1929">{{ $campaign->created_at->format('d/m/Y') }}</div>
                            <div style="font-size:11px;color:#94A3B8">{{ $campaign->created_at->format('H:i') }}</div>
                        </div>
                        <div style="padding:12px;background:#F8FAFC;border-radius:10px">
                            <div style="font-size:10px;font-weight:700;color:#8BA4C4;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:4px">Date de début</div>
                            <div style="font-size:14px;font-weight:700;color:#0B1929">{{ $campaign->starts_at ? $campaign->starts_at->format('d/m/Y') : '—' }}</div>
                            @if($campaign->starts_at)<div style="font-size:11px;color:#94A3B8">{{ $campaign->starts_at->format('H:i') }}</div>@endif
                        </div>
                        <div style="padding:12px;background:#F8FAFC;border-radius:10px">
                            <div style="font-size:10px;font-weight:700;color:#8BA4C4;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:4px">Date de fin</div>
                            <div style="font-size:14px;font-weight:700;color:#0B1929">{{ $campaign->ends_at ? $campaign->ends_at->format('d/m/Y') : '—' }}</div>
                            @if($campaign->ends_at)<div style="font-size:11px;color:#94A3B8">{{ $campaign->ends_at->format('H:i') }}</div>@endif
                        </div>
                        @if($campaign->duration_seconds)
                        <div style="padding:12px;background:#F8FAFC;border-radius:10px">
                            <div style="font-size:10px;font-weight:700;color:#8BA4C4;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:4px">Durée de la pub</div>
                            <div style="font-size:14px;font-weight:700;color:#0B1929">{{ $campaign->duration_seconds }} secondes</div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Status info --}}
            @if($campaign->status === 'draft')
            <div style="background:#EFF6FF;border:1px solid #BFDBFE;border-radius:14px;padding:16px">
                <div style="font-size:13px;font-weight:700;color:#1E40AF;margin-bottom:6px">&#128176; Paiement requis</div>
                <div style="font-size:12px;color:#1D4ED8;line-height:1.5;margin-bottom:12px">Votre campagne est prête. Procédez au paiement de <strong>{{ number_format($campaign->budget, 0, ',', ' ') }} FCFA</strong> pour la soumettre à validation.</div>
                <form action="{{ route('panel.advertiser.campaigns.pay', $campaign) }}" method="POST" style="display:inline">
                    @csrf
                    <button type="submit" style="background:linear-gradient(135deg,#059669,#10B981);color:#fff;border:none;padding:10px 24px;border-radius:10px;font-size:13px;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:6px">
                        &#128179; Payer {{ number_format($campaign->budget, 0, ',', ' ') }} FCFA
                    </button>
                </form>
            </div>
            @elseif($campaign->status === 'pending_review')
            <div style="background:#FFF7ED;border:1px solid #FED7AA;border-radius:14px;padding:16px">
                <div style="font-size:13px;font-weight:700;color:#92400E;margin-bottom:6px">&#9201; En attente de validation</div>
                <div style="font-size:12px;color:#B45309;line-height:1.5">Votre campagne est en cours de modération par notre équipe. Vous serez notifié dès qu'elle sera approuvée.</div>
            </div>
            @elseif($campaign->status === 'rejected')
            <div style="background:#FEF2F2;border:1px solid #FECACA;border-radius:14px;padding:16px">
                <div style="font-size:13px;font-weight:700;color:#991B1B;margin-bottom:6px">&#10007; Campagne rejetée</div>
                <div style="font-size:12px;color:#B91C1C;line-height:1.5">Cette campagne a été rejetée. Contactez notre support pour plus d'informations.</div>
            </div>
            @elseif($campaign->status === 'active')
            <div style="background:#F0FDF4;border:1px solid #BBF7D0;border-radius:14px;padding:16px">
                <div style="font-size:13px;font-weight:700;color:#166534;margin-bottom:6px">&#10003; Campagne active</div>
                <div style="font-size:12px;color:#15803D;line-height:1.5">Votre campagne est actuellement en diffusion sur la plateforme.</div>
            </div>
            @endif

        </div>
    </div>

@endsection

@push('styles')
<style>
.live-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 10px;
    border-radius: 8px;
    font-size: 10px;
    font-weight: 800;
    background: #D1FAE5;
    color: #065F46;
    animation: livePulse 2s infinite;
    margin-left: 6px;
    vertical-align: middle;
}
.live-badge::before {
    content: '';
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #22C55E;
    flex-shrink: 0;
}
@keyframes livePulse {
    0%, 100% { opacity: 1; }
    50%       { opacity: 0.6; }
}
</style>
@endpush

@push('scripts')
<script src="https://js.pusher.com/8.0/pusher.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var campaignId = {{ $campaign->id }};
    var status     = '{{ $campaign->status }}';

    // Only run real-time updates for active campaigns
    if (status !== 'active') return;

    // Inject the live indicator badge next to the status badge
    var statusBadge = document.querySelector('.campaign-status');
    if (statusBadge) {
        var liveEl       = document.createElement('span');
        liveEl.className = 'live-badge';
        liveEl.textContent = 'EN DIRECT';
        statusBadge.parentNode.insertBefore(liveEl, statusBadge.nextSibling);
    }

    /**
     * Apply a progress payload to all live DOM targets.
     * Expects: { views_count, max_views, budget_used, budget, remaining_views, status }
     */
    function updateProgress(data) {
        var fmt = new Intl.NumberFormat('fr-FR');

        // KPI card — views count (top row)
        var viewsEl = document.getElementById('live-views');
        if (viewsEl) viewsEl.textContent = fmt.format(data.views_count);

        // Progress bar — views progression
        var pct = data.max_views > 0 ? (data.views_count / data.max_views * 100) : 0;

        var progressBar = document.getElementById('live-progress-bar');
        if (progressBar) progressBar.style.width = pct.toFixed(1) + '%';

        var progressText = document.getElementById('live-progress-text');
        if (progressText) progressText.textContent = Math.round(pct) + '%';

        // Progress footer — "X vues réalisées"
        var remViewsEl = document.getElementById('live-remaining-views');
        if (remViewsEl) remViewsEl.textContent = fmt.format(data.views_count);

        // Budget consumed
        var spentEl = document.getElementById('live-spent');
        if (spentEl) spentEl.textContent = fmt.format(data.budget_used) + ' F';

        var remainEl = document.getElementById('live-remaining');
        if (remainEl) remainEl.textContent = fmt.format(data.budget - data.budget_used) + ' F';

        // Full reload when campaign transitions to completed
        if (data.status === 'completed') {
            location.reload();
        }
    }

    // ── WebSocket via Pusher ──────────────────────────────────────────────────
    var pusherKey     = '{{ config("broadcasting.connections.pusher.key") }}';
    var pusherCluster = '{{ config("broadcasting.connections.pusher.options.cluster") }}';

    if (pusherKey && pusherKey !== '' && typeof Pusher !== 'undefined') {
        var pusher  = new Pusher(pusherKey, { cluster: pusherCluster });
        var channel = pusher.subscribe('campaign.' + campaignId);
        channel.bind('progress.updated', updateProgress);
    } else {
        // ── Polling fallback — every 10 seconds ───────────────────────────────
        setInterval(function () {
            fetch('/panel/advertiser/campaigns/' + campaignId + '/progress', {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(function (r) { return r.json(); })
            .then(updateProgress)
            .catch(function () { /* silent — polling will retry */ });
        }, 10000);
    }
});
</script>
@endpush
