@extends("System/layouts/main")

@section("content")
    <div id="app" data-login-image-url="{{ route('login.image', [], false) }}" data-combination-mark-url="{{ route('login.brand', [], false) }}"></div>
    @vite('resources/js/System/Pages/Organizations/companies/main.js')
@endsection
