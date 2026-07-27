<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Mahjong Club - Reservasi Meja Mahjong Online">
    <title>@yield('title', 'Mahjong Club') | Mahjong Club</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --gold: #c9a84c;
            --gold-light: #e8c97a;
            --dark: #0f0f1a;
            --dark-card: #1a1a2e;
            --dark-border: #2a2a45;
        }
        * { font-family: 'Inter', sans-serif; }
        body { background: var(--dark); color: #e0e0e0; min-height: 100vh; }
        .navbar-brand {
            font-weight: 700; font-size: 1.4rem;
            color: var(--gold) !important; letter-spacing: 0.5px;
        }
        .navbar {
            background: rgba(15,15,26,0.95) !important;
            border-bottom: 1px solid var(--dark-border);
            backdrop-filter: blur(10px); padding: 0.8rem 0;
        }
        .nav-link { color: #a0a0c0 !important; font-weight: 500; transition: color 0.2s; }
        .nav-link:hover, .nav-link.active { color: var(--gold) !important; }
        .btn-gold {
            background: linear-gradient(135deg, var(--gold), var(--gold-light));
            color: #1a1200; font-weight: 600; border: none;
            padding: 0.5rem 1.5rem; border-radius: 8px; transition: all 0.3s;
        }
        .btn-gold:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 20px rgba(201,168,76,0.4); color: #1a1200;
        }
        .card-dark {
            background: var(--dark-card); border: 1px solid var(--dark-border);
            border-radius: 16px; color: #e0e0e0;
        }
        .text-gold { color: var(--gold) !important; }
        footer {
            background: var(--dark-card); border-top: 1px solid var(--dark-border);
            padding: 2rem 0; margin-top: 4rem; color: #666; font-size: 0.875rem;
        }
    </style>
    @yield('head')
</head>
<body>
<nav class="navbar navbar-expand-lg sticky-top">
    <div class="container">
        <a class="navbar-brand" href="{{ route('booking.index') }}">
            <span style="font-size:1.5rem">🀄</span> Mahjong Club
        </a>
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
            <i class="bi bi-list text-gold fs-4"></i>
        </button>
        <div class="collapse navbar-collapse" id="navMain">
            <ul class="navbar-nav ms-auto align-items-center gap-2">
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('booking.index') ? 'active' : '' }}" href="{{ route('booking.index') }}">
                        <i class="bi bi-table me-1"></i>Pesan Meja
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('schedule.index') ? 'active' : '' }}" href="{{ route('schedule.index') }}">
                        <i class="bi bi-calendar3 me-1"></i>Jadwal
                    </a>
                </li>
                @auth
                    @if(auth()->user()->isAdmin())
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('dashboard.*') ? 'active' : '' }}" href="{{ route('dashboard.index') }}">
                            <i class="bi bi-speedometer2 me-1"></i>Dashboard
                        </a>
                    </li>
                    @endif
                    <li class="nav-item">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-secondary ms-2">
                                <i class="bi bi-box-arrow-right me-1"></i>Logout
                            </button>
                        </form>
                    </li>
                @else
                    <li class="nav-item">
                        <a href="{{ route('login') }}" class="btn btn-gold btn-sm ms-2">Login Admin</a>
                    </li>
                @endauth
            </ul>
        </div>
    </div>
</nav>

<main>
    @if(session('success'))
        <div class="container mt-3">
            <div class="alert alert-dismissible fade show border-0" style="background:#16a08520;color:#1abc9c;border:1px solid #16a08540 !important;border-radius:12px">
                <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
    @endif
    @if(session('error'))
        <div class="container mt-3">
            <div class="alert alert-dismissible fade show border-0" style="background:#c0392b20;color:#e74c3c;border-radius:12px">
                <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
    @endif
    @yield('content')
</main>

<footer>
    <div class="container text-center">
        <p class="mb-0">🀄 <strong class="text-gold">Mahjong Club</strong> &copy; {{ date('Y') }} — Sistem Reservasi Meja Mahjong</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@yield('scripts')
</body>
</html>
