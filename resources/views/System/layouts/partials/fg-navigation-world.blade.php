@php
    $isActiveSection = $activeNavigationSection?->id === $section->id;
    $singlePage = $hasNavigationContext($section) ? null : $section->subSections->first();
    $isWorkspace = $section->slug === "sc_workspace";
    $worldLabel = $navigationWorldLabel($section);
    $worldIcon = $isWorkspace ? "fa-solid fa-folder-open" : $section->dom_icon;
@endphp
@if($singlePage)
    <a href="{{ $singlePage->dom_route_url }}" id="{{ $section->dom_id }}"
       class="fg-world {{ $isActiveSection ? 'is-active' : '' }} {{ $section->dom_id }}"
       data-navigation-section="{{ $section->id }}"
       aria-current="{{ $isActiveSection ? 'page' : 'false' }}" aria-label="{{ $worldLabel }}"
       data-fg-desktop-tooltip data-bs-toggle="tooltip" data-bs-placement="right" data-bs-custom-class="fg-tooltip">
        <i class="{{ $worldIcon }}" aria-hidden="true"></i>
    </a>
@else
    <button type="button" id="{{ $section->dom_id }}"
            class="fg-world {{ $isActiveSection ? 'is-active' : '' }} {{ $section->dom_id }}"
            data-navigation-section="{{ $section->id }}"
            aria-controls="{{ $isActiveSection ? 'brNavigationContext' : 'brNavigationContext-'.$section->id }}"
            aria-expanded="{{ $isActiveSection && $showNavigationContext ? 'true' : 'false' }}" aria-label="{{ $worldLabel }}"
            data-fg-desktop-tooltip data-bs-toggle="tooltip" data-bs-placement="right" data-bs-custom-class="fg-tooltip">
        <i class="{{ $worldIcon }}" aria-hidden="true"></i>
    </button>
@endif
