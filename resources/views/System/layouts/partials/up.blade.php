<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
<meta name="description" content="" />

@php
    $ownerApp = config("app.owner_app");
@endphp

<title>{{ $ownerApp->commercial_name }}</title>

<!-- Favicon -->
<link rel="icon" type="image/x-icon" href="{{ asset($ownerApp->assets->img->logomark) }}"/>

<!-- Fonts -->
@include("shared.font-inter")

<!-- Icons -->
<link rel="stylesheet" href="{{ asset('System/assets/vendor/fonts/tabler-icons.css') }}" />
<link rel="stylesheet" href="{{ asset('System/assets/vendor/fonts/fontawesome.css') }}" />
<link rel="stylesheet" href="{{ asset('System/assets/vendor/fonts/flag-icons.css') }}" />

<!-- Core CSS -->
<link rel="stylesheet" href="{{ asset('System/assets/vendor/css/rtl/core.css') }}" class="template-customizer-core-css" />
<link rel="stylesheet" href="{{ asset('System/assets/vendor/css/rtl/theme-default.css') }}" class="template-customizer-theme-css" />
<link rel="stylesheet" href="{{ asset('System/assets/css/demo.css') }}" />

<!-- Vendors CSS -->
<link rel="stylesheet" href="{{ asset('System/assets/vendor/libs/node-waves/node-waves.css') }}" />
<link rel="stylesheet" href="{{ asset('System/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css') }}" />
<link rel="stylesheet" href="{{ asset('System/assets/vendor/libs/sweetalert2/sweetalert2.css') }}" />
<link rel="stylesheet" href="{{ asset('System/assets/vendor/libs/toastr/toastr.css') }}" />
<link rel="stylesheet" href="{{ asset('System/assets/vendor/libs/animate-css/animate.css') }}" />

<link rel="stylesheet" href="{{ asset('System/assets/vendor/libs/select2/select2.css') }}" />
<link rel="stylesheet" href="{{ asset('System/assets/vendor/libs/bootstrap-select/bootstrap-select.css') }}" />
<link rel="stylesheet" href="{{ asset('System/assets/css/br-branding.css') }}?v={{ filemtime(public_path('System/assets/css/br-branding.css')) }}" />

<!-- Helpers -->
<script src="{{ asset('System/assets/vendor/js/helpers.js') }}"></script>
<!--! Template customizer & Theme config files MUST be included after core stylesheets and helpers.js in the <head> section -->
<!--? Template customizer: To hide customizer set displayCustomizer value false in config.js.  -->
<script src="{{ asset('System/assets/vendor/js/template-customizer.js') }}"></script>
<!--? Config:  Mandatory theme config file contain global vars & default theme options, Set your preferred theme option in this file.  -->
<script src="{{ asset('System/assets/js/config.js') }}"></script>
