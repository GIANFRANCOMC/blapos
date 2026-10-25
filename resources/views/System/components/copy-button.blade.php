@php($copyValue = trim((string) ($value ?? "")))
@if($copyValue !== "")
    <button
        type="button"
        class="br-copy-button"
        data-br-copy-value="{{ $copyValue }}"
        data-bs-toggle="tooltip"
        data-bs-placement="top"
        title="Copiar"
        aria-label="Copiar {{ Str::lower($label) }}">
        <i class="fa-solid fa-copy" aria-hidden="true"></i>
    </button>
@endif
