<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin | Hóng Zhōng Mahjong</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@600;700;800&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        * { font-family: 'Nunito', sans-serif; }
        body {
            background: #0e300f;
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
            background: radial-gradient(ellipse at 30% 40%, rgba(244,241,221,0.05) 0%, transparent 60%),
                        radial-gradient(ellipse at 70% 70%, rgba(85,20,20,0.15) 0%, transparent 60%);
        }
        .login-card {
            width: 100%;
            max-width: 420px;
            background: #f4f1dd;
            border-radius: 24px;
            padding: 2.5rem;
            position: relative;
            z-index: 1;
            color: #3a1414;
        }
        .login-logo {
            text-align: center;
            margin-bottom: 2rem;
        }
        .login-logo .tile { color: #551414; display: flex; justify-content: center; margin-bottom: 0.75rem; }
        .login-logo h1 { font-family: 'Baloo 2', sans-serif; font-size: 1.3rem; font-weight: 800; text-transform: uppercase; color: #551414; margin-bottom: 0.25rem; }
        .login-logo p { font-size: 0.875rem; color: #6b5d4a; }
        .form-label { color: #6b5d4a; font-size: 0.875rem; font-weight: 700; }
        .form-control {
            background: rgba(58,20,20,0.06) !important;
            border: none !important;
            color: #3a1414 !important;
            border-radius: 999px;
            padding: 0.75rem 1.25rem;
            font-weight: 600;
        }
        .form-control:focus {
            box-shadow: 0 0 0 3px rgba(85,20,20,0.15) !important;
        }
        .form-control::placeholder { color: #9a8a72; }
        .btn-login {
            background: #551414;
            color: #f4f1dd;
            font-weight: 700;
            font-family: 'Baloo 2', sans-serif;
            border: none;
            border-radius: 999px;
            padding: 0.8rem;
            width: 100%;
            font-size: 0.95rem;
            transition: all 0.3s;
        }
        .btn-login:hover {
            background: #7a2020;
            transform: translateY(-1px);
            box-shadow: 0 8px 25px rgba(85,20,20,0.35);
            color: #f4f1dd;
        }
        .alert-error {
            background: #F6DEDE;
            color: #551414;
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
        .back-link a { color: #6b5d4a; text-decoration: none; transition: color 0.2s; }
        .back-link a:hover { color: #551414; }
        .hint-box {
            background: rgba(85,20,20,0.06);
            border-radius: 10px;
            padding: 0.75rem 1rem;
            margin-top: 1rem;
            font-size: 0.78rem;
            color: #6b5d4a;
        }
        .hint-box code { color: #551414; }
    </style>
</head>
<body>
<div class="login-card">
    <div class="login-logo">
        <span class="tile"><x-brand-logo :size="52" variant="dark" /></span>
        <h1>Hóng Zhōng Mahjong</h1>
        <p>Login Admin & Viewer</p>
    </div>

    @if ($errors->any())
    <div class="alert-error">
        <i class="bi bi-exclamation-triangle me-2"></i>
        {{ $errors->first() }}
    </div>
    @endif

    @if (session('status'))
    <div class="mb-3 p-3 rounded-2" style="background:#DCEFDD;color:#1f7a34;font-size:0.875rem">
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

    <div class="back-link">
        <a href="{{ route('booking.index') }}">
            <i class="bi bi-arrow-left me-1"></i>Kembali ke Halaman Utama
        </a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
