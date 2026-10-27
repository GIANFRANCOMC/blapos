<a href="{{ $item["url"] }}" class="br-workspace__link">
    <span class="br-workspace__link-icon" aria-hidden="true"><i class="{{ $item["icon"] }}"></i></span>
    <span class="br-workspace__link-copy">
        <strong>{{ $item["label"] }}</strong>
        @if($item["description"])
            <small>{{ $item["description"] }}</small>
        @endif
    </span>
    <span class="br-workspace__link-arrow" aria-hidden="true"><i class="fa-solid fa-chevron-right"></i></span>
</a>
