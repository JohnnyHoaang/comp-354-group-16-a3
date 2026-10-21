<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') · MediLink</title>
    <link rel="stylesheet" href="https://ka-f.webawesome.com/webawesome@3.14.0/styles/themes/default.css">
    <script type="module" src="https://ka-f.webawesome.com/webawesome@3.14.0/webawesome.loader.js"></script>
    <style>
        body { margin: 0; padding: 1rem; background: #f5f7fa; color: #202938; font-family: system-ui, sans-serif; line-height: 1.5; }
        main { max-width: 26rem; margin: 3rem auto; padding: 1.5rem; background: white; border: 1px solid #dce1e8; border-radius: .75rem; overflow-wrap: anywhere; }
        h1 { margin: .5rem 0 1.5rem; font-size: 1.5rem; }
        .brand { margin: 0; color: #526075; font-weight: 600; }
        wa-input { display: block; margin-bottom: 1rem; }
        .help-text { color: #526075; }
        .error { color: #a51d2d; }
    </style>
</head>
<body>
    <main>
        <p class="brand">MediLink</p>
        <h1>@yield('title')</h1>
        @yield('content')
    </main>
</body>
</html>
