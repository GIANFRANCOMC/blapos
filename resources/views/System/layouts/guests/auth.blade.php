<!DOCTYPE html>

<html lang="es" dir="ltr">
    <head>
        @include("System.layouts.partials.fg-up")
        @vite(['resources/css/System/fg/auth.css', 'resources/js/Shared/disabled-controls.js'])
    </head>
    <body class="fg-theme">
        {{ $slot }}
        @include('shared.sweetalert')
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" defer></script>
    </body>
</html>
