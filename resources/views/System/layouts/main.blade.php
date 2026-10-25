<!DOCTYPE html>

@php
    $ownerApp = config("app.owner_app");
    $user     = Auth::user();
    $company  = app(\App\Services\System\Tenancy\TenantCompanyContext::class)->get();
    $role     = $user->role;
    $systemAssetsPath = rtrim(asset('System/assets'), '/').'/';
    $sections = \App\Services\System\Organizations\Companies\CompanySectionService::getSections($role?->id);
    $user->load("preferences");
    $preferences = $user->formatted_preferences;
    $userInitials = collect(preg_split('/\s+/', trim($user->name)))
                        ->filter()
                        ->take(2)
                        ->map(fn($part) => Str::upper(Str::substr($part, 0, 1)))
                        ->join('') ?: 'U';

    $subSectionIds = $sections->pluck("subSections")->flatten()->pluck("id")->toArray();
    $valuePreferences = $preferences["config_companies_sub_sections"]->sub_sections ?? [];
    $configuredSubSectionIds = collect($valuePreferences)->pluck("sub_section_id")->map(fn($id) => (int) $id)->toArray();
    $visibleSubSectionIds = collect($valuePreferences)
        ->filter(fn($preference) => data_get($preference, "visible_in_menu", true))
        ->pluck("sub_section_id")
        ->map(fn($id) => (int) $id)
        ->unique()
        ->values()
        ->toArray();
    $unconfiguredSubSectionIds = collect($subSectionIds)
        ->reject(fn($id) => in_array($id, $configuredSubSectionIds, true))
        ->toArray();
    $sectionFiltered = count($valuePreferences) > 0
        ? array_merge($unconfiguredSubSectionIds, $visibleSubSectionIds)
        : $subSectionIds;

    $navigationSections = $sections
        ->map(function($section) use ($sectionFiltered) {

            if($section->slug === "sc_workspace") $section->dom_icon = "fa fa-home";

            $section->setRelation(
                "subSections",
                $section->subSections->whereIn("id", $sectionFiltered)->values()
            );

            return $section;

        })
        ->filter(fn($section) => $section->subSections->isNotEmpty())
        ->values();

    $activeNavigationSubSection = $navigationSections
        ->pluck("subSections")
        ->flatten()
        ->first(function($subSection) {

            if(request()->routeIs($subSection->dom_route)) {

                return true;

            }

            $route = Illuminate\Support\Facades\Route::getRoutes()->getByName($subSection->dom_route);

            return $route && request()->is(ltrim($route->uri(), "/")."/*");

        });

    $activeNavigationSection = $activeNavigationSubSection
        ? $navigationSections->firstWhere("id", $activeNavigationSubSection->section_id)
        : $navigationSections->first();
    $showNavigationContext = ($activeNavigationSection?->subSections->count() ?? 0) > 1;
    $favoritesEnabled = \App\Services\System\General\AutomaticMasterDataService::enabled(
        \App\Services\System\General\AutomaticMasterDataService::MENU_FAVORITES
    ) && $sections->pluck("subSections")->flatten()->contains("dom_route", "home.index");

    $favoritePreferences = collect($valuePreferences)
        ->filter(fn($preference) => data_get($preference, "is_favorite", false))
        ->pluck("sub_section_id")
        ->map(fn($id) => (int) $id)
        ->unique()
        ->values()
        ->toArray();
    $favoriteMenuGroups = collect();

    foreach($favoritesEnabled ? $navigationSections : [] as $section) {

        $favoriteSubSections = $section->subSections->whereIn("id", $favoritePreferences);

        if($favoriteSubSections->isEmpty()) {

            continue;

        }

        $favoriteMenuGroups->push([
            "section" => $section->dom_label,
            "icon" => $section->dom_icon,
            "items" => $favoriteSubSections->map(fn($subSection) => [
                "label" => $subSection->dom_label,
                "description" => $subSection->description,
                "url" => $subSection->dom_route_url,
            ])->values(),
        ]);

    }
@endphp

