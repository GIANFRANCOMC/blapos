<!DOCTYPE html>

@php
    $ownerApp = config("app.owner_app");
    $systemAssetsPath = rtrim(asset('System/assets'), '/').'/';
@endphp

<html
    lang="es"
    class="light-style layout-navbar-fixed layout-wide"
    dir="ltr"
    data-theme="theme-default"
    data-assets-path="{{ $systemAssetsPath }}"
    data-template="front-pages-no-customizer">
    <head>
        @include('Guest.layouts.partials.up')
        @vite('resources/js/Shared/disabled-controls.js')
        <script>
            window.ownerApp = @json($ownerApp ?? null);
            window.withMenu = {{ json_encode($withMenu ?? true) }};
            window.company  = @json($company);
            window.branch   = @json($branch ?? null);
            window.captchaSiteKey = @json(config("app.CAPTCHA_KEY_FRONTEND"));
            window.publicAttendanceAccess = @json($publicAttendanceAccess ?? null);
        </script>
    </head>
    <style>
        .bg-down-color-primary {
            background-color: #212121 !important;
        }

        .bg-down-color-secondary {
            background-color: #2c2c2c !important;
        }

        .bg-primary-custom {
            background-color: #ba60d7 !important;
        }

        .text-primary-custom {
            color: #ba60d7 !important;
        }

        .a-primary-custom {
            transition: color 0.3s ease-in-out;;
        }

        .a-primary-custom:hover {
            color: #ba60d7 !important;
        }

        .text-white-custom {
            color: #f4f4f4 !important;
        }

        .navbar-logomark {
            height: 100px;
            width: auto;
            position: absolute;
            top: 5px;
            bottom: 0;
            left: 1px;
        }
    </style>
    <body class="br-guest-page">
        @if($withMenu ?? true)
            <nav class="layout-navbar shadow-none">
                <div class="container">
                    <div class="navbar navbar-expand-lg px-3 px-md-5 py-2 py-md-4 mt-3 shadow-sm bg-down-color-primary">
                        <div class="navbar-brand app-brand">
                            <button class="navbar-toggler text-white-custom me-3" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMenu" aria-controls="navbarMenu" aria-expanded="false" aria-label="Toggle navigation">
                                <i class="ti ti-menu-2 ti-sm align-middle"></i>
                            </button>
                            <div class="d-none d-lg-block">
                                <img src="{{ asset($company->logomark ? ('storage/'.$company->logomark) : $ownerApp->assets->img->logomark) }}" class="navbar-logomark">
                            </div>
                        </div>
                        <div class="collapse navbar-collapse landing-nav-menu" id="navbarMenu">
                            <button class="navbar-toggler border-0 position-absolute end-0 top-0 text-white-custom" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMenu" aria-controls="navbarMenu" aria-expanded="false" aria-label="Toggle navigation">
                                <i class="fa fa-times fs-3"></i>
                            </button>
                            <ul class="navbar-nav me-auto d-flex gap-4 mx-3 mx-md-5 px-0 px-md-4">
                                <li class="nav-item">
                                    <a class="fw-semibold fs-6 text-white-custom a-primary-custom" href="{{ url($company->slug.'/home') }}">
                                        <span class="text-uppercase">Inicio</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="fw-semibold fs-6 text-white-custom a-primary-custom" href="{{ url($company->slug.'/book_complaints') }}">
                                        <span class="text-uppercase">Libro de reclamaciones</span>
                                    </a>
                                </li>
                            </ul>
                            <ul class="navbar-nav flex-row align-items-center ms-auto">
                                {{--  --}}
                            </ul>
                        </div>
                    </div>
                </div>
            </nav>
        @endif
        <div data-bs-spy="scroll" class="scrollspy-example">
            <div>
                @yield('content')
                <div class="content-backdrop fade"></div>
            </div>
        </div>

        @include("Guest.layouts.partials.down")
    </body>
</html>
