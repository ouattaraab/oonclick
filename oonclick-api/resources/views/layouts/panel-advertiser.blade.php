<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — oon.click Annonceur</title>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
    <style>
        *, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Nunito',sans-serif; background:#F0F4F8; color:#1a2332; }
        .panel-wrap { display:flex; min-height:100vh; }

        /* --- SIDEBAR: Dark gradient navy (Design B) --- */
        .sidebar { width:240px; background:linear-gradient(180deg,#0B1929 0%,#0F2744 100%); display:flex; flex-direction:column; position:fixed; top:0; left:0; bottom:0; z-index:40; }
        .sidebar-brand { padding:20px; display:flex; align-items:center; gap:10px; border-bottom:1px solid rgba(255,255,255,0.06); }
        .sidebar-brand .logo-icon { width:38px; height:38px; background:linear-gradient(135deg,#3B82F6,#06B6D4); border-radius:12px; display:flex; align-items:center; justify-content:center; color:#fff; font-weight:900; font-size:16px; box-shadow:0 4px 12px rgba(59,130,246,0.3); flex-shrink:0; }
        .sidebar-brand .logo-text { font-size:20px; font-weight:900; color:#fff; }
        .sidebar-brand .logo-text span { background:linear-gradient(135deg,#38BDF8,#06B6D4); -webkit-background-clip:text; -webkit-text-fill-color:transparent; }

        .sidebar-nav { flex:1; padding:20px 12px; overflow-y:auto; }
        .nav-item { display:flex; align-items:center; gap:10px; padding:11px 14px; border-radius:10px; font-size:13px; font-weight:700; color:#7B9BC5; cursor:pointer; text-decoration:none; transition:all .2s; margin-bottom:3px; }
        .nav-item:hover { background:rgba(255,255,255,0.05); color:#BAD4F0; }
        .nav-item.active { background:linear-gradient(135deg,rgba(59,130,246,0.2),rgba(6,182,212,0.15)); color:#fff; }
        .nav-item svg { width:20px; height:20px; opacity:.6; flex-shrink:0; }
        .nav-item.active svg { opacity:1; }
        .nav-group { margin-bottom:8px; }

        /* Balance widget */
        .sidebar-bottom { padding:16px; border-top:1px solid rgba(255,255,255,0.06); }
        .balance-box { background:linear-gradient(135deg,#1E40AF,#0EA5E9); border-radius:14px; padding:16px; color:#fff; }
        .balance-label { font-size:10px; font-weight:700; opacity:0.7; text-transform:uppercase; letter-spacing:1px; }
        .balance-value { font-size:22px; font-weight:900; margin:4px 0; }
        .balance-sub { font-size:10px; opacity:0.6; font-weight:600; }

        /* User */
        .sidebar-footer-user { padding:12px 16px; display:flex; align-items:center; gap:10px; border-top:1px solid rgba(255,255,255,0.06); }
        .sidebar-footer-user .av { width:32px; height:32px; border-radius:8px; background:linear-gradient(135deg,#334155,#475569); color:#fff; font-size:11px; font-weight:700; display:flex; align-items:center; justify-content:center; flex-shrink:0; overflow:hidden; }
        .sidebar-footer-user .av img { width:100%; height:100%; object-fit:cover; }
        .sidebar-footer-user .name { font-size:12px; font-weight:600; color:#E2E8F0; }
        .sidebar-footer-user .role { font-size:10px; color:#64748B; }

        /* --- MAIN --- */
        .main-area { flex:1; margin-left:240px; display:flex; flex-direction:column; min-height:100vh; }

        .topbar { height:60px; background:#fff; border-bottom:1px solid #E5E9F0; display:flex; align-items:center; justify-content:space-between; padding:0 28px; position:sticky; top:0; z-index:30; box-shadow:0 1px 3px rgba(0,0,0,0.04); }
        .topbar h1 { font-size:17px; font-weight:800; color:#0B1929; }
        .topbar-right { display:flex; align-items:center; gap:12px; }
        .avatar-top { width:36px; height:36px; border-radius:50%; background:linear-gradient(135deg,#3B82F6,#06B6D4); color:#fff; font-size:13px; font-weight:800; display:flex; align-items:center; justify-content:center; overflow:hidden; }
        .avatar-top img { width:100%; height:100%; object-fit:cover; }
        .btn-new { background:linear-gradient(135deg,#3B82F6,#2563EB); color:#fff; border:none; padding:9px 18px; border-radius:10px; font-weight:700; font-size:12px; cursor:pointer; box-shadow:0 3px 10px rgba(59,130,246,0.25); font-family:inherit; text-decoration:none; display:inline-flex; align-items:center; gap:5px; }
        .btn-new:hover { box-shadow:0 5px 16px rgba(59,130,246,0.35); transform:translateY(-1px); }

        .content { flex:1; padding:24px 28px; }
        .page-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; }
        .page-title { font-size:22px; font-weight:800; color:#0B1929; letter-spacing:-0.3px; }

        /* --- KPI: GRADIENT CARDS (Design B signature) --- */
        .hero-stats { display:grid; grid-template-columns:repeat(4,1fr); gap:14px; margin-bottom:24px; }
        .hero-stat { border-radius:16px; padding:20px; color:#fff; position:relative; overflow:hidden; }
        .hero-stat::after { content:''; position:absolute; top:-20px; right:-20px; width:100px; height:100px; background:rgba(255,255,255,0.1); border-radius:50%; }
        .hero-stat.blue { background:linear-gradient(135deg,#3B82F6,#2563EB); }
        .hero-stat.cyan { background:linear-gradient(135deg,#06B6D4,#0891B2); }
        .hero-stat.emerald { background:linear-gradient(135deg,#10B981,#059669); }
        .hero-stat.violet { background:linear-gradient(135deg,#8B5CF6,#7C3AED); }
        .hero-stat.amber { background:linear-gradient(135deg,#F59E0B,#D97706); }
        .hs-label { font-size:11px; font-weight:700; opacity:0.85; margin-bottom:8px; }
        .hs-value { font-size:28px; font-weight:900; }
        .hs-sub { font-size:10px; opacity:0.7; font-weight:600; margin-top:4px; }

        /* --- CARDS / TABLE --- */
        .card { background:#fff; border-radius:16px; border:1px solid #E5E9F0; overflow:hidden; margin-bottom:20px; }
        .card-head { padding:18px 22px; display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid #F0F3F7; }
        .card-title { font-size:15px; font-weight:800; color:#0B1929; }
        .card-link { font-size:12px; font-weight:600; color:#3B82F6; text-decoration:none; }
        .card-body { padding:18px 22px; }
        .tab-bar { display:flex; gap:2px; background:#F0F3F7; border-radius:8px; padding:2px; }
        .tab { padding:5px 14px; font-size:11px; font-weight:700; color:#64748B; border-radius:6px; cursor:pointer; border:none; background:none; font-family:inherit; }
        .tab.active { background:#fff; color:#0B1929; box-shadow:0 1px 3px rgba(0,0,0,0.06); }

        table { width:100%; border-collapse:collapse; }
        thead th { text-align:left; font-size:10px; font-weight:800; text-transform:uppercase; letter-spacing:0.8px; color:#8BA4C4; padding:12px 20px; background:#F8FAFC; }
        tbody td { padding:14px 20px; font-size:13px; color:#334155; border-bottom:1px solid #F0F3F7; font-weight:600; }
        tbody tr:hover td { background:#FAFCFF; }
        tbody tr:last-child td { border-bottom:none; }

        .badge { display:inline-flex; align-items:center; gap:4px; padding:4px 10px; border-radius:8px; font-size:11px; font-weight:700; }
        .badge-active { background:#D1FAE5; color:#065F46; }
        .badge-pending { background:#FEF3C7; color:#78350F; }
        .badge-video { background:#DBEAFE; color:#1E40AF; }
        .badge-flash { background:#F3E8FF; color:#6B21A8; }
        .badge-gray { background:#F1F5F9; color:#475569; }

        .progress-cell { display:flex; align-items:center; gap:8px; }
        .mini-bar { width:80px; height:6px; background:#E5E9F0; border-radius:3px; overflow:hidden; }
        .mini-fill { height:100%; border-radius:3px; background:linear-gradient(90deg,#3B82F6,#06B6D4); }
        .pct { font-size:11px; font-weight:800; color:#0B1929; }
        .action-link { font-size:11px; font-weight:700; color:#3B82F6; text-decoration:none; }
        .action-link:hover { text-decoration:underline; }

        /* Form */
        .form-section { padding:22px; }
        .form-grid { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
        .form-group { display:flex; flex-direction:column; gap:5px; }
        .form-group.full { grid-column:span 2; }
        .form-label { font-size:11px; font-weight:700; color:#64748B; text-transform:uppercase; letter-spacing:0.3px; }
        .form-input { padding:11px 14px; border:2px solid #E5E9F0; border-radius:10px; font-size:13px; font-family:inherit; color:#0B1929; font-weight:600; outline:none; transition:border .15s; }
        .form-input:focus { border-color:#3B82F6; }
        select.form-input { appearance:none; background:#fff url("data:image/svg+xml,%3Csvg width='12' height='8' viewBox='0 0 12 8' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M1 1.5L6 6.5L11 1.5' stroke='%2394A3B8' stroke-width='2' stroke-linecap='round'/%3E%3C/svg%3E") no-repeat right 14px center; }
        textarea.form-input { resize:vertical; min-height:80px; }
        .form-actions { display:flex; gap:10px; margin-top:20px; justify-content:flex-end; }
        .btn-cancel { padding:10px 22px; border-radius:10px; font-weight:700; font-size:13px; border:2px solid #E5E9F0; background:#fff; color:#64748B; cursor:pointer; font-family:inherit; }
        .btn-submit { padding:10px 24px; border-radius:10px; font-weight:800; font-size:13px; background:linear-gradient(135deg,#3B82F6,#2563EB); color:#fff; border:none; cursor:pointer; box-shadow:0 3px 10px rgba(59,130,246,0.25); font-family:inherit; }

        .pagination { display:flex; align-items:center; justify-content:space-between; padding:12px 20px; border-top:1px solid #F0F3F7; font-size:12px; color:#94A3B8; }
        .empty-state { text-align:center; padding:48px 20px; color:#94A3B8; }
        .empty-state p { font-size:13px; font-weight:500; }
        .chart-container { padding:20px; min-height:250px; }

        @@media (max-width:1024px) {
            .sidebar { display:none; }
            .main-area { margin-left:0; }
            .hero-stats { grid-template-columns:repeat(2,1fr); }
        }
    </style>
    @stack('styles')
</head>
<body>
    <div class="panel-wrap">
        <aside class="sidebar">
            <div class="sidebar-brand">
                <div class="logo-icon">O</div>
                <div class="logo-text"><span>oon</span>.click</div>
            </div>
            <nav class="sidebar-nav">
                @yield('sidebar-nav')
            </nav>
            <div class="sidebar-bottom">
                <div class="balance-box">
                    <div class="balance-label">Solde disponible</div>
                    <div class="balance-value">{{ number_format($walletBalance ?? 0, 0, ',', ' ') }} F</div>
                    <div class="balance-sub">Budget restant toutes campagnes</div>
                </div>
            </div>
            <div class="sidebar-footer-user">
                <div class="av">
                    @if(auth()->user()->avatar_path)
                        <img src="{{ asset('storage/' . auth()->user()->avatar_path) }}" alt="Avatar">
                    @else
                        {{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}
                    @endif
                </div>
                <div>
                    <div class="name">{{ Str::limit(auth()->user()->name ?? auth()->user()->phone, 20) }}</div>
                    <div class="role">Annonceur</div>
                </div>
            </div>
        </aside>

        <div class="main-area">
            <header class="topbar">
                <h1>@yield('topbar-title', 'Tableau de bord')</h1>
                <div class="topbar-right">
                    @yield('topbar-actions')
                    <div class="avatar-top">
                        @if(auth()->user()->avatar_path)
                            <img src="{{ asset('storage/' . auth()->user()->avatar_path) }}" alt="Avatar">
                        @else
                            {{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}
                        @endif
                    </div>
                </div>
            </header>
            <main class="content">
                @yield('content')
            </main>
        </div>
    </div>
    @stack('scripts')
</body>
</html>
