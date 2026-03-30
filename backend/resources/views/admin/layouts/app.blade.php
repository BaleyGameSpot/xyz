<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin Panel') — Chinar Signals</title>

    <!-- Bootstrap 5 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --bg-dark:     #0D1117;
            --bg-card:     #161B22;
            --bg-elevated: #21262D;
            --text-primary:   #E6EDF3;
            --text-secondary: #8B949E;
            --accent-green: #00C853;
            --accent-red:   #FF1744;
            --accent-gold:  #FFD700;
            --accent-blue:  #2979FF;
            --border:       #30363D;
            --sidebar-w:    260px;
        }

        * { box-sizing: border-box; }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-dark);
            color: var(--text-primary);
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* ─── Sidebar ─────────────────────────────────────── */
        .sidebar {
            position: fixed;
            top: 0; left: 0;
            width: var(--sidebar-w);
            height: 100vh;
            background-color: var(--bg-card);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            z-index: 1000;
            transition: transform 0.3s ease;
        }

        .sidebar-brand {
            padding: 1.5rem 1.25rem;
            border-bottom: 1px solid var(--border);
            text-decoration: none;
        }

        .sidebar-brand .brand-logo {
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }

        .brand-icon {
            width: 36px; height: 36px;
            background: linear-gradient(135deg, var(--accent-blue), #6C47FF);
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.1rem;
        }

        .brand-name {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--text-primary);
            line-height: 1.1;
        }

        .brand-sub {
            font-size: 0.65rem;
            color: var(--text-secondary);
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .sidebar-nav {
            flex: 1;
            padding: 1rem 0.75rem;
            overflow-y: auto;
        }

        .nav-section-label {
            font-size: 0.65rem;
            font-weight: 600;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: var(--text-secondary);
            padding: 0.75rem 0.5rem 0.4rem;
        }

        .sidebar-nav .nav-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.6rem 0.85rem;
            border-radius: 8px;
            color: var(--text-secondary);
            font-size: 0.875rem;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.15s ease;
            margin-bottom: 2px;
        }

        .sidebar-nav .nav-link i {
            font-size: 1.05rem;
            width: 20px;
            text-align: center;
            flex-shrink: 0;
        }

        .sidebar-nav .nav-link:hover {
            background-color: var(--bg-elevated);
            color: var(--text-primary);
        }

        .sidebar-nav .nav-link.active {
            background-color: rgba(41, 121, 255, 0.15);
            color: var(--accent-blue);
            font-weight: 600;
        }

        .sidebar-nav .nav-link .badge {
            margin-left: auto;
            font-size: 0.65rem;
        }

        .sidebar-footer {
            padding: 0.75rem;
            border-top: 1px solid var(--border);
        }

        .sidebar-footer .admin-info {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            padding: 0.5rem 0.75rem;
            margin-bottom: 0.4rem;
        }

        .admin-avatar {
            width: 32px; height: 32px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--accent-blue), #6C47FF);
            display: flex; align-items: center; justify-content: center;
            font-size: 0.8rem;
            font-weight: 700;
            color: #fff;
            flex-shrink: 0;
        }

        .admin-name  { font-size: 0.8rem; font-weight: 600; color: var(--text-primary); }
        .admin-role  { font-size: 0.7rem; color: var(--text-secondary); }

        .btn-logout {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            width: 100%;
            padding: 0.55rem 0.85rem;
            border-radius: 8px;
            background: transparent;
            border: 1px solid var(--border);
            color: var(--text-secondary);
            font-size: 0.8rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.15s ease;
            text-decoration: none;
        }

        .btn-logout:hover {
            background: rgba(255, 23, 68, 0.1);
            border-color: var(--accent-red);
            color: var(--accent-red);
        }

        /* ─── Main content ─────────────────────────────────── */
        .main-wrapper {
            margin-left: var(--sidebar-w);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* ─── Topbar ───────────────────────────────────────── */
        .topbar {
            position: sticky;
            top: 0;
            background-color: var(--bg-card);
            border-bottom: 1px solid var(--border);
            padding: 0 1.5rem;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            z-index: 900;
        }

        .topbar-title {
            font-size: 1rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .topbar-btn {
            width: 36px; height: 36px;
            border-radius: 8px;
            background: var(--bg-elevated);
            border: 1px solid var(--border);
            color: var(--text-secondary);
            display: flex; align-items: center; justify-content: center;
            cursor: pointer;
            transition: all 0.15s ease;
            position: relative;
            text-decoration: none;
        }

        .topbar-btn:hover { color: var(--text-primary); border-color: var(--accent-blue); }

        .notification-dot {
            position: absolute;
            top: 6px; right: 6px;
            width: 7px; height: 7px;
            background: var(--accent-red);
            border-radius: 50%;
            border: 1.5px solid var(--bg-card);
        }

        /* ─── Page content ─────────────────────────────────── */
        .page-content {
            flex: 1;
            padding: 1.5rem;
        }

        /* ─── Cards ────────────────────────────────────────── */
        .card {
            background-color: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 12px;
        }

        .card-header {
            background-color: transparent;
            border-bottom: 1px solid var(--border);
            padding: 1rem 1.25rem;
            font-weight: 600;
        }

        /* ─── Stat cards ───────────────────────────────────── */
        .stat-card {
            background-color: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 1.25rem;
            position: relative;
            overflow: hidden;
            transition: transform 0.15s ease, border-color 0.15s ease;
        }

        .stat-card:hover { transform: translateY(-2px); }

        .stat-card.blue   { border-top: 3px solid var(--accent-blue); }
        .stat-card.green  { border-top: 3px solid var(--accent-green); }
        .stat-card.purple { border-top: 3px solid #9C27B0; }
        .stat-card.gold   { border-top: 3px solid var(--accent-gold); }

        .stat-icon {
            width: 44px; height: 44px;
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.2rem;
            margin-bottom: 1rem;
        }

        .stat-icon.blue   { background: rgba(41, 121, 255, 0.15); color: var(--accent-blue); }
        .stat-icon.green  { background: rgba(0, 200, 83, 0.15);   color: var(--accent-green); }
        .stat-icon.purple { background: rgba(156, 39, 176, 0.15); color: #9C27B0; }
        .stat-icon.gold   { background: rgba(255, 215, 0, 0.15);  color: var(--accent-gold); }

        .stat-value {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--text-primary);
            line-height: 1;
            margin-bottom: 0.25rem;
        }

        .stat-label {
            font-size: 0.8rem;
            color: var(--text-secondary);
            font-weight: 500;
        }

        .stat-change {
            font-size: 0.75rem;
            font-weight: 600;
            margin-top: 0.5rem;
        }

        .stat-change.up   { color: var(--accent-green); }
        .stat-change.down { color: var(--accent-red); }

        /* ─── Tables ───────────────────────────────────────── */
        .table {
            color: var(--text-primary);
            margin-bottom: 0;
        }

        .table thead th {
            background-color: var(--bg-elevated);
            border-color: var(--border);
            color: var(--text-secondary);
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            padding: 0.75rem 1rem;
            white-space: nowrap;
        }

        .table tbody td {
            border-color: var(--border);
            padding: 0.75rem 1rem;
            vertical-align: middle;
            font-size: 0.875rem;
        }

        .table tbody tr:hover { background-color: rgba(255,255,255,0.03); }

        /* ─── Badges ───────────────────────────────────────── */
        .badge-buy    { background: rgba(0,200,83,0.15);    color: var(--accent-green); border: 1px solid rgba(0,200,83,0.3); }
        .badge-sell   { background: rgba(255,23,68,0.15);   color: var(--accent-red);   border: 1px solid rgba(255,23,68,0.3); }
        .badge-win    { background: rgba(0,200,83,0.15);    color: var(--accent-green); border: 1px solid rgba(0,200,83,0.3); }
        .badge-loss   { background: rgba(255,23,68,0.15);   color: var(--accent-red);   border: 1px solid rgba(255,23,68,0.3); }
        .badge-pending { background: rgba(255,215,0,0.15); color: var(--accent-gold);  border: 1px solid rgba(255,215,0,0.3); }
        .badge-active { background: rgba(0,200,83,0.15);    color: var(--accent-green); border: 1px solid rgba(0,200,83,0.3); }
        .badge-blocked { background: rgba(255,23,68,0.15);  color: var(--accent-red);   border: 1px solid rgba(255,23,68,0.3); }

        .badge-custom {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.25rem 0.6rem;
            border-radius: 6px;
            font-size: 0.7rem;
            font-weight: 600;
            letter-spacing: 0.03em;
        }

        /* ─── Forms ────────────────────────────────────────── */
        .form-control, .form-select {
            background-color: var(--bg-elevated);
            border-color: var(--border);
            color: var(--text-primary);
        }

        .form-control:focus, .form-select:focus {
            background-color: var(--bg-elevated);
            border-color: var(--accent-blue);
            color: var(--text-primary);
            box-shadow: 0 0 0 3px rgba(41, 121, 255, 0.15);
        }

        .form-control::placeholder { color: var(--text-secondary); }

        .form-label {
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.4rem;
        }

        .input-group-text {
            background-color: var(--bg-elevated);
            border-color: var(--border);
            color: var(--text-secondary);
        }

        /* ─── Buttons ──────────────────────────────────────── */
        .btn-primary   { background-color: var(--accent-blue);  border-color: var(--accent-blue); }
        .btn-success   { background-color: var(--accent-green); border-color: var(--accent-green); color: #000; }
        .btn-danger    { background-color: var(--accent-red);   border-color: var(--accent-red); }
        .btn-warning   { background-color: var(--accent-gold);  border-color: var(--accent-gold); color: #000; }
        .btn-outline-secondary { border-color: var(--border); color: var(--text-secondary); }
        .btn-outline-secondary:hover { background-color: var(--bg-elevated); color: var(--text-primary); }

        /* ─── Alerts ───────────────────────────────────────── */
        .alert-success { background: rgba(0,200,83,0.1);   border-color: rgba(0,200,83,0.3);   color: var(--accent-green); }
        .alert-danger  { background: rgba(255,23,68,0.1);  border-color: rgba(255,23,68,0.3);  color: var(--accent-red); }
        .alert-warning { background: rgba(255,215,0,0.1);  border-color: rgba(255,215,0,0.3);  color: var(--accent-gold); }
        .alert-info    { background: rgba(41,121,255,0.1); border-color: rgba(41,121,255,0.3); color: var(--accent-blue); }

        /* ─── Pagination ───────────────────────────────────── */
        .pagination .page-link {
            background-color: var(--bg-elevated);
            border-color: var(--border);
            color: var(--text-secondary);
        }

        .pagination .page-link:hover { background-color: var(--bg-card); color: var(--text-primary); }
        .pagination .page-item.active .page-link { background-color: var(--accent-blue); border-color: var(--accent-blue); }

        /* ─── Scrollbar ────────────────────────────────────── */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: var(--bg-dark); }
        ::-webkit-scrollbar-thumb { background: var(--border); border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--text-secondary); }

        /* ─── Mobile ───────────────────────────────────────── */
        @media (max-width: 992px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.show { transform: translateX(0); }
            .main-wrapper { margin-left: 0; }
            .sidebar-overlay {
                display: none;
                position: fixed; inset: 0;
                background: rgba(0,0,0,0.5);
                z-index: 999;
            }
            .sidebar-overlay.show { display: block; }
        }

        /* ─── Chart containers ─────────────────────────────── */
        .chart-container { position: relative; }

        /* ─── Misc ─────────────────────────────────────────── */
        .text-muted  { color: var(--text-secondary) !important; }
        .border-color { border-color: var(--border) !important; }
        .bg-card     { background-color: var(--bg-card); }
        .bg-elevated { background-color: var(--bg-elevated); }
        hr { border-color: var(--border); }
    </style>

    @stack('styles')
</head>
<body>

<!-- Sidebar overlay (mobile) -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<!-- ─── Sidebar ──────────────────────────────────────────── -->
<nav class="sidebar" id="sidebar">
    <!-- Brand -->
    <a href="{{ route('admin.dashboard') }}" class="sidebar-brand text-decoration-none">
        <div class="brand-logo">
            <div class="brand-icon">📈</div>
            <div>
                <div class="brand-name">Chinar Signals</div>
                <div class="brand-sub">Admin Panel</div>
            </div>
        </div>
    </a>

    <!-- Navigation -->
    <div class="sidebar-nav">
        <div class="nav-section-label">Main</div>

        <a href="{{ route('admin.dashboard') }}"
           class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
            <i class="bi bi-speedometer2"></i>
            Dashboard
        </a>

        <a href="{{ route('admin.users.index') }}"
           class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
            <i class="bi bi-people"></i>
            Users
            @if(isset($pendingUsersCount) && $pendingUsersCount > 0)
                <span class="badge bg-danger rounded-pill">{{ $pendingUsersCount }}</span>
            @endif
        </a>

        <a href="{{ route('admin.signals.index') }}"
           class="nav-link {{ request()->routeIs('admin.signals.*') ? 'active' : '' }}">
            <i class="bi bi-graph-up-arrow"></i>
            Signals
        </a>

        <div class="nav-section-label">Commerce</div>

        <a href="{{ route('admin.packages.index') }}"
           class="nav-link {{ request()->routeIs('admin.packages.*') ? 'active' : '' }}">
            <i class="bi bi-box-seam"></i>
            Packages
        </a>

        <a href="{{ route('admin.payments.index') }}"
           class="nav-link {{ request()->routeIs('admin.payments.*') ? 'active' : '' }}">
            <i class="bi bi-credit-card"></i>
            Payments
            @if(isset($pendingPaymentsCount) && $pendingPaymentsCount > 0)
                <span class="badge bg-warning text-dark rounded-pill">{{ $pendingPaymentsCount }}</span>
            @endif
        </a>

        <div class="nav-section-label">System</div>

        <a href="{{ route('admin.settings') }}"
           class="nav-link {{ request()->routeIs('admin.settings') ? 'active' : '' }}">
            <i class="bi bi-gear"></i>
            Settings
        </a>
    </div>

    <!-- Footer -->
    <div class="sidebar-footer">
        <div class="admin-info">
            <div class="admin-avatar">
                {{ strtoupper(substr(auth('admin')->user()->name ?? 'A', 0, 1)) }}
            </div>
            <div>
                <div class="admin-name">{{ auth('admin')->user()->name ?? 'Admin' }}</div>
                <div class="admin-role">Super Admin</div>
            </div>
        </div>
        <form action="{{ route('admin.logout') }}" method="POST">
            @csrf
            <button type="submit" class="btn-logout">
                <i class="bi bi-box-arrow-left"></i> Sign Out
            </button>
        </form>
    </div>
</nav>

<!-- ─── Main wrapper ──────────────────────────────────────── -->
<div class="main-wrapper">
    <!-- Topbar -->
    <header class="topbar">
        <div class="d-flex align-items-center gap-3">
            <!-- Mobile hamburger -->
            <button class="topbar-btn d-lg-none" onclick="toggleSidebar()">
                <i class="bi bi-list fs-5"></i>
            </button>
            <h1 class="topbar-title mb-0">@yield('page-title', 'Dashboard')</h1>

            <!-- Breadcrumb (optional) -->
            @hasSection('breadcrumb')
            <nav aria-label="breadcrumb" class="d-none d-md-block">
                <ol class="breadcrumb mb-0 small">
                    @yield('breadcrumb')
                </ol>
            </nav>
            @endif
        </div>

        <div class="topbar-right">
            <!-- Notifications -->
            <a href="#" class="topbar-btn" title="Notifications">
                <i class="bi bi-bell"></i>
                <span class="notification-dot"></span>
            </a>
            <!-- Full screen -->
            <button class="topbar-btn d-none d-md-flex" onclick="toggleFullscreen()" title="Fullscreen">
                <i class="bi bi-fullscreen" id="fsIcon"></i>
            </button>
            <!-- Admin badge -->
            <div class="d-none d-md-flex align-items-center gap-2 ps-2">
                <div class="admin-avatar" style="width:32px;height:32px;font-size:0.75rem;">
                    {{ strtoupper(substr(auth('admin')->user()->name ?? 'A', 0, 1)) }}
                </div>
                <span class="small fw-600" style="color:var(--text-secondary);">
                    {{ auth('admin')->user()->name ?? 'Admin' }}
                </span>
            </div>
        </div>
    </header>

    <!-- Flash messages -->
    <div class="px-3 pt-3">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2" role="alert">
                <i class="bi bi-check-circle-fill"></i>
                {{ session('success') }}
                <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2" role="alert">
                <i class="bi bi-exclamation-circle-fill"></i>
                {{ session('error') }}
                <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('warning'))
            <div class="alert alert-warning alert-dismissible fade show d-flex align-items-center gap-2" role="alert">
                <i class="bi bi-exclamation-triangle-fill"></i>
                {{ session('warning') }}
                <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="alert"></button>
            </div>
        @endif
    </div>

    <!-- Page content -->
    <main class="page-content">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="text-center py-3" style="border-top:1px solid var(--border);color:var(--text-secondary);font-size:0.75rem;">
        © {{ date('Y') }} Chinar Signals Admin Panel &nbsp;·&nbsp; v1.0.0
    </footer>
</div>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>

<script>
    // Sidebar toggle (mobile)
    function toggleSidebar() {
        document.getElementById('sidebar').classList.toggle('show');
        document.getElementById('sidebarOverlay').classList.toggle('show');
    }
    function closeSidebar() {
        document.getElementById('sidebar').classList.remove('show');
        document.getElementById('sidebarOverlay').classList.remove('show');
    }

    // Fullscreen toggle
    function toggleFullscreen() {
        const icon = document.getElementById('fsIcon');
        if (!document.fullscreenElement) {
            document.documentElement.requestFullscreen();
            icon.className = 'bi bi-fullscreen-exit';
        } else {
            document.exitFullscreen();
            icon.className = 'bi bi-fullscreen';
        }
    }

    // Auto-dismiss flash messages after 5 s
    setTimeout(() => {
        document.querySelectorAll('.alert.fade.show').forEach(el => {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(el);
            bsAlert.close();
        });
    }, 5000);

    // CSRF token for AJAX
    const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').content;
</script>

@stack('scripts')
</body>
</html>
