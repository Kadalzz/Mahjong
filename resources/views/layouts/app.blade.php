<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Hóng Zhōng Mahjong - Reservasi Meja Mahjong Online">
    <title>@yield('title', 'Hóng Zhōng Mahjong') | Hóng Zhōng Mahjong</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@600;700;800&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --green: #0e300f;
            --green-deep: #0a2410;
            --red: #551414;
            --red-light: #7a2020;
            --cream: #f4f1dd;
            --ink: #3a1414;
            --ink-mute: #6b5d4a;
            --border: rgba(244,241,221,0.14);
            /* legacy aliases kept so existing markup/components keep working */
            --gold: var(--red);
            --gold-light: var(--red-light);
            --dark: var(--green);
            --dark-card: var(--cream);
            --dark-border: var(--border);
        }
        * { font-family: 'Nunito', sans-serif; }
        h1, h2, h3, h4, h5, h6, .display-font { font-family: 'Baloo 2', sans-serif; }
        body { background: var(--green); color: var(--cream); min-height: 100vh; }
        .brand-logo-mark { color: var(--cream); }
        .navbar-brand {
            display: flex; align-items: center; gap: 0.6rem;
            font-family: 'Baloo 2', sans-serif;
            font-weight: 700; font-size: 1.1rem; line-height: 1.1;
            color: var(--cream) !important; letter-spacing: 0.3px;
        }
        .navbar-brand .brand-sub { display: block; font-weight: 600; font-size: 0.8rem; opacity: 0.85; }
        .navbar {
            background: rgba(10,36,16,0.92) !important;
            border-bottom: 1px solid var(--border);
            backdrop-filter: blur(10px); padding: 0.8rem 0;
        }
        .nav-link { color: rgba(244,241,221,0.75) !important; font-weight: 700; transition: color 0.2s; }
        .nav-link:hover, .nav-link.active { color: var(--cream) !important; }
        .btn-gold {
            background: var(--red);
            color: var(--cream); font-weight: 700; border: none;
            padding: 0.55rem 1.5rem; border-radius: 999px; transition: all 0.3s;
            font-family: 'Baloo 2', sans-serif;
        }
        .btn-gold:hover {
            background: var(--red-light);
            transform: translateY(-1px);
            box-shadow: 0 4px 20px rgba(85,20,20,0.45); color: var(--cream);
        }
        .card-dark {
            background: var(--cream); border: 1px solid rgba(58,20,20,0.1);
            border-radius: 16px; color: var(--ink);
        }
        .text-gold { color: var(--red) !important; }
        footer {
            background: var(--green-deep); border-top: 1px solid var(--border);
            padding: 2rem 0; margin-top: 4rem; color: rgba(244,241,221,0.55); font-size: 0.875rem;
        }
    </style>
    @yield('head')
</head>
<body>
<nav class="navbar navbar-expand-lg sticky-top">
    <div class="container">
        <a class="navbar-brand" href="{{ route('booking.index') }}">
            <x-brand-logo :size="34" />
            <span>HÓNG ZHŌNG<span class="brand-sub">MAHJONG</span></span>
        </a>
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
            <i class="bi bi-list fs-4" style="color:#F4C95D"></i>
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
                            <button type="submit" class="btn btn-sm btn-outline-light ms-2" style="border-radius:999px;">
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
            <div class="alert alert-dismissible fade show border-0" style="background:#DCEFDD;color:#1f7a34;border:1px solid #2f9e4440 !important;border-radius:12px">
                <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
    @endif
    @if(session('error'))
        <div class="container mt-3">
            <div class="alert alert-dismissible fade show border-0" style="background:#F6DEDE;color:#551414;border-radius:12px">
                <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
    @endif
    @yield('content')
</main>

<footer>
    <div class="container text-center">
        <p class="mb-0">🀄 <strong class="text-gold" style="color:var(--cream) !important;">RICHARD CHRISTIAN S</strong> &copy; {{ date('Y') }} — Sistem Reservasi Meja Mahjong</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@yield('scripts')
</body>
</html>
