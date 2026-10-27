@php($displayValue = trim((string) ($value ?? "")))
<div class="br-workspace__company-detail{{ ($full ?? false) ? " br-workspace__company-detail--full" : " br-workspace__company-detail--compact" }}{{ ($divider ?? false) ? " br-workspace__company-detail--divider" : "" }}{{ isset($tone) ? " br-workspace__company-detail--".$tone : "" }}">
    <span class="br-workspace__company-detail-icon" aria-hidden="true"><i class="{{ $icon }}"></i></span>
    <div class="br-workspace__company-detail-content">
        <dt class="colon-at-end">{{ $label }}</dt>
        <dd class="br-workspace__company-value{{ $displayValue === "" ? " is-empty" : "" }}">
            <span class="br-workspace__company-text">{{ $displayValue !== "" ? $displayValue : "Sin información" }}</span>
            @if(($copyable ?? false) && $displayValue !== "")
                @include("System.components.copy-button", ["value" => $displayValue, "label" => $label])
            @endif
        </dd>
    </div>
</div>
