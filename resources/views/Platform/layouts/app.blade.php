<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow, noarchive">
    <meta name="referrer" content="same-origin">
    <meta name="theme-color" content="#0b63a9">
    <title>@yield('title', 'Clientes | Blapos')</title>
    <link rel="icon" type="image/png" href="{{ asset('System/assets/img/utils/owner_app/logomark.png') }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('System/assets/img/utils/owner_app/logomark.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('System/assets/img/utils/owner_app/logomark.png') }}">
    @include("shared.font-inter")
    <link rel="stylesheet" href="{{ asset('System/assets/vendor/css/rtl/core.css') }}">
    <link rel="stylesheet" href="{{ asset('System/assets/vendor/fonts/fontawesome.css') }}">
    @vite('resources/css/Platform/app.css')
</head>
<body class="platform-body" data-platform="landlord">
<div class="platform-shell">
    <main>
        @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        @if($errors->any())<div class="alert alert-danger"><strong>Revisa la información.</strong><ul class="mb-0 mt-1">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        @yield('content')
    </main>
</div>
<script src="{{ asset('System/assets/vendor/js/bootstrap.js') }}"></script>
@stack('scripts')
</body>
</html>
