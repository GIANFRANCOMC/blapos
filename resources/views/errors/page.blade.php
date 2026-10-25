@php
    $ownerApp = config("app.owner_app");
    $appName = $ownerApp->commercial_name ?? config("app.name", "Blapos");
    $detail = trim((string) ($msg ?? ""));
    $requiresLogin = in_array($code, [401, 419], true);
    $actionHref = $actionHref ?? ($requiresLogin ? url("/login") : url("/"));
    $actionText = $actionText ?? ($requiresLogin ? "Iniciar sesión" : "Ir al inicio");
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <meta name="robots" content="noindex, nofollow"/>
    <title>{{ $code }} · {{ $heading }} | {{ $appName }}</title>
    <link rel="icon" href="{{ asset($ownerApp->assets->img->logomark) }}"/>
    @include("shared.font-inter")
    <link rel="stylesheet" href="{{ asset('System/assets/css/br-branding.css') }}"/>
</head>
<body class="br-error-page">
    <div class="br-error-shell">
        <header class="br-error-brand">
            <img src="{{ asset($ownerApp->assets->img->logomark) }}" alt="" width="36" height="36"/>
            <span>{{ $appName }}</span>
        </header>

        <main class="br-error-content">
            <span class="br-error-code">Error {{ $code }}</span>
            <h1>{{ $heading }}</h1>
            <p class="br-error-description">{{ $description }}</p>

            @if($detail !== "")
                <p class="br-error-detail">{{ $detail }}</p>
            @endif

            <a class="br-error-action" href="{{ $actionHref }}">
                {{ $actionText }}
            </a>
        </main>
    </div>
</body>
</html>
