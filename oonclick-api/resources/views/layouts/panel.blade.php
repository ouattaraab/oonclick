<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — oon.click {{ $panelLabel ?? 'Admin' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
    <style>
        *, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Inter',system-ui,sans-serif; background:#F1F5F9; color:#1E293B; }

        /* ============================================================
           LAYOUT
           ============================================================ */
        .panel-wrap { display:flex; min-height:100vh; }

        /* --- SIDEBAR --- */
        .sidebar { width:260px; background:linear-gradient(180deg,#0F172A 0%,#1E293B 100%); display:flex; flex-direction:column; position:fixed; top:0; left:0; bottom:0; z-index:40; transition:transform .25s ease; }
        .sidebar-brand { padding:22px 20px; display:flex; align-items:center; gap:12px; border-bottom:1px solid rgba(255,255,255,0.06); }
        .sidebar-brand .logo-icon { width:36px; height:36px; background:linear-gradient(135deg,#38BDF8,#0EA5E9); border-radius:10px; display:flex; align-items:center; justify-content:center; color:#fff; font-weight:900; font-size:14px; flex-shrink:0; }
        .sidebar-brand .logo-text { font-size:18px; font-weight:800; color:#F8FAFC; letter-spacing:-0.5px; }
        .sidebar-brand .logo-text span { color:#38BDF8; }

        .sidebar-nav { flex:1; padding:16px 12px; overflow-y:auto; }
        .nav-group { margin-bottom:22px; }
        .nav-group-label { font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:1.5px; color:#5A6B83; padding:0 12px 8px; }
        .nav-item { display:flex; align-items:center; gap:10px; padding:10px 12px; border-radius:10px; font-size:13px; font-weight:500; color:#B0BFD0; cursor:pointer; text-decoration:none; transition:all .15s; margin-bottom:2px; }
        .nav-item:hover { background:rgba(255,255,255,0.06); color:#E2E8F0; }
        .nav-item.active { background:rgba(56,189,248,0.14); color:#38BDF8; font-weight:600; }
        .nav-item svg, .nav-item .nav-icon { width:20px; height:20px; opacity:.6; flex-shrink:0; }
        .nav-item.active svg, .nav-item.active .nav-icon { opacity:1; }
        .nav-badge { background:#EF4444; color:#fff; font-size:9px; font-weight:700; padding:1px 7px; border-radius:10px; margin-left:auto; min-width:18px; text-align:center; }

        .sidebar-footer { padding:16px; border-top:1px solid rgba(255,255,255,0.06); }
        .sidebar-user { display:flex; align-items:center; gap:10px; }
        .sidebar-user .av { width:34px; height:34px; border-radius:8px; background:linear-gradient(135deg,#334155,#475569); color:#fff; font-size:12px; font-weight:700; display:flex; align-items:center; justify-content:center; flex-shrink:0; overflow:hidden; }
        .sidebar-user .av img { width:100%; height:100%; object-fit:cover; }
        .sidebar-user .info { flex:1; min-width:0; }
        .sidebar-user .name { font-size:12px; font-weight:600; color:#E2E8F0; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .sidebar-user .role { font-size:10px; color:#64748B; font-weight:500; }

        /* --- MAIN --- */
        .main-area { flex:1; margin-left:260px; display:flex; flex-direction:column; min-height:100vh; }

        /* --- TOPBAR --- */
        .topbar { height:60px; background:#fff; border-bottom:1px solid #E2E8F0; display:flex; align-items:center; justify-content:space-between; padding:0 28px; position:sticky; top:0; z-index:30; box-shadow:0 1px 3px rgba(0,0,0,0.04); }
        .topbar-left { display:flex; align-items:center; gap:16px; }
        .breadcrumb { font-size:13px; color:#94A3B8; font-weight:500; }
        .breadcrumb a { color:#94A3B8; text-decoration:none; }
        .breadcrumb a:hover { color:#64748B; }
        .breadcrumb .current { color:#1E293B; font-weight:600; }
        .topbar-right { display:flex; align-items:center; gap:14px; }
        .topbar-search { background:#F8FAFC; border:1px solid #E2E8F0; border-radius:10px; padding:7px 14px 7px 36px; font-size:12px; color:#64748B; width:200px; outline:none; font-family:inherit; background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%2394A3B8' viewBox='0 0 16 16'%3E%3Cpath d='M11.742 10.344a6.5 6.5 0 10-1.397 1.398h-.001l3.85 3.85a1 1 0 001.415-1.414l-3.85-3.85zm-5.242.156a5 5 0 110-10 5 5 0 010 10z'/%3E%3C/svg%3E"); background-repeat:no-repeat; background-position:12px center; }
        .topbar-search:focus { border-color:#0EA5E9; box-shadow:0 0 0 3px rgba(14,165,233,0.08); }
        .notif-btn { width:36px; height:36px; border-radius:10px; background:#F8FAFC; border:1px solid #E2E8F0; cursor:pointer; display:flex; align-items:center; justify-content:center; position:relative; color:#64748B; font-size:16px; }
        .notif-btn .dot { position:absolute; top:6px; right:6px; width:7px; height:7px; background:#EF4444; border-radius:50%; border:2px solid #fff; }
        .avatar-top { width:34px; height:34px; border-radius:10px; background:linear-gradient(135deg,#0F172A,#334155); color:#fff; font-size:12px; font-weight:700; display:flex; align-items:center; justify-content:center; overflow:hidden; }
        .avatar-top img { width:100%; height:100%; object-fit:cover; }

        /* --- CONTENT --- */
        .content { flex:1; padding:28px; }
        .page-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; }
        .page-title { font-size:24px; font-weight:800; color:#0F172A; letter-spacing:-0.5px; }
        .btn-primary { background:linear-gradient(135deg,#0EA5E9,#0284C7); color:#fff; border:none; padding:10px 20px; border-radius:10px; font-weight:700; font-size:13px; cursor:pointer; box-shadow:0 2px 8px rgba(14,165,233,0.3); font-family:inherit; display:inline-flex; align-items:center; gap:6px; transition:all .15s; }
        .btn-primary:hover { box-shadow:0 4px 14px rgba(14,165,233,0.4); transform:translateY(-1px); }
        .btn-secondary { padding:10px 18px; border-radius:10px; font-weight:600; font-size:13px; border:1px solid #E2E8F0; background:#fff; color:#64748B; cursor:pointer; font-family:inherit; }
        .btn-danger { background:#EF4444; color:#fff; border:none; padding:6px 14px; border-radius:8px; font-weight:600; font-size:12px; cursor:pointer; font-family:inherit; }
        .btn-sm { padding:5px 12px; font-size:12px; border-radius:8px; }

        /* --- KPI CARDS --- */
        .kpi-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:16px; margin-bottom:24px; }
        .kpi-card { background:#fff; border-radius:14px; padding:20px; border:1px solid #E2E8F0; position:relative; overflow:hidden; }
        .kpi-card .accent { position:absolute; left:0; top:0; bottom:0; width:4px; border-radius:4px 0 0 4px; }
        .kpi-card .accent.sky { background:#0EA5E9; }
        .kpi-card .accent.green { background:#22C55E; }
        .kpi-card .accent.amber { background:#F59E0B; }
        .kpi-card .accent.purple { background:#8B5CF6; }
        .kpi-card .accent.cyan { background:#06B6D4; }
        .kpi-card .accent.rose { background:#F43F5E; }
        .kpi-header { display:flex; align-items:center; gap:8px; margin-bottom:10px; }
        .kpi-icon { width:32px; height:32px; border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:15px; flex-shrink:0; }
        .kpi-icon.sky { background:rgba(14,165,233,0.1); color:#0EA5E9; }
        .kpi-icon.green { background:rgba(34,197,94,0.1); color:#22C55E; }
        .kpi-icon.amber { background:rgba(245,158,11,0.1); color:#F59E0B; }
        .kpi-icon.purple { background:rgba(139,92,246,0.1); color:#8B5CF6; }
        .kpi-label { font-size:12px; font-weight:600; color:#64748B; }
        .kpi-value { font-size:28px; font-weight:800; color:#0F172A; letter-spacing:-1px; line-height:1.2; }
        .kpi-change { font-size:11px; font-weight:600; color:#22C55E; margin-top:4px; display:flex; align-items:center; gap:3px; }
        .kpi-change.down { color:#EF4444; }
        .kpi-change.neutral { color:#94A3B8; }

        /* --- TABLE CARD --- */
        .card { background:#fff; border-radius:14px; border:1px solid #E2E8F0; overflow:hidden; margin-bottom:20px; }
        .card-header { padding:16px 20px; border-bottom:1px solid #F1F5F9; display:flex; justify-content:space-between; align-items:center; }
        .card-title { font-size:15px; font-weight:700; color:#0F172A; }
        .card-link { font-size:12px; font-weight:600; color:#0EA5E9; text-decoration:none; }
        .card-link:hover { color:#0284C7; text-decoration:underline; }
        .card-body { padding:16px 20px; }

        table { width:100%; border-collapse:collapse; }
        thead th { text-align:left; font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:0.8px; color:#64748B; padding:12px 16px; background:#F8FAFC; border-bottom:1px solid #E2E8F0; }
        tbody td { padding:14px 16px; font-size:13px; color:#334155; border-bottom:1px solid #F1F5F9; font-weight:500; }
        tbody tr:hover td { background:#F8FAFC; }
        tbody tr:last-child td { border-bottom:none; }

        .user-cell { display:flex; align-items:center; gap:10px; }
        .user-avatar { width:32px; height:32px; border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:11px; font-weight:700; color:#fff; flex-shrink:0; }
        .user-name { font-weight:600; color:#0F172A; font-size:13px; }
        .user-sub { font-size:11px; color:#94A3B8; margin-top:1px; }

        .badge { display:inline-block; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:600; }
        .badge-sub { background:#DBEAFE; color:#1D4ED8; }
        .badge-adv { background:#FEF3C7; color:#92400E; }
        .badge-admin { background:#0F172A; color:#F8FAFC; }
        .badge-active { background:#DCFCE7; color:#15803D; }
        .badge-pending { background:#FEF3C7; color:#92400E; }
        .badge-danger { background:#FEE2E2; color:#B91C1C; }
        .badge-info { background:#DBEAFE; color:#1D4ED8; }
        .badge-gray { background:#F1F5F9; color:#475569; }

        .trust-bar { display:flex; align-items:center; gap:6px; }
        .trust-track { width:60px; height:5px; background:#E2E8F0; border-radius:3px; overflow:hidden; }
        .trust-fill { height:100%; border-radius:3px; }
        .trust-fill.high { background:#22C55E; }
        .trust-fill.med { background:#F59E0B; }
        .trust-fill.low { background:#EF4444; }

        .action-btn { padding:5px 12px; border-radius:7px; font-size:11px; font-weight:600; border:1px solid #E2E8F0; background:#fff; color:#64748B; cursor:pointer; font-family:inherit; transition:all .1s; }
        .action-btn:hover { background:#F8FAFC; color:#0F172A; }
        .action-btn.primary { border-color:#0EA5E9; color:#0EA5E9; }
        .action-btn.primary:hover { background:#EFF6FF; }
        .action-btn.success { border-color:#22C55E; color:#22C55E; }
        .action-btn.danger { border-color:#EF4444; color:#EF4444; }

        /* --- CHART --- */
        .chart-container { padding:20px; min-height:250px; }

        /* --- PAGINATION --- */
        .pagination { display:flex; align-items:center; justify-content:space-between; padding:12px 16px; border-top:1px solid #F1F5F9; font-size:12px; color:#94A3B8; }

        /* --- EMPTY STATE --- */
        .empty-state { text-align:center; padding:48px 20px; color:#94A3B8; }
        .empty-state .icon { font-size:48px; margin-bottom:12px; opacity:0.3; }
        .empty-state p { font-size:13px; font-weight:500; }

        /* --- GRID LAYOUTS --- */
        .grid-2 { display:grid; grid-template-columns:2fr 1fr; gap:20px; }

        /* --- ACTIVITY FEED --- */
        .activity-item { display:flex; gap:12px; padding:12px 0; border-bottom:1px solid #F1F5F9; }
        .activity-item:last-child { border-bottom:none; }
        .activity-dot { width:8px; height:8px; border-radius:50%; margin-top:5px; flex-shrink:0; }
        .activity-dot.blue { background:#0EA5E9; }
        .activity-dot.green { background:#22C55E; }
        .activity-dot.amber { background:#F59E0B; }
        .activity-dot.red { background:#EF4444; }
        .activity-text { font-size:12px; color:#475569; font-weight:500; line-height:1.5; }
        .activity-text strong { color:#0F172A; font-weight:600; }
        .activity-time { font-size:10px; color:#CBD5E1; font-weight:500; margin-top:2px; }

        /* --- MOBILE --- */
        .mobile-toggle { display:none; background:none; border:none; color:#64748B; font-size:20px; cursor:pointer; }
        .sidebar-overlay { display:none; }
        @@media (max-width:1024px) {
            .sidebar { transform:translateX(-100%); }
            .sidebar.open { transform:translateX(0); }
            .sidebar-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.4); z-index:35; }
            .sidebar-overlay.open { display:block; }
            .main-area { margin-left:0; }
            .mobile-toggle { display:block; }
            .kpi-grid { grid-template-columns:repeat(2,1fr); }
            .grid-2 { grid-template-columns:1fr; }
        }
        @@media (max-width:640px) {
            .kpi-grid { grid-template-columns:1fr; }
            .content { padding:16px; }
        }
    </style>
    @stack('styles')
    @livewireStyles
</head>
<body>
    <div class="panel-wrap">
        {{-- Sidebar overlay for mobile --}}
        <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

        {{-- SIDEBAR --}}
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-brand">
                <div class="logo-icon">O</div>
                <div class="logo-text"><span>oon</span>.click</div>
            </div>

            <nav class="sidebar-nav">
                @yield('sidebar-nav')
            </nav>

            <div class="sidebar-footer">
                <div class="sidebar-user">
                    <div class="av">
                        @if(auth()->user()->avatar_path)
                            <img src="{{ asset('storage/' . auth()->user()->avatar_path) }}" alt="Avatar">
                        @else
                            {{ strtoupper(substr(auth()->user()->name ?? auth()->user()->phone ?? 'A', 0, 1)) }}{{ strtoupper(substr(explode(' ', auth()->user()->name ?? 'U')[1] ?? '', 0, 1)) }}
                        @endif
                    </div>
                    <div class="info">
                        <div class="name">{{ auth()->user()->name ?? auth()->user()->phone }}</div>
                        <div class="role">{{ ucfirst(auth()->user()->role) }}</div>
                    </div>
                </div>
            </div>
        </aside>

        {{-- MAIN --}}
        <div class="main-area">
            {{-- TOPBAR --}}
            <header class="topbar">
                <div class="topbar-left">
                    <button class="mobile-toggle" onclick="toggleSidebar()">&#9776;</button>
                    <div class="breadcrumb">
                        @yield('breadcrumb', '<span class="current">Tableau de bord</span>')
                    </div>
                </div>
                <div class="topbar-right">
                    <input class="topbar-search" placeholder="Rechercher..." />
                    <button class="notif-btn">
                        &#128276;
                        <div class="dot"></div>
                    </button>
                    <div class="avatar-top">
                        @if(auth()->user()->avatar_path)
                            <img src="{{ asset('storage/' . auth()->user()->avatar_path) }}" alt="Avatar">
                        @else
                            {{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}{{ strtoupper(substr(explode(' ', auth()->user()->name ?? 'U')[1] ?? '', 0, 1)) }}
                        @endif
                    </div>
                </div>
            </header>

            {{-- CONTENT --}}
            <main class="content">
                @yield('content')
            </main>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('open');
            document.getElementById('sidebarOverlay').classList.toggle('open');
        }
    </script>
    @livewireScripts
    @stack('scripts')
</body>
</html>
