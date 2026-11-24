<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
@php($ownerApp = config("app.owner_app"))
<title>{{ $ownerApp->commercial_name }}</title>
<link rel="icon" type="image/x-icon" href="{{ asset($ownerApp->assets->img->logomark) }}">
<link rel="stylesheet" href="{{ asset('System/assets/vendor/fonts/fontawesome.css') }}">
<link rel="stylesheet" href="{{ asset('System/assets/vendor/fonts/tabler-icons.css') }}">
