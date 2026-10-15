<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8"/>
        <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0"/>
        <meta name="description" content=""/>

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

        <!-- Page -->
        <link rel="stylesheet" href="{{ asset('System/assets/vendor/css/pages/page-auth.css') }}" />
        <link rel="stylesheet" href="{{ asset("System/assets/css/br-branding.css") }}?v={{ filemtime(public_path("System/assets/css/br-branding.css")) }}" />
    </head>
    <body>
        <div class="container-xxl container-p-y">
            <div class="d-flex justify-content-center align-items-center">
                <div class="text-center">
                    <h1 class="mb-2 mx-2" style="line-height: 6rem;font-size: 6rem;">500</h1>
                    <div class="d-block fw-bold fs-4">
                        <i class="fa fa-warning text-warning"></i>
                        <span class="ms-1">Internal Server Error</span>
                    </div>
                    @if($msg)
                        <div class="card my-3">
                            <div class="card-body">
                                <span class="badge bg-label-primary">Mensaje:</span>
                                <span>{{ $msg ?? "" }}</span>
                            </div>
                        </div>
                    @endif
                    @if(url()->previous())
                        <a href="{{ url()->previous() }}" class="btn btn-primary mb-10 waves-effect">
                            <i class="fa fa-arrow-left fa-beat"></i>
                            <span class="ms-1">Regresar</span>
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </body>
</html>
