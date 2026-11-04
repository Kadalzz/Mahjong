<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Hóng Zhōng Mahjong')</title>
    <link rel="preload" href="{{ asset('fonts/poin/baloo2-latin.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="{{ asset('fonts/poin/nunito-latin.woff2') }}" as="font" type="font/woff2" crossorigin>
    @vite(['resources/css/poin.css', 'resources/js/poin.js'])
</head>
<body>
    @yield('content')
    @stack('dialogs')
</body>
</html>
