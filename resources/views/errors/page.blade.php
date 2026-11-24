@php
    $ownerApp = config("app.owner_app");
    $appName = $ownerApp->commercial_name ?? config("app.name", "Blapos");
    $detail = trim((string) ($msg ?? ""));
    $requiresLogin = in_array($code, [401, 419], true);
    $actionHref = $actionHref ?? ($requiresLogin ? url("/login") : url("/"));
    $actionText = $actionText ?? ($requiresLogin ? "Iniciar sesión" : "Ir al inicio");
    $supportContact = app(\App\Services\System\Tenancy\PlatformSupportService::class)->contact();
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <meta name="robots" content="noindex, nofollow"/>
    <title>{{ $code }} · {{ $heading }} | {{ $appName }}</title>
    <link rel="icon" href="{{ asset($ownerApp->assets->img->logomark) }}"/>
    @vite('resources/css/Shared/error-page.css')
</head>
<body class="fg-error-page">
    <div class="fg-error-shell">
        <header class="fg-error-brand">
            <img src="{{ asset($ownerApp->assets->img->logomark) }}" alt="" width="36" height="36"/>
            <span>{{ $appName }}</span>
        </header>

        <main class="fg-error-content">
            <span class="fg-error-code">Error {{ $code }}</span>
            <h1>{{ $heading }}</h1>
            <p class="fg-error-description">{{ $description }}</p>

            @if($detail !== "")
                <p class="fg-error-detail">{{ $detail }}</p>
            @endif

            <a class="fg-error-action" href="{{ $actionHref }}">
                {{ $actionText }}
            </a>
        </main>
        <footer class="fg-error-support" aria-label="Ayuda de Blapos">
            @include("shared.support-contact")
        </footer>
    </div>
</body>
</html>
