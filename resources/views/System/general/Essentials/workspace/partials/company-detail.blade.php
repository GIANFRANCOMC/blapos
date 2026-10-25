@php($displayValue = trim((string) ($value ?? "")))
<div class="br-workspace__company-detail{{ ($full ?? false) ? " br-workspace__company-detail--full" : "" }}">
    <dt class="colon-at-end">{{ $label }}</dt>
    <dd class="br-workspace__company-value{{ $displayValue === "" ? " is-empty" : "" }}">
        <span class="br-workspace__company-text">{{ $displayValue !== "" ? $displayValue : "Sin información" }}</span>
        @if(($copyable ?? false) && $displayValue !== "")
            @include("System.components.copy-button", ["value" => $displayValue, "label" => $label])
        @endif
    </dd>
</div>
