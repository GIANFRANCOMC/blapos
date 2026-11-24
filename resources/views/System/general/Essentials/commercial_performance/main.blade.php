@extends("System/layouts/main")

@section("content")
    <div id="app"
        data-params-url="{{ route('analytics.commercial_performance.initParams') }}"
        data-list-url="{{ route('analytics.commercial_performance.list') }}"></div>
    @vite('resources/js/System/Pages/Essentials/commercial_performance/main.js')
@endsection
