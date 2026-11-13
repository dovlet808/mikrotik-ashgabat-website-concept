<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ $title }} — MikroTik Ashgabat</title>
    <style>
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; background: #f3f0eb; color: #121314; font-family: ui-sans-serif, system-ui, -apple-system, 'Segoe UI', sans-serif; }
        main { max-width: 34rem; padding: 2rem; }
        p.code { font-family: ui-monospace, Menlo, monospace; color: #61676d; letter-spacing: .12em; font-size: .8rem; }
        h1 { font-size: clamp(1.8rem, 5vw, 2.6rem); line-height: 1.1; margin: .5rem 0 1rem; }
        p { color: #454a4f; line-height: 1.6; }
        a { color: #121314; }
    </style>
</head>
<body>
    <main>
        <p class="code">{{ $code }} · MIKROTIK ASHGABAT</p>
        <h1>{{ $title }}</h1>
        <p>{{ $text }}</p>
        <p><a href="/">{{ __('site.errors.home') }}</a></p>
    </main>
</body>
</html>