<html
    lang="en"
    class="light-style layout-navbar-fixed layout-menu-fixed layout-compact br-html-brand br-navigation-ready {{ $showNavigationContext ? 'br-navigation-with-context' : 'br-navigation-without-context' }}"
    dir="ltr"
    data-theme="theme-default"
    data-assets-path="{{ $systemAssetsPath }}"
    data-navigation-section-id="{{ $activeNavigationSection?->id }}"
    data-navigation-world="{{ $activeNavigationSection?->dom_label }}"
    data-navigation-group="{{ $activeNavigationSubSection?->menuGroup?->name }}"
    data-navigation-page="{{ $activeNavigationSubSection?->dom_label }}"
    data-template="vertical-menu-template-starter">
    <head>
        @include("System.layouts.partials.up")
        <script>
            window.ownerApp = @json($ownerApp ?? null);
            window.user     = @json($user ?? null);
            window.company  = @json($company ?? null);
            window.sections = @json($sections ?? []);
            window.preferences = @json($preferences ?? []);
        </script>
        @vite("resources/js/System/Layout/main.js")
    </head>
    <body class="br-layout">
        <div class="layout-wrapper layout-content-navbar">
            <div class="layout-container">
                <aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme br-menu-brand br-navigation {{ $showNavigationContext ? 'has-context' : 'without-context' }}" aria-label="Navegación principal">
                    <div class="br-navigation__rail">
                        <nav class="br-navigation__modules" aria-label="Módulos disponibles">
                            @foreach($navigationSections as $section)
                                @php
                                    $isActiveSection = $activeNavigationSection?->id === $section->id;
                                    $singlePage = $section->subSections->count() === 1
                                        ? $section->subSections->first()
                                        : null;
                                @endphp
                                @if($singlePage)
                                <a
                                    href="{{ $singlePage->dom_route_url }}"
                                    id="{{ $section->dom_id }}"
                                    class="br-navigation__module {{ $isActiveSection ? 'is-active' : '' }} {{ $section->dom_id }}"
                                    data-navigation-section="{{ $section->id }}"
                                    data-navigation-count="1"
                                    aria-current="{{ $isActiveSection ? 'page' : 'false' }}"
                                    aria-label="{{ $section->dom_label }}"
                                    data-bs-toggle="tooltip"
                                    data-bs-placement="right"
                                    data-bs-custom-class="br-navigation-tooltip"
                                    title="{{ $section->dom_label }}">
                                    <i class="{{ $section->dom_icon }}" aria-hidden="true"></i>
                                </a>
                                @else
                                <button
                                    type="button"
                                    id="{{ $section->dom_id }}"
                                    class="br-navigation__module {{ $isActiveSection ? 'is-active' : '' }} {{ $section->dom_id }}"
                                    data-navigation-section="{{ $section->id }}"
                                    data-navigation-count="{{ $section->subSections->count() }}"
                                    aria-controls="{{ $isActiveSection ? 'brNavigationContext' : 'brNavigationContext-'.$section->id }}"
                                    aria-pressed="{{ $isActiveSection ? 'true' : 'false' }}"
                                    aria-label="{{ $section->dom_label }}"
                                    data-bs-toggle="tooltip"
                                    data-bs-placement="right"
                                    data-bs-custom-class="br-navigation-tooltip"
                                    title="{{ $section->dom_label }}">
                                    <i class="{{ $section->dom_icon }}" aria-hidden="true"></i>
                                </button>
                                @endif
                            @endforeach
                        </nav>

                        <div class="br-navigation__session">
                            <button
                                type="button"
                                class="br-navigation__logout"
                                data-bs-toggle="tooltip"
                                data-bs-placement="right"
                                data-bs-custom-class="br-navigation-logout-tooltip"
                                aria-label="Cerrar sesión"
                                title="Cerrar sesión">
                                <i class="fa-solid fa-power-off" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>

                    @foreach($navigationSections as $section)
                    @php
                        $isActiveSection = $activeNavigationSection?->id === $section->id;
                    @endphp
                    <div
                        class="br-navigation__context"
                        id="{{ $isActiveSection ? 'brNavigationContext' : 'brNavigationContext-'.$section->id }}"
                        data-navigation-panel="{{ $section->id }}"
                        @if(!$isActiveSection || !$showNavigationContext) hidden @endif>
                        <div class="br-navigation__context-head">
                            <div class="br-navigation__world-card">
                                <span class="br-navigation__world-icon" aria-hidden="true">
                                    <i class="{{ $section->dom_icon }}"></i>
                                </span>
                                <h2 class="br-navigation__world-title">{{ $section->dom_label }}</h2>
                            </div>
                        </div>

                        <nav class="br-navigation__context-body" aria-label="Opciones de {{ $section->dom_label }}">
                            @foreach($section->subSections->groupBy(fn($subSection) => $subSection->menuGroup?->id ?? 0) as $menuGroupItems)
                                @php
                                    $menuGroup = $menuGroupItems->first()->menuGroup;
                                @endphp
                                <section class="br-navigation__group" aria-label="{{ $menuGroup?->name ?? $section->dom_label }}">
                                    @if($menuGroup)
                                        <h3 class="br-navigation__group-title">{{ $menuGroup->name }}</h3>
                                    @endif
                                    <ul class="br-navigation__pages">
                                        @foreach($menuGroupItems as $subSection)
                                            @php
                                                $isActivePage = $isActiveSection && $activeNavigationSubSection?->id === $subSection->id;
                                            @endphp
                                            <li>
                                                <a
                                                    href="{{ $subSection->dom_route_url }}"
                                                    id="{{ $subSection->dom_id }}"
                                                    class="br-navigation__page {{ $subSection->dom_id }} {{ $isActivePage ? 'active' : '' }}"
                                                    data-navigation-section="{{ $section->id }}"
                                                    aria-current="{{ $isActivePage ? 'page' : 'false' }}">
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

                    <form method="POST" action="{{ route('logout') }}" id="logout" class="d-none">
                        @csrf
                    </form>
                </aside>
                <div class="layout-page br-layout-page">
                    <nav class="layout-navbar navbar navbar-expand-xl navbar-detached align-items-center bg-navbar-theme br-layout-navbar" id="layout-navbar">
                        <div class="br-navbar-shell" id="navbar-collapse">
                            <div class="br-navbar-left">
                                <div class="layout-menu-toggle navbar-nav align-items-center br-navbar-menu-toggle">
                                    <a class="nav-item nav-link br-navbar-icon-btn" href="javascript:void(0)" aria-label="Alternar menú lateral" title="Alternar menú lateral">
                                        <i class="ti ti-menu-2" aria-hidden="true"></i>
                                    </a>
                                </div>
                            {{-- <div class="navbar-nav align-items-center">
                                <div class="nav-item navbar-search-wrapper mb-0">
                                    <a class="nav-item nav-link search-toggler d-flex align-items-center px-0 br-navbar-brand-link" href="{{ $ownerApp->web }}" target="_blank" rel="noopener noreferrer">
                                        <img src="{{ asset($ownerApp->assets->img->logotype) }}" class="d-none d-md-block" width="80"/>
                                        <img src="{{ asset($ownerApp->assets->img->logomark) }}" class="d-block d-md-none" width="50"/>
                                    </a>
                                </div>
                            </div> --}}
                            </div>

                            <div class="br-navbar-actions">
                                @if($favoritesEnabled)
                                    <div class="br-fab-favorites br-navbar-favorites" id="brFabFavorites" data-open="0" data-home-url="{{ route('home.index') }}">
                                    <div class="br-fab-favorites__backdrop" id="brFabFavoritesBackdrop" aria-hidden="true"></div>
                                    <div class="br-fab-favorites__panel" id="brFabFavoritesPanel" role="region" aria-labelledby="brFabFavoritesTitle" aria-hidden="true">
                                        <div class="br-fab-favorites__head">
                                            <span id="brFabFavoritesTitle" class="br-fab-favorites__title">Favoritos</span>
                                            <button type="button" class="br-fab-favorites__close" id="brFabFavoritesClose" aria-label="Cerrar favoritos" title="Cerrar favoritos">
                                                <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                                            </button>
                                        </div>
                                        <div class="br-fab-favorites__body" id="brFabFavoritesBody">
                                            @if($favoriteMenuGroups->isEmpty())
                                                <p class="br-fab-favorites__empty mb-0" data-favorites-empty>
                                                    Aún no tienes favoritos. Puedes configurarlos desde
                                                    <a href="{{ route('home.index') }}" class="fw-semibold">Favoritos</a>.
                                                </p>
                                            @else
                                                <ul class="br-fab-favorites__list list-unstyled mb-0" data-favorites-list>
                                                    @foreach($favoriteMenuGroups as $group)
                                                        <li class="br-fab-favorites__group">
                                                            <div class="br-fab-favorites__group-head">
                                                                <i class="{{ $group['icon'] }} br-fab-favorites__group-icon" aria-hidden="true"></i>
                                                                <span>{{ $group['section'] }}</span>
                                                            </div>
                                                            <ul class="br-fab-favorites__group-items list-unstyled mb-0">
                                                                @foreach($group['items'] as $favoriteItem)
                                                                    <li class="br-fab-favorites__item">
                                                                        <a href="{{ $favoriteItem['url'] }}" class="br-fab-favorites__link">
                                                                            <span class="br-fab-favorites__item-content">
                                                                                <span class="br-fab-favorites__item-label">{{ $favoriteItem['label'] }}</span>
                                                                                @if($favoriteItem['description'])
                                                                                    <span class="br-fab-favorites__item-description">{{ $favoriteItem['description'] }}</span>
                                                                                @endif
                                                                            </span>
                                                                            <i class="fa-solid fa-arrow-right br-fab-favorites__item-arrow" aria-hidden="true"></i>
                                                                        </a>
                                                                    </li>
                                                                @endforeach
                                                            </ul>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            @endif
                                        </div>
                                    </div>
                                    <button
                                        type="button"
                                        class="br-fab-favorites__btn br-navbar-favorites__btn"
                                        id="brFabFavoritesToggle"
                                        aria-label="Abrir favoritos"
                                        aria-expanded="false"
                                        aria-controls="brFabFavoritesPanel">
                                        <i class="fa-solid fa-star fa-2xs" aria-hidden="true"></i>
                                        <span class="br-navbar-favorites__label">Favoritos</span>
                                        <span class="br-navbar-favorites__count" id="brFabFavoritesCount">{{ $favoriteMenuGroups->sum(fn($group) => $group['items']->count()) }}</span>
                                    </button>
                                    </div>
                                @endif
                                <div class="dropdown br-navbar-announcements">
                                    <button
                                        type="button"
                                        class="br-navbar-announcements__toggle"
                                        data-bs-toggle="dropdown"
                                        data-bs-auto-close="outside"
                                        aria-expanded="false"
                                        aria-label="Abrir anuncios">
                                        <i class="fa-regular fa-bell" aria-hidden="true"></i>
                                        <span class="br-navbar-announcements__label">Anuncios</span>
                                        <span class="br-navbar-announcements__count">{{ ($tenantAnnouncements ?? collect())->count() }}</span>
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-end br-navbar-announcements__menu">
                                        <div class="br-navbar-announcements__head">
                                            <div>
                                                <strong>Anuncios</strong>
                                                <span>Comunicaciones de la plataforma</span>
                                            </div>
                                            <i class="fa-regular fa-bell" aria-hidden="true"></i>
                                        </div>
                                        <div class="br-navbar-announcements__body">
                                            @forelse(($tenantAnnouncements ?? collect()) as $announcement)
                                                <article class="br-navbar-announcement br-navbar-announcement--{{ $announcement->severity }}">
                                                    <span class="br-navbar-announcement__icon" aria-hidden="true">
                                                        <i class="fa-solid fa-circle-info"></i>
                                                    </span>
                                                    <div>
                                                        <strong>{{ $announcement->title }}</strong>
                                                        <p>{{ $announcement->message }}</p>
                                                    </div>
                                                </article>
                                            @empty
                                                <div class="br-navbar-announcements__empty">
                                                    <i class="fa-regular fa-bell-slash" aria-hidden="true"></i>
                                                    <span>No hay anuncios vigentes.</span>
                                                </div>
                                            @endforelse
                                        </div>
                                    </div>
                                </div>
                                <div class="dropdown br-navbar-user">
                                    <button
                                        type="button"
                                        class="br-navbar-user__toggle"
                                        data-bs-toggle="dropdown"
                                        data-bs-auto-close="outside"
                                        aria-expanded="false"
                                        aria-label="Abrir menú de usuario">
                                        <span class="br-navbar-user__avatar" aria-hidden="true">{{ $userInitials }}</span>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end br-navbar-user__menu">
                                        <li class="br-navbar-user__summary">
                                            <strong>{{ $user->name }}</strong>
                                            <span>{{ $company->commercial_name }}</span>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <a href="{{ route("account.index") }}" class="dropdown-item br-navbar-user__profile">
                                                <i class="fa-regular fa-user" aria-hidden="true"></i>
                                                <span>Mi perfil</span>
                                            </a>
                                        </li>
                                        <li>
                                            <button type="button" class="dropdown-item br-navbar-user__logout">
                                                <i class="fa-solid fa-power-off" aria-hidden="true"></i>
                                                <span>Cerrar sesión</span>
                                            </button>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </nav>
                    <div class="content-wrapper br-layout-content">
                        <div id="brNavigationContent" class="container-xxl flex-grow-1 container-p-y">
                            @yield('content')
                        </div>
                        <div class="content-backdrop fade"></div>
                    </div>
                </div>
            </div>
            <div class="layout-overlay layout-menu-toggle"></div>
            {{-- <div class="drag-target"></div> --}}
        </div>

        @include("System.layouts.partials.down")
    </body>
</html>
