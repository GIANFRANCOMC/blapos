@php
    $publicTitle = trim((string) ($meta["title"] ?? $company->commercial_name));
    $publicDescription = trim((string) ($meta["description"] ?? ($company->description ?: "Conoce el catálogo, servicios y canales de atención de ".$company->commercial_name.".")));
    $publicImage = $meta["image"] ?? ($company->combinationmark ?: $company->logotype ?: $company->logomark);
    $publicImageUrl = $publicImage ? asset("storage/".$publicImage) : asset(config("app.owner_app")->assets->img->logomark);
    $publicUrl = url()->current();
@endphp

<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0" />
<meta name="description" content="{{ $publicDescription }}" />
<meta name="robots" content="index,follow" />
<link rel="canonical" href="{{ $publicUrl }}" />

<meta property="og:type" content="website" />
<meta property="og:title" content="{{ $publicTitle }}" />
<meta property="og:description" content="{{ $publicDescription }}" />
<meta property="og:url" content="{{ $publicUrl }}" />
<meta property="og:image" content="{{ $publicImageUrl }}" />
<meta property="og:site_name" content="{{ $company->commercial_name }}" />
<meta name="twitter:card" content="summary_large_image" />
<meta name="twitter:title" content="{{ $publicTitle }}" />
<meta name="twitter:description" content="{{ $publicDescription }}" />
<meta name="twitter:image" content="{{ $publicImageUrl }}" />

<title>{{ $publicTitle }}</title>

<!-- Favicon -->
<link rel="icon" type="image/x-icon" href="{{ $company->logomark ? asset('storage/'.$company->logomark) : asset(config("app.owner_app")->assets->img->logomark) }}"/>

<!-- Icons -->
<link rel="stylesheet" href="{{ asset('System/assets/vendor/fonts/fontawesome.css') }}" />
