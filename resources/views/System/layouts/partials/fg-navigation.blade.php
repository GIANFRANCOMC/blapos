<aside id="fg-layout-menu" class="fg-navigation {{ $showNavigationContext ? 'has-context' : 'without-context' }}" aria-label="Navegación principal">
    <div class="fg-rail">
        <nav id="brNavigationWorlds" class="fg-worlds" aria-label="Módulos disponibles">
            <div class="fg-worlds__list">
                @foreach($worldNavigationSections as $section)
                    @include("System.layouts.partials.fg-navigation-world")
                @endforeach
            </div>
        </nav>
    </div>
    <div class="fg-context" id="fg-navigation-context" @if(!$showNavigationContext) inert aria-hidden="true" @endif>
        @foreach($worldNavigationSections->filter($hasNavigationContext) as $section)
            @php
                $isActiveSection = $activeNavigationSection?->id === $section->id;
                $worldLabel = $navigationWorldLabel($section);
            @endphp
            <div class="fg-context__panel" id="{{ $isActiveSection ? 'brNavigationContext' : 'brNavigationContext-'.$section->id }}"
                 data-navigation-panel="{{ $section->id }}" @if(!$isActiveSection || !$showNavigationContext) hidden @endif>
                <header class="fg-context__header"><h2>{{ $worldLabel }}</h2><button type="button" class="fg-context__close fg-button fg-button--icon" data-fg-menu-close aria-label="Cerrar menú lateral"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button></header>
                <nav class="fg-context__body" aria-label="Opciones de {{ $worldLabel }}">
                    @foreach(\App\Support\System\NavigationMenuGroups::ordered($section->subSections->where("dom_route", "!=", "home.index")) as $menuGroupItems)
                        @php $menuGroup = $menuGroupItems->first()->menuGroup; @endphp
                        <section class="fg-context__group" aria-label="{{ $menuGroup?->name ?? $worldLabel }}">
                            @if($menuGroup)<h3 class="fg-context__group-title">{{ $menuGroup->name }}</h3>@endif
                            <ul class="fg-context__pages">
                                @foreach($menuGroupItems as $subSection)
                                    @php $isActivePage = $isActiveSection && $activeNavigationSubSection?->id === $subSection->id; @endphp
                                    <li>
                                        <a href="{{ $subSection->dom_route_url }}" id="{{ $subSection->dom_id }}"
                                           class="fg-context__page {{ $subSection->dom_id }} {{ $isActivePage ? 'active' : '' }}"
                                           data-navigation-section="{{ $section->id }}" aria-current="{{ $isActivePage ? 'page' : 'false' }}">
                                            <i class="fg-context__page-icon {{ $subSection->dom_icon ?: 'fa-regular fa-file' }}" aria-hidden="true"></i>
                                            <span>{{ $subSection->dom_label }}</span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </section>
                    @endforeach
                </nav>
            </div>
        @endforeach
    </div>
</aside>
<button type="button" class="fg-nav-backdrop" data-fg-menu-close aria-label="Cerrar menú lateral" aria-hidden="true" tabindex="-1" inert></button>
