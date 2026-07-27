<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin | Mahjong Club</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }
        body {
            background: #0f0f1a;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }
        body::before {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(ellipse at 30% 40%, rgba(201,168,76,0.06) 0%, transparent 60%),
                        radial-gradient(ellipse at 70% 70%, rgba(41,128,185,0.04) 0%, transparent 60%);
        }
        .login-card {
            width: 100%;
            max-width: 420px;
            background: #1a1a2e;
            border: 1px solid #2a2a45;
            border-radius: 24px;
            padding: 2.5rem;
            position: relative;
            z-index: 1;
        }
        .login-logo {
            text-align: center;
            margin-bottom: 2rem;
        }
        .login-logo .tile { font-size: 3rem; display: block; margin-bottom: 0.5rem; }
        .login-logo h1 { font-size: 1.5rem; font-weight: 700; color: #c9a84c; margin-bottom: 0.25rem; }
        .login-logo p { font-size: 0.875rem; color: #666; }
        .form-label { color: #888; font-size: 0.875rem; font-weight: 500; }
        .form-control {
            background: #0f0f1a !important;
            border: 1px solid #2a2a45 !important;
            color: #e0e0e0 !important;
            border-radius: 10px;
            padding: 0.75rem 1rem;
        }
        .form-control:focus {
            border-color: rgba(201,168,76,0.5) !important;
            box-shadow: 0 0 0 3px rgba(201,168,76,0.1) !important;
        }
        .form-control::placeholder { color: #333; }
        .btn-login {
            background: linear-gradient(135deg, #c9a84c, #e8c97a);
            color: #1a1200;
            font-weight: 700;
            border: none;
            border-radius: 10px;
            padding: 0.8rem;
            width: 100%;
            font-size: 0.95rem;
            transition: all 0.3s;
        }
        .btn-login:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 25px rgba(201,168,76,0.35);
        }
        .alert-error {
            background: #c0392b20;
            color: #e74c3c;
            border: 1px solid #c0392b40;
            border-radius: 10px;
            padding: 0.75rem 1rem;
            font-size: 0.875rem;
            margin-bottom: 1rem;
        }
        .back-link {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.875rem;
        }
        .back-link a { color: #555; text-decoration: none; transition: color 0.2s; }
        .back-link a:hover { color: #c9a84c; }
        .hint-box {
            background: rgba(201,168,76,0.06);
            border: 1px solid rgba(201,168,76,0.15);
            border-radius: 10px;
            padding: 0.75rem 1rem;
            margin-top: 1rem;
            font-size: 0.78rem;
            color: #888;
        }
        .hint-box code { color: #c9a84c; }
    </style>
</head>
<body>
<div class="login-card">
    <div class="login-logo">
        <span class="tile">🀄</span>
        <h1>Mahjong Club</h1>
        <p>Login Admin & Viewer</p>
    </div>

    @if ($errors->any())
    <div class="alert-error">
        <i class="bi bi-exclamation-triangle me-2"></i>
        {{ $errors->first() }}
    </div>
    @endif

    @if (session('status'))
    <div class="mb-3 p-3 rounded-2" style="background:#16a08520;color:#1abc9c;font-size:0.875rem">
        {{ session('status') }}
    </div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf
        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input id="email" type="email" name="email"
                class="form-control"
                value="{{ old('email') }}"
                placeholder="admin@mahjong.com"
                required autofocus>
        </div>
        <div class="mb-4">
            <label for="password" class="form-label">Password</label>
            <input id="password" type="password" name="password"
                class="form-control"
                placeholder="••••••••"
                required>
        </div>
        <button type="submit" class="btn-login">
            <i class="bi bi-box-arrow-in-right me-2"></i>Masuk
        </button>
    </form>

    <div class="hint-box">
        <div class="mb-1"><strong style="color:#c9a84c">Demo Akun:</strong></div>
        <div>Admin: <code>admin@mahjong.com</code> / <code>admin123</code></div>
        <div>Viewer: <code>viewer@mahjong.com</code> / <code>viewer123</code></div>
    </div>

    <div class="back-link">
        <a href="{{ route('booking.index') }}">
            <i class="bi bi-arrow-left me-1"></i>Kembali ke Halaman Utama
        </a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
