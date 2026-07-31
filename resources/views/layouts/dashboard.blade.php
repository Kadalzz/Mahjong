<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard') | Hóng Zhōng Mahjong Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@600;700;800&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --red: #551414;
            --red-light: #7a2020;
            --cream: #f4f1dd;
            --ink: #3a1414;
            --ink-mute: #6b5d4a;
            --sidebar-bg: #0a2410;
            --sidebar-width: 260px;
            --topbar-h: 64px;
            --dark: #0e300f;
            --card-bg: #f4f1dd;
            --border: rgba(244,241,221,0.14);
            /* legacy aliases */
            --gold: var(--red);
            --gold-light: var(--red-light);
        }
        * { font-family: 'Nunito', sans-serif; }
        h1, h2, h3, h4, h5, h6 { font-family: 'Baloo 2', sans-serif; }
        body { background: var(--dark); color: var(--cream); }

        /* Sidebar */
        .sidebar {
            position: fixed;
            left: 0; top: 0; bottom: 0;
            width: var(--sidebar-width);
            background: var(--sidebar-bg);
            border-right: 1px solid var(--border);
            z-index: 1000;
            overflow-y: auto;
            transition: transform 0.3s;
        }
        .sidebar-brand {
            padding: 1.25rem 1.5rem 1rem;
            font-family: 'Baloo 2', sans-serif;
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--cream);
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 0.6rem;
            text-decoration: none;
        }
        .sidebar-brand:hover { color: var(--cream); opacity: 0.85; }
        .sidebar-nav { padding: 1rem 0; }
        .sidebar-label {
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: rgba(244,241,221,0.35);
            padding: 0.8rem 1.5rem 0.4rem;
        }
        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.65rem 1.5rem;
            color: rgba(244,241,221,0.65);
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 700;
            border-left: 3px solid transparent;
            transition: all 0.2s;
        }
        .sidebar-link:hover {
            color: var(--cream);
            background: rgba(85,20,20,0.25);
            border-left-color: rgba(244,241,221,0.3);
        }
        .sidebar-link.active {
            color: var(--cream);
            background: var(--red);
            border-left-color: var(--cream);
        }
        .sidebar-link i { font-size: 1.1rem; width: 20px; text-align: center; }

        /* Main area */
        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
        }
        .topbar {
            height: var(--topbar-h);
            background: var(--sidebar-bg);
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            padding: 0 2rem;
            position: sticky;
            top: 0;
            z-index: 100;
            color: var(--cream);
        }
        .page-content { padding: 2rem; }

        /* Cards */
        .stat-card {
            background: var(--card-bg);
            border: 1px solid rgba(58,20,20,0.08);
            border-radius: 16px;
            padding: 1.5rem;
            position: relative;
            overflow: hidden;
            transition: transform 0.2s, border-color 0.2s;
            color: var(--ink);
        }
        .stat-card:hover {
            transform: translateY(-2px);
            border-color: rgba(85,20,20,0.3);
        }
        .stat-icon {
            width: 48px; height: 48px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.4rem;
        }
        .stat-value { font-family: 'Baloo 2', sans-serif; font-size: 1.75rem; font-weight: 700; color: var(--ink); }
        .stat-label { font-size: 0.8rem; color: var(--ink-mute); font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
        .stat-change { font-size: 0.8rem; font-weight: 700; }

        .card-dark {
            background: var(--card-bg);
            border: 1px solid rgba(58,20,20,0.08);
            border-radius: 16px;
            color: var(--ink);
        }
        .card-dark .card-header {
            background: transparent;
            border-bottom: 1px solid rgba(58,20,20,0.1);
            padding: 1.25rem 1.5rem;
            font-weight: 700;
            color: var(--ink);
        }
        .card-dark .card-body { padding: 1.5rem; }

        /* Table */
        .table-dark-custom {
            color: var(--ink);
            --bs-table-bg: transparent;
        }
        .table-dark-custom thead th {
            background: rgba(58,20,20,0.04);
            border-color: rgba(58,20,20,0.1);
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--ink-mute);
            font-weight: 700;
            padding: 0.875rem 1rem;
        }
        .table-dark-custom tbody td {
            border-color: rgba(58,20,20,0.1);
            padding: 0.875rem 1rem;
            vertical-align: middle;
        }
        .table-dark-custom tbody tr:hover { background: rgba(58,20,20,0.03); }

        .text-gold { color: var(--red) !important; }
        .btn-gold {
            background: var(--red);
            color: var(--cream);
            font-weight: 700;
            border: none;
            border-radius: 999px;
            font-family: 'Baloo 2', sans-serif;
            transition: all 0.3s;
        }
        .btn-gold:hover {
            background: var(--red-light);
            transform: translateY(-1px);
            box-shadow: 0 4px 20px rgba(85,20,20,0.4);
            color: var(--cream);
        }

        .badge-s-active    { background: #DCEFDD; color: #1f7a34; border: 1px solid #2f9e4440; padding: 0.35em 0.75em; border-radius: 20px; font-size: 0.75rem; font-weight: 700; }
        .badge-s-waiting   { background: #FBF0C8; color: #8a6d1a; border: 1px solid #d4a01740; padding: 0.35em 0.75em; border-radius: 20px; font-size: 0.75rem; font-weight: 700; }
        .badge-s-pending   { background: #DCE8F7; color: #2e6fba; border: 1px solid #2e6fba40; padding: 0.35em 0.75em; border-radius: 20px; font-size: 0.75rem; font-weight: 700; }
        .badge-s-done      { background: rgba(58,20,20,0.08); color: var(--ink-mute); border: 1px solid rgba(58,20,20,0.12); padding: 0.35em 0.75em; border-radius: 20px; font-size: 0.75rem; font-weight: 700; }
        .badge-s-cancelled { background: #F6DEDE; color: var(--red); border: 1px solid rgba(85,20,20,0.3); padding: 0.35em 0.75em; border-radius: 20px; font-size: 0.75rem; font-weight: 700; }

        @media (max-width: 991px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.show { transform: translateX(0); }
            .main-content { margin-left: 0; }
        }
    </style>
    @yield('head')
</head>
<body>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
    <a class="sidebar-brand" href="{{ route('dashboard.index') }}">
        <x-brand-logo :size="28" />
        <span>HÓNG ZHŌNG
            <div style="font-size:0.7rem;font-weight:400;color:rgba(244,241,221,0.5);margin-top:2px">Admin Dashboard</div>
        </span>
    </a>

    <nav class="sidebar-nav">
        <div class="sidebar-label">Utama</div>
        <a href="{{ route('dashboard.index') }}" class="sidebar-link {{ request()->routeIs('dashboard.index') ? 'active' : '' }}">
            <i class="bi bi-speedometer2"></i> Overview
        </a>
        <a href="{{ route('dashboard.revenue') }}" class="sidebar-link {{ request()->routeIs('dashboard.revenue') ? 'active' : '' }}">
            <i class="bi bi-graph-up-arrow"></i> Revenue
        </a>
        <a href="{{ route('dashboard.occupancy') }}" class="sidebar-link {{ request()->routeIs('dashboard.occupancy') ? 'active' : '' }}">
            <i class="bi bi-bar-chart-fill"></i> Occupancy
        </a>

        <div class="sidebar-label">Manajemen</div>
        <a href="{{ route('dashboard.bookings') }}" class="sidebar-link {{ request()->routeIs('dashboard.bookings') ? 'active' : '' }}">
            <i class="bi bi-journal-check"></i> Booking
        </a>
        <a href="{{ route('dashboard.tables.index') }}" class="sidebar-link {{ request()->routeIs('dashboard.tables.*') ? 'active' : '' }}">
            <i class="bi bi-grid-3x3-gap-fill"></i> Meja
        </a>
        <a href="{{ route('dashboard.pricing') }}" class="sidebar-link {{ request()->routeIs('dashboard.pricing') ? 'active' : '' }}">
            <i class="bi bi-tag-fill"></i> Harga
        </a>

        <div class="sidebar-label">Lainnya</div>
        <a href="{{ route('booking.index') }}" class="sidebar-link" target="_blank">
            <i class="bi bi-box-arrow-up-right"></i> User View
        </a>
        <a href="{{ route('schedule.index') }}" class="sidebar-link" target="_blank">
            <i class="bi bi-calendar3"></i> Jadwal Publik
        </a>
    </nav>

    <div style="padding:1.5rem;border-top:1px solid var(--border);margin-top:auto">
        <div style="font-size:0.8rem;color:rgba(244,241,221,0.5)">Login sebagai</div>
        <div style="font-weight:700;color:var(--cream)">{{ auth()->user()->name ?? 'Admin' }}</div>
        <form method="POST" action="{{ route('logout') }}" class="mt-2">
            @csrf
            <button type="submit" class="btn btn-sm btn-outline-light w-100" style="border-radius:999px;">
                <i class="bi bi-box-arrow-right me-1"></i>Logout
            </button>
        </form>
    </div>
</aside>

<!-- Main content -->
<div class="main-content">
    <div class="topbar">
        <button class="btn btn-sm me-3 d-lg-none" onclick="document.getElementById('sidebar').classList.toggle('show')" style="color:var(--cream);background:transparent;border:1px solid var(--border)">
            <i class="bi bi-list fs-5"></i>
        </button>
        <div>
            <h6 class="mb-0 fw-700" style="color:var(--cream)">@yield('page-title', 'Dashboard')</h6>
            <small style="color:rgba(244,241,221,0.5)">{{ now()->format('l, d F Y') }}</small>
        </div>
        <div class="ms-auto d-flex align-items-center gap-2">
            <span style="font-size:0.8rem;color:rgba(244,241,221,0.4)">{{ now()->format('H:i') }} WIB</span>
        </div>
    </div>

    <div class="page-content">
        @if(session('success'))
            <div class="alert border-0 mb-3" style="background:#DCEFDD;color:#1f7a34;border-radius:10px;">
                <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="alert border-0 mb-3" style="background:#F6DEDE;color:#551414;border-radius:10px;">
                <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
            </div>
        @endif

        @yield('content')
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
@yield('scripts')
</body>
</html>
