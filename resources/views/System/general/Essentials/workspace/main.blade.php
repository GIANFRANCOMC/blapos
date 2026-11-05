@extends("System/layouts/main")

@section("content")
    <div id="app" data-endpoint="{{ route('workspace.data') }}"></div>
    @vite("resources/js/System/Pages/Essentials/workspace/main.js")
@endsection
