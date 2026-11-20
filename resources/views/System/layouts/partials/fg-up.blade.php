<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
@php($ownerApp = config("app.owner_app"))
<title>{{ $ownerApp->commercial_name }}</title>
<link rel="icon" type="image/x-icon" href="{{ asset($ownerApp->assets->img->logomark) }}">
{{-- Bootstrap aporta comportamiento y compatibilidad con pantallas pendientes de migrar. FG define la identidad. --}}
<link rel="stylesheet" href="{{ asset('System/assets/vendor/bootstrap/bootstrap.min.css') }}">
<link rel="stylesheet" href="{{ asset('System/assets/vendor/fonts/fontawesome.css') }}">
<link rel="stylesheet" href="{{ asset('System/assets/vendor/fonts/tabler-icons.css') }}">
<link rel="stylesheet" href="{{ asset('System/assets/vendor/fonts/flag-icons.css') }}">
<link rel="stylesheet" href="{{ asset('System/assets/vendor/libs/toastr/toastr.css') }}">
<link rel="stylesheet" href="{{ asset('System/assets/vendor/libs/select2/select2.css') }}">
<link rel="stylesheet" href="{{ asset('System/assets/vendor/libs/bootstrap-select/bootstrap-select.css') }}">
