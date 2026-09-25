<!DOCTYPE html>

@php
    $ownerApp = config("app.owner_app");
    $user     = Auth::user();
    $company  = app(\App\Services\System\Tenancy\TenantCompanyContext::class)->get();
    $role     = $user->role;
    $systemAssetsPath = rtrim(asset('System/assets'), '/').'/';
    $sections = \App\Services\System\Organizations\Companies\CompanySectionService::getSections($company->id, $role?->id);
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

    $favoritePreferences = collect($valuePreferences)
        ->filter(fn($preference) => data_get($preference, "is_favorite", false))
        ->pluck("sub_section_id")
        ->map(fn($id) => (int) $id)
        ->unique()
        ->values()
        ->toArray();
    $favoriteMenuGroups = collect();

    foreach($navigationSections as $section) {

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
    </head>
    <body class="br-layout">
        <div class="layout-wrapper layout-content-navbar">
            <div class="layout-container">
                <aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme br-menu-brand br-navigation {{ $showNavigationContext ? 'has-context' : 'without-context' }}" aria-label="Navegación principal">
                    <div class="br-navigation__rail">
                        <a
                            href="{{ $navigationSections->first()?->subSections->first()?->dom_route_url ?? '#' }}"
                            class="br-navigation__brand"
                            aria-label="Ir al inicio"
                            title="{{ $company->commercial_name }}">
                            <span aria-hidden="true">{{ Str::upper(Str::substr($company->commercial_name, 0, 1)) }}</span>
                        </a>

                        <nav class="br-navigation__modules" aria-label="Módulos disponibles">
                            @foreach($navigationSections as $section)
                                @php
                                    $isActiveSection = $activeNavigationSection?->id === $section->id;
                                    $reference = $section->subSections->first();
                                @endphp
                                <a
                                    href="{{ $reference->dom_route_url }}"
                                    id="{{ $section->dom_id }}"
                                    class="br-navigation__module {{ $isActiveSection ? 'is-active' : '' }} {{ $section->dom_id }}"
                                    data-navigation-section="{{ $section->id }}"
                                    aria-label="{{ $section->dom_label }}"
                                    aria-current="{{ $isActiveSection ? 'true' : 'false' }}"
                                    data-bs-toggle="tooltip"
                                    data-bs-placement="right"
                                    data-bs-custom-class="br-navigation-tooltip"
                                    title="{{ $section->dom_label }}">
                                    <i class="{{ $section->dom_icon }}" aria-hidden="true"></i>
                                </a>
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
                                title="Cerrar sesión"
                                onclick="confirmLogout();">
                                <i class="fa-solid fa-power-off" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>

                    @if($showNavigationContext)
                    <div class="br-navigation__context" id="brNavigationContext">
                        <div class="br-navigation__context-head">
                            <h2 class="br-navigation__world-title">{{ $activeNavigationSection?->dom_label }}</h2>
                        </div>

                        <nav class="br-navigation__context-body" aria-label="Opciones de {{ $activeNavigationSection?->dom_label }}">
                            @foreach($activeNavigationSection?->subSections->groupBy(fn($subSection) => $subSection->menuGroup?->id ?? 0) ?? [] as $menuGroupItems)
                                @php
                                    $menuGroup = $menuGroupItems->first()->menuGroup;
                                    $menuGroupIsActive = $activeNavigationSubSection
                                        && $menuGroupItems->contains("id", $activeNavigationSubSection->id);
                                @endphp
                                <section class="br-navigation__group {{ $menuGroupIsActive ? 'is-current' : '' }}">
                                    @if($menuGroup)
                                        <h3 class="br-navigation__group-title">{{ $menuGroup->name }}</h3>
                                    @endif
                                    <ul class="br-navigation__pages">
                                        @foreach($menuGroupItems as $subSection)
                                            @php
                                                $isActivePage = $activeNavigationSubSection?->id === $subSection->id;
                                            @endphp
                                            <li>
                                                <a
                                                    href="{{ $subSection->dom_route_url }}"
                                                    id="{{ $subSection->dom_id }}"
                                                    class="br-navigation__page {{ $subSection->dom_id }} {{ $isActivePage ? 'active' : '' }}"
                                                    data-navigation-section="{{ $activeNavigationSection->id }}"
                                                    aria-current="{{ $isActivePage ? 'page' : 'false' }}">
                                                    <span>{{ $subSection->dom_label }}</span>
                                                    @if($isActivePage)
                                                        <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                                                    @endif
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                </section>
                            @endforeach
                        </nav>
                    </div>
                    @endif

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
                            {{-- <ul class="navbar-nav flex-row align-items-center ms-auto">
                                <li class="nav-item">
                                    <a class="nav-link px-0" href="javascript:void(0);" onclick='generateMyUrl(@json($company), true, "my_web")'>
                                        <span class="br-btn br-btn-primary br-btn-sm rounded-pill">
                                            <i class="fa fa-globe"></i>
                                            <span class="ms-2">Visitar mi página</span>
                                        </span>
                                    </a>
                                </li>
                            </ul> --}}
                            </div>

                            <div class="br-navbar-actions">
                                <div class="br-fab-favorites br-navbar-favorites" id="brFabFavorites" data-open="0">
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
                                            <button type="button" class="dropdown-item br-navbar-user__logout" onclick="confirmLogout();">
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
                        <div class="container-xxl flex-grow-1 container-p-y">
                            @yield('content')
                        </div>
                        <div class="content-backdrop fade"></div>
                    </div>
                </div>
            </div>
            <div class="layout-overlay layout-menu-toggle"></div>
            {{-- <div class="drag-target"></div> --}}
        </div>

        <script>
            window.confirmLogout = function () {

                var submitLogout = function () {

                    document.getElementById("logout")?.requestSubmit();

                };

                if (!window.Swal) {

                    if (window.confirm("¿Deseas cerrar sesión?")) submitLogout();

                    return;

                }

                window.Swal.fire({
                    title: "¿Deseas cerrar sesión?",
                    text: "Tendrás que volver a ingresar tus credenciales para continuar.",
                    icon: "question",
                    showCancelButton: true,
                    confirmButtonText: "Sí, cerrar sesión",
                    cancelButtonText: "Cancelar",
                    buttonsStyling: false,
                    customClass: {
                        popup: "br-swal",
                        confirmButton: "br-btn br-btn-danger ms-2",
                        cancelButton: "br-btn br-btn-cancel"
                    },
                    reverseButtons: true
                }).then(function (result) {

                    if (result.isConfirmed) submitLogout();

                });

            };
        </script>

        <script>
            (function () {

                var root = document.getElementById("brFabFavorites");

                if (!root) return;

                var btn = document.getElementById("brFabFavoritesToggle");
                var panel = document.getElementById("brFabFavoritesPanel");
                var closeBtn = document.getElementById("brFabFavoritesClose");
                var backdrop = document.getElementById("brFabFavoritesBackdrop");
                var body = document.getElementById("brFabFavoritesBody");
                var count = document.getElementById("brFabFavoritesCount");
                var homeUrl = @json(route("home.index"));

                function setOpen(open) {

                    root.setAttribute("data-open", open ? "1" : "0");
                    panel.classList.toggle("br-fab-favorites__panel--open", open);
                    panel.setAttribute("aria-hidden", open ? "false" : "true");
                    btn.setAttribute("aria-expanded", open ? "true" : "false");
                    btn.setAttribute("aria-label", open ? "Cerrar favoritos" : "Abrir favoritos");

                }

                function getFavoriteItems(preferences) {

                    var preferenceConfig = preferences?.config_companies_sub_sections;
                    var favoriteIds = new Set(
                        (preferenceConfig?.sub_sections || [])
                            .filter(function (preference) { return preference?.is_favorite; })
                            .map(function (preference) { return Number(preference.sub_section_id); })
                    );
                    var groups = [];

                    (window.sections || []).forEach(function (section) {

                        var subSections = section?.sub_sections || section?.subSections || [];
                        var items = [];

                        subSections.forEach(function (subSection) {

                            if (!favoriteIds.has(Number(subSection.id))) return;

                            items.push({
                                label: subSection.dom_label,
                                description: subSection.description || "",
                                url: subSection.dom_route_url,
                            });

                        });

                        if(items.length > 0) {

                            groups.push({
                                section: section.dom_label,
                                icon: section.dom_icon,
                                items: items
                            });

                        }

                    });

                    return groups;

                }

                function renderFavoriteItems(preferences) {

                    var groups = getFavoriteItems(preferences);
                    var totalItems = groups.reduce(function (total, group) {
                        return total + group.items.length;
                    }, 0);

                    count.textContent = String(totalItems);
                    body.replaceChildren();

                    if (totalItems === 0) {

                        var empty = document.createElement("p");
                        var link = document.createElement("a");

                        empty.className = "br-fab-favorites__empty mb-0";
                        empty.append("Aún no tienes favoritos. Puedes configurarlos desde ");
                        link.href = homeUrl;
                        link.className = "fw-semibold";
                        link.textContent = "Favoritos";
                        empty.append(link, ".");
                        body.appendChild(empty);

                        return;

                    }

                    var list = document.createElement("ul");
                    list.className = "br-fab-favorites__list list-unstyled mb-0";

                    groups.forEach(function (group) {

                        var groupItem = document.createElement("li");
                        var groupHead = document.createElement("div");
                        var groupIcon = document.createElement("i");
                        var groupTitle = document.createElement("span");
                        var groupList = document.createElement("ul");

                        groupItem.className = "br-fab-favorites__group";
                        groupHead.className = "br-fab-favorites__group-head";
                        groupIcon.className = (group.icon || "") + " br-fab-favorites__group-icon";
                        groupIcon.setAttribute("aria-hidden", "true");
                        groupTitle.textContent = group.section;
                        groupList.className = "br-fab-favorites__group-items list-unstyled mb-0";
                        groupHead.append(groupIcon, groupTitle);

                        group.items.forEach(function (item) {

                            var listItem = document.createElement("li");
                            var link = document.createElement("a");
                            var content = document.createElement("span");
                            var label = document.createElement("span");
                            var description = document.createElement("span");
                            var arrow = document.createElement("i");

                            listItem.className = "br-fab-favorites__item";
                            link.className = "br-fab-favorites__link";
                            link.href = item.url;
                            content.className = "br-fab-favorites__item-content";
                            label.className = "br-fab-favorites__item-label";
                            label.textContent = item.label;
                            description.className = "br-fab-favorites__item-description";
                            description.textContent = item.description;
                            arrow.className = "fa-solid fa-arrow-right br-fab-favorites__item-arrow";
                            arrow.setAttribute("aria-hidden", "true");
                            content.appendChild(label);

                            if(item.description) {

                                content.appendChild(description);

                            }

                            link.append(content, arrow);
                            listItem.appendChild(link);
                            groupList.appendChild(listItem);

                        });

                        groupItem.append(groupHead, groupList);
                        list.appendChild(groupItem);

                    });

                    body.appendChild(list);

                }

                btn.addEventListener("click", function (e) {

                    e.stopPropagation();
                    setOpen(!panel.classList.contains("br-fab-favorites__panel--open"));

                });

                closeBtn.addEventListener("click", function () { setOpen(false); });

                if(backdrop) backdrop.addEventListener("click", function () { setOpen(false); });

                document.addEventListener("keydown", function (e) {
                    if (e.key === "Escape" && root.getAttribute("data-open") === "1") { setOpen(false); btn.focus(); }
                });

                document.addEventListener("click", function (e) {
                    if (root.getAttribute("data-open") === "1" && !root.contains(e.target)) { setOpen(false); }
                });

                window.addEventListener("br:preferences-updated", function (event) {
                    renderFavoriteItems(event.detail?.preferences || window.preferences || {});
                });

            })();
        </script>

        @include("System.layouts.partials.down")
        <script>
            (function () {

                var navigation = document.getElementById("layout-menu");

                if(!navigation) {

                    return;

                }

                var tooltips = navigation.querySelectorAll('[data-bs-toggle="tooltip"]');

                tooltips.forEach(function (element) {

                    if(window.bootstrap?.Tooltip) {

                        window.bootstrap.Tooltip.getOrCreateInstance(element, {
                            container: document.body,
                            customClass: element.dataset.bsCustomClass || "br-navigation-tooltip",
                            trigger: "hover focus"
                        });

                    }

                });

                function sectionIdFor(target) {

                    var directSection = target.closest("[data-navigation-section]")?.dataset.navigationSection;

                    if(directSection) {

                        return directSection;

                    }

                    var targetTokens = Array.from(target.classList);
                    var matchingModule = Array.from(navigation.querySelectorAll(".br-navigation__module"))
                        .find(function (moduleLink) {

                            return targetTokens.some(function (token) {

                                return token.startsWith("menu-parent-") && moduleLink.classList.contains(token);

                            });

                        });

                    return matchingModule?.dataset.navigationSection || null;

                }

                var activeObserver = new MutationObserver(function (mutations) {

                    mutations.forEach(function (mutation) {

                        var target = mutation.target;

                        if(!target.classList.contains("active") && !target.classList.contains("open")) {

                            return;

                        }

                        var sectionId = sectionIdFor(target);

                        if(!sectionId) {

                            return;

                        }

                        navigation.querySelectorAll("[data-navigation-section]").forEach(function (moduleLink) {

                            var isActive = moduleLink.dataset.navigationSection === sectionId;
                            moduleLink.classList.toggle("is-active", isActive);
                            moduleLink.setAttribute("aria-current", isActive ? "true" : "false");

                        });

                    });

                });

                navigation.querySelectorAll("[class*='menu-']").forEach(function (element) {

                    activeObserver.observe(element, {
                        attributes: true,
                        attributeFilter: ["class"]
                    });

                });

            })();
        </script>
    </body>
</html>
