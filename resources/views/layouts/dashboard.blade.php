<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard') | Mahjong Club Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --gold: #c9a84c;
            --gold-light: #e8c97a;
            --sidebar-bg: #0d0d1f;
            --sidebar-width: 260px;
            --topbar-h: 64px;
            --dark: #111122;
            --card-bg: #1a1a2e;
            --border: #2a2a45;
        }
        * { font-family: 'Inter', sans-serif; }
        body { background: var(--dark); color: #e0e0e0; }

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
            padding: 1.5rem 1.5rem 1rem;
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--gold);
            border-bottom: 1px solid var(--border);
            display: block;
            text-decoration: none;
        }
        .sidebar-brand:hover { color: var(--gold-light); }
        .sidebar-nav { padding: 1rem 0; }
        .sidebar-label {
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: #444466;
            padding: 0.8rem 1.5rem 0.4rem;
        }
        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.65rem 1.5rem;
            color: #8888aa;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            border-left: 3px solid transparent;
            transition: all 0.2s;
        }
        .sidebar-link:hover {
            color: #e0e0e0;
            background: rgba(201,168,76,0.06);
            border-left-color: rgba(201,168,76,0.3);
        }
        .sidebar-link.active {
            color: var(--gold);
            background: rgba(201,168,76,0.1);
            border-left-color: var(--gold);
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
        }
        .page-content { padding: 2rem; }

        /* Cards */
        .stat-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 1.5rem;
            position: relative;
            overflow: hidden;
            transition: transform 0.2s, border-color 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-2px);
            border-color: rgba(201,168,76,0.3);
        }
        .stat-icon {
            width: 48px; height: 48px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.4rem;
        }
        .stat-value { font-size: 1.75rem; font-weight: 700; color: #fff; }
        .stat-label { font-size: 0.8rem; color: #888; font-weight: 500; text-transform: uppercase; letter-spacing: 0.5px; }
        .stat-change { font-size: 0.8rem; font-weight: 500; }

        .card-dark {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 16px;
            color: #e0e0e0;
        }
        .card-dark .card-header {
            background: transparent;
            border-bottom: 1px solid var(--border);
            padding: 1.25rem 1.5rem;
            font-weight: 600;
            color: #e0e0e0;
        }
        .card-dark .card-body { padding: 1.5rem; }

        /* Table */
        .table-dark-custom {
            color: #e0e0e0;
            --bs-table-bg: transparent;
        }
        .table-dark-custom thead th {
            background: rgba(255,255,255,0.03);
            border-color: var(--border);
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #888;
            font-weight: 600;
            padding: 0.875rem 1rem;
        }
        .table-dark-custom tbody td {
            border-color: var(--border);
            padding: 0.875rem 1rem;
            vertical-align: middle;
        }
        .table-dark-custom tbody tr:hover { background: rgba(255,255,255,0.02); }

        .text-gold { color: var(--gold) !important; }
        .btn-gold {
            background: linear-gradient(135deg, var(--gold), var(--gold-light));
            color: #1a1200;
            font-weight: 600;
            border: none;
            border-radius: 8px;
            transition: all 0.3s;
        }
        .btn-gold:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 20px rgba(201,168,76,0.4);
            color: #1a1200;
        }

        .badge-s-active    { background: #16a08520; color: #1abc9c; border: 1px solid #16a08540; padding: 0.35em 0.75em; border-radius: 20px; font-size: 0.75rem; }
        .badge-s-waiting   { background: #2980b920; color: #3498db; border: 1px solid #2980b940; padding: 0.35em 0.75em; border-radius: 20px; font-size: 0.75rem; }
        .badge-s-pending   { background: #f39c1220; color: #f1c40f; border: 1px solid #f39c1240; padding: 0.35em 0.75em; border-radius: 20px; font-size: 0.75rem; }
        .badge-s-done      { background: #ffffff10; color: #aaa;     border: 1px solid #ffffff20; padding: 0.35em 0.75em; border-radius: 20px; font-size: 0.75rem; }
        .badge-s-cancelled { background: #c0392b20; color: #e74c3c; border: 1px solid #c0392b40; padding: 0.35em 0.75em; border-radius: 20px; font-size: 0.75rem; }

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
        🀄 Mahjong Club
        <div style="font-size:0.7rem;font-weight:400;color:#555;margin-top:2px">Admin Dashboard</div>
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
        <div style="font-size:0.8rem;color:#666">Login sebagai</div>
        <div style="font-weight:600;color:var(--gold)">{{ auth()->user()->name ?? 'Admin' }}</div>
        <form method="POST" action="{{ route('logout') }}" class="mt-2">
            @csrf
            <button type="submit" class="btn btn-sm btn-outline-secondary w-100">
                <i class="bi bi-box-arrow-right me-1"></i>Logout
            </button>
        </form>
    </div>
</aside>

<!-- Main content -->
<div class="main-content">
    <div class="topbar">
        <button class="btn btn-sm me-3 d-lg-none" onclick="document.getElementById('sidebar').classList.toggle('show')" style="color:var(--gold);background:transparent;border:1px solid var(--border)">
            <i class="bi bi-list fs-5"></i>
        </button>
        <div>
            <h6 class="mb-0 fw-600">@yield('page-title', 'Dashboard')</h6>
            <small class="text-muted">{{ now()->format('l, d F Y') }}</small>
        </div>
        <div class="ms-auto d-flex align-items-center gap-2">
            <span style="font-size:0.8rem;color:#555">{{ now()->format('H:i') }} WIB</span>
        </div>
    </div>

    <div class="page-content">
        @if(session('success'))
            <div class="alert border-0 mb-3" style="background:#16a08520;color:#1abc9c;border-radius:10px;">
                <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="alert border-0 mb-3" style="background:#c0392b20;color:#e74c3c;border-radius:10px;">
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
