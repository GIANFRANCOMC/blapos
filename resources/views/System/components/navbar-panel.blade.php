<div id="{{ $id }}" class="dropdown-menu dropdown-menu-end fg-menu__panel fg-topbar__panel" role="region" aria-labelledby="{{ $id }}-title" @if($focusable ?? false) tabindex="0" @endif>
    <header class="fg-topbar__panel-heading">{{ $heading }}</header>
    <div class="fg-topbar__panel-body">{{ $slot }}</div>
    @isset($footer)
        <footer class="fg-topbar__panel-footer">{{ $footer }}</footer>
    @endisset
</div>
