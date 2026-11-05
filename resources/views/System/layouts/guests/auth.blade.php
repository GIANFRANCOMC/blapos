<!DOCTYPE html>

@php
    $systemAssetsPath = rtrim(asset('System/assets'), '/').'/';
@endphp

<html
    lang="en"
    class="light-style layout-navbar-fixed layout-menu-fixed layout-compact"
    dir="ltr"
    data-theme="theme-default"
    data-assets-path="{{ $systemAssetsPath }}"
    data-template="vertical-menu-template-starter">
    <head>
        @include("System.layouts.partials.up")
        <link rel="stylesheet" href="{{ asset('System/assets/css/br-login.css') }}" />
        @vite('resources/js/Shared/disabled-controls.js')
    </head>
    <body>
        {{ $slot }}
        @include("System.layouts.partials.down")
    </body>
</html>
