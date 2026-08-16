<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Hóng Zhōng Mahjong - Reservasi Meja Mahjong Online">
    <title>Hóng Zhōng Mahjong</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@600;700;800&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --green: #0e300f;
            --green-deep: #0a2410;
            --red: #551414;
            --red-light: #7a2020;
            --cream: #f4f1dd;
            --ink-mute: #6b5d4a;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Nunito', sans-serif; background: var(--green-deep); }

        .cover-screen {
            position: relative;
            height: 100vh;
            min-height: 640px;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        /* --- reactbits-style "Aurora" ambient background --- */
        .aurora {
            position: absolute;
            inset: 0;
            background: var(--green);
            overflow: hidden;
        }
        .aurora::before, .aurora::after {
            content: '';
            position: absolute;
            width: 70vmax;
            height: 70vmax;
            border-radius: 50%;
            filter: blur(90px);
            opacity: 0.35;
            animation: auroraDrift 16s ease-in-out infinite alternate;
        }
        .aurora::before {
            background: radial-gradient(circle, var(--red) 0%, transparent 65%);
            top: -20%; left: -15%;
        }
        .aurora::after {
            background: radial-gradient(circle, #d4a017 0%, transparent 65%);
            bottom: -25%; right: -10%;
            animation-delay: -8s;
        }
        @keyframes auroraDrift {
            0%   { transform: translate(0, 0) scale(1); }
            100% { transform: translate(6%, 4%) scale(1.15); }
        }
        .tile-pattern {
            position: absolute;
            inset: 0;
            opacity: 0.05;
            background-image:
                linear-gradient(rgba(244,241,221,0.6) 1px, transparent 1px),
                linear-gradient(90deg, rgba(244,241,221,0.6) 1px, transparent 1px);
            background-size: 56px 56px;
        }

        .cover-hero {
            position: relative;
            z-index: 2;
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 2rem 1.5rem;
        }
        .cover-logo {
            opacity: 0;
            animation: fadeUp 0.9s ease forwards;
            margin-bottom: 1.75rem;
        }
        .cover-eyebrow {
            font-family: 'Baloo 2', sans-serif;
            font-weight: 700;
            letter-spacing: 3px;
            text-transform: uppercase;
            font-size: 0.85rem;
            color: rgba(244,241,221,0.6);
            opacity: 0;
            animation: fadeUp 0.9s ease forwards;
            animation-delay: 0.15s;
        }

        /* --- reactbits-style "Split Text" character reveal --- */
        .split-text {
            font-family: 'Baloo 2', sans-serif;
            font-weight: 800;
            text-transform: uppercase;
            color: var(--cream);
            font-size: clamp(2.25rem, 7vw, 4.25rem);
            line-height: 1.05;
            margin: 0.5rem 0 1.25rem;
        }
        .split-text .char {
            display: inline-block;
            opacity: 0;
            transform: translateY(0.6em);
            animation: charReveal 0.6s cubic-bezier(0.2, 0.8, 0.2, 1) forwards;
        }
        @keyframes charReveal {
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(14px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .cover-tagline {
            color: rgba(244,241,221,0.78);
            font-size: 1.05rem;
            max-width: 480px;
            opacity: 0;
            animation: fadeUp 0.9s ease forwards;
            animation-delay: 1.5s;
        }

        .cover-band {
            position: relative;
            z-index: 2;
            background: var(--red);
            padding: 1.75rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1.25rem;
            flex-wrap: wrap;
            opacity: 0;
            animation: fadeUp 0.9s ease forwards;
            animation-delay: 1.8s;
        }
        .cover-band .band-text {
            font-family: 'Baloo 2', sans-serif;
            font-weight: 700;
            color: var(--cream);
            letter-spacing: 0.5px;
            text-transform: uppercase;
            font-size: 0.95rem;
        }
        .btn-enter {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: var(--cream);
            color: var(--red);
            font-family: 'Baloo 2', sans-serif;
            font-weight: 700;
            padding: 0.6rem 1.75rem;
            border-radius: 999px;
            text-decoration: none;
            transition: all 0.25s ease;
        }
        .btn-enter:hover {
            color: var(--red);
            transform: translateY(-2px);
            box-shadow: 0 10px 28px rgba(0,0,0,0.28);
        }
        .btn-enter i { transition: transform 0.25s ease; }
        .btn-enter:hover i { transform: translateX(4px); }

        @media (max-width: 576px) {
            .cover-band { flex-direction: column; text-align: center; }
        }
    </style>
</head>
<body>
<div class="cover-screen">
    <div class="aurora"></div>
    <div class="tile-pattern"></div>

    <div class="cover-hero">
        <div class="cover-logo">
            <x-brand-logo :size="56" />
        </div>
        <div class="cover-eyebrow">Selamat Datang</div>
        <h1 class="split-text" id="splitText" aria-label="Hóng Zhōng Mahjong"></h1>
        <p class="cover-tagline">Tempat bermain Mahjong dengan reservasi meja yang mudah, cepat, dan nyaman.</p>
    </div>

    <div class="cover-band">
        <span class="band-text">Hóng Zhōng Mahjong</span>
        <a href="{{ route('landing.index') }}" class="btn-enter">
            Masuk ke Website <i class="bi bi-arrow-right"></i>
        </a>
    </div>
</div>

<script>
    (function () {
        var el = document.getElementById('splitText');
        var text = 'Hóng Zhōng\nMahjong';
        var delay = 0.45;
        var step = 0.035;

        text.split('\n').forEach(function (line, lineIndex) {
            if (lineIndex > 0) el.appendChild(document.createElement('br'));
            Array.from(line).forEach(function (ch) {
                var span = document.createElement('span');
                span.className = 'char';
                span.style.animationDelay = delay + 's';
                span.textContent = ch === ' ' ? ' ' : ch;
                el.appendChild(span);
                delay += step;
            });
        });
    })();
</script>
</body>
</html>
