@php($displayValue = trim((string) ($value ?? "")))
<div class="br-workspace__company-detail{{ ($full ?? false) ? " br-workspace__company-detail--full" : "" }}">
    <dt>{{ $label }}</dt>
    <dd class="br-workspace__company-value{{ $displayValue === "" ? " is-empty" : "" }}">
        <span class="br-workspace__company-text">{{ $displayValue !== "" ? $displayValue : "Sin información" }}</span>
        @if(($copyable ?? false) && $displayValue !== "")
            <button
                type="button"
                class="br-copy-button"
                data-br-copy-value="{{ $displayValue }}"
                data-bs-toggle="tooltip"
                data-bs-placement="top"
                title="Copiar"
                aria-label="Copiar {{ Str::lower($label) }}">
                <i class="fa-solid fa-copy" aria-hidden="true"></i>
            </button>
        @endif
    </dd>
</div>
