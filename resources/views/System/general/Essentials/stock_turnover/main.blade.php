@extends("System/layouts/main")

@section("content")
    <div id="app"
        data-params-url="{{ route('analytics.stock_turnover_replenishment.initParams') }}"
        data-list-url="{{ route('analytics.stock_turnover_replenishment.list') }}"></div>
    @vite('resources/js/System/Pages/Essentials/stock_turnover/main.js')
@endsection
