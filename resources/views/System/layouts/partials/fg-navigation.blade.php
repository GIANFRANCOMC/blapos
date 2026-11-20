<aside id="fg-layout-menu" class="fg-navigation {{ $showNavigationContext ? 'has-context' : 'without-context' }}" aria-label="Navegación principal">
    <div class="fg-rail">
        <div class="fg-rail__brand"><img src="{{ $navigationLogomarkUrl }}" alt="Isotipo de {{ $navbarCompanyName }}" width="32" height="32"></div>
        <nav id="brNavigationWorlds" class="fg-worlds" aria-label="Módulos disponibles">
            @foreach($navigationSections as $section)
                @php
                    $isActiveSection = $activeNavigationSection?->id === $section->id;
                    $singlePage = $section->subSections->count() === 1 ? $section->subSections->first() : null;
                @endphp
                @if($singlePage)
                    <a href="{{ $singlePage->dom_route_url }}" id="{{ $section->dom_id }}"
                       class="fg-world {{ $isActiveSection ? 'is-active' : '' }} {{ $section->dom_id }}"
                       data-navigation-section="{{ $section->id }}" data-navigation-count="1"
                       aria-current="{{ $isActiveSection ? 'page' : 'false' }}" aria-label="{{ $section->dom_label }}"
                       data-bs-toggle="tooltip" data-bs-placement="right" data-bs-custom-class="fg-tooltip" title="{{ $section->dom_label }}">
                        <i class="{{ $section->dom_icon }}" aria-hidden="true"></i>
                    </a>
                @else
                    <button type="button" id="{{ $section->dom_id }}"
                            class="fg-world {{ $isActiveSection ? 'is-active' : '' }} {{ $section->dom_id }}"
                            data-navigation-section="{{ $section->id }}" data-navigation-count="{{ $section->subSections->count() }}"
                            aria-controls="{{ $isActiveSection ? 'brNavigationContext' : 'brNavigationContext-'.$section->id }}"
                            aria-pressed="{{ $isActiveSection ? 'true' : 'false' }}" aria-label="{{ $section->dom_label }}"
                            data-bs-toggle="tooltip" data-bs-placement="right" data-bs-custom-class="fg-tooltip" title="{{ $section->dom_label }}">
                        <i class="{{ $section->dom_icon }}" aria-hidden="true"></i>
                    </button>
                @endif
            @endforeach
        </nav>
        <button type="button" class="fg-world fg-world--logout" aria-label="Cerrar sesión" title="Cerrar sesión"
                data-bs-toggle="tooltip" data-bs-placement="right" data-bs-custom-class="fg-tooltip fg-tooltip--danger">
            <i class="fa-solid fa-power-off" aria-hidden="true"></i>
        </button>
    </div>
    @foreach($navigationSections as $section)
        @php $isActiveSection = $activeNavigationSection?->id === $section->id; @endphp
        <div class="fg-context" id="{{ $isActiveSection ? 'brNavigationContext' : 'brNavigationContext-'.$section->id }}"
             data-navigation-panel="{{ $section->id }}" @if(!$isActiveSection || !$showNavigationContext) hidden @endif>
            <header class="fg-context__header"><h2>{{ $section->dom_label }}</h2><button type="button" class="fg-context__close fg-button fg-button--icon" data-fg-menu-close aria-label="Cerrar menú"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button></header>
            <nav class="fg-context__body" aria-label="Opciones de {{ $section->dom_label }}">
                @foreach(\App\Support\System\NavigationMenuGroups::ordered($section->subSections) as $menuGroupItems)
                    @php $menuGroup = $menuGroupItems->first()->menuGroup; @endphp
                    <section class="fg-context__group" aria-label="{{ $menuGroup?->name ?? $section->dom_label }}">
                        @if($menuGroup)<h3 class="fg-context__group-title">{{ $menuGroup->name }}</h3>@endif
                        <ul class="fg-context__pages">
                            @foreach($menuGroupItems as $subSection)
                                @php $isActivePage = $isActiveSection && $activeNavigationSubSection?->id === $subSection->id; @endphp
                                <li><a href="{{ $subSection->dom_route_url }}" id="{{ $subSection->dom_id }}"
                                       class="fg-context__page {{ $subSection->dom_id }} {{ $isActivePage ? 'active' : '' }}"
                                       data-navigation-section="{{ $section->id }}" aria-current="{{ $isActivePage ? 'page' : 'false' }}">{{ $subSection->dom_label }}</a></li>
                            @endforeach
                        </ul>
                    </section>
                @endforeach
            </nav>
        </div>
    @endforeach
    <form method="POST" action="{{ route('logout') }}" id="logout" hidden>@csrf</form>
</aside>
<button type="button" class="fg-nav-backdrop" data-fg-menu-close aria-label="Cerrar menú" hidden></button>
