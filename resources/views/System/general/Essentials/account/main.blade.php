@extends("System/layouts/main")

@section("content")
    <div id="app" data-endpoint="{{ route('account.data') }}"></div>
    @vite("resources/js/System/Pages/Essentials/account/main.js")
@endsection
