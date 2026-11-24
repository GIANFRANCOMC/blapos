<!DOCTYPE html>

@php($ownerApp = config("app.owner_app"))

<html lang="es" dir="ltr">
    <head>
        @include('Guest.layouts.partials.up')
        @vite(['resources/css/Guest/app.css', 'resources/js/Shared/disabled-controls.js'])
        <script>
            window.ownerApp = @json($ownerApp ?? null);
            window.withMenu = {{ json_encode($withMenu ?? true) }};
            window.company  = @json($company);
            window.numericInputConfig = @json(\App\Helpers\System\Numbers::inputConfig());
            window.branch   = @json($branch ?? null);
            window.captchaSiteKey = @json(config("app.CAPTCHA_KEY_FRONTEND"));
            window.publicAttendanceAccess = @json($publicAttendanceAccess ?? null);
        </script>
    </head>
    <body class="fg-theme fg-guest-page">
        @if($withMenu ?? true)
            <nav class="fg-guest-nav" aria-label="Navegación del portal">
                <div class="container">
                    <div class="navbar navbar-expand-lg fg-guest-nav__bar">
                        <button class="navbar-toggler fg-button fg-button--icon fg-guest-nav__toggle" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMenu" aria-controls="navbarMenu" aria-expanded="false" aria-label="Alternar menú">
                            <i class="fa-solid fa-bars" aria-hidden="true"></i>
                        </button>
                        <img src="{{ asset($company->logomark ? ('storage/'.$company->logomark) : $ownerApp->assets->img->logomark) }}" class="fg-guest-nav__logo" alt="{{ $company->commercial_name }}">
                        <div class="collapse navbar-collapse" id="navbarMenu">
                            <ul class="navbar-nav fg-guest-nav__links">
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ url($company->slug.'/home') }}">Inicio</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ url($company->slug.'/book_complaints') }}">Libro de reclamaciones</a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </nav>
        @endif
        @yield('content')

        @include("Guest.layouts.partials.down")
        @vite('resources/js/Guest/Layout/main.js')
    </body>
</html>
