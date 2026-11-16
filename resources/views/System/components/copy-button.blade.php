@php
    $copyValue = trim((string) ($value ?? ""));
    $copyTag = ($type ?? 'icon') === 'button' ? 'button' : 'span';
@endphp
@if($copyValue !== "")
    <{{ $copyTag }}
        @if($copyTag === 'button')
            type="button" class="br-copy-button"
        @else
            class="br-copy-icon" role="button" tabindex="0"
        @endif
        data-br-copy-value="{{ $copyValue }}"
        data-bs-toggle="tooltip"
        data-bs-placement="top"
        title="Copiar"
        aria-label="Copiar {{ Str::lower($label) }}">
        <i class="fa-solid fa-clipboard" aria-hidden="true"></i>
    </{{ $copyTag }}>
@endif
