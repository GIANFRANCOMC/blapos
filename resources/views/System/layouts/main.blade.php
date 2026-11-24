<!DOCTYPE html>

@php

    $ownerApp = config("app.owner_app");
    $user     = Auth::user();
    $company  = app(\App\Services\System\Tenancy\TenantCompanyContext::class)->get();
    $navbarLogomarkUrl = asset($ownerApp->assets->img->logomark);
    $navbarCompanyName = $company->legal_name ?: $company->commercial_name ?: "Empresa";
    $navbarUserInitial = \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr(trim($user->name), 0, 1)) ?: "?";
    $role     = $user->role;
    $systemAssetsPath = rtrim(asset('System/assets'), '/').'/';
    $sections = \App\Services\System\Organizations\Companies\CompanySectionService::getSections($role?->id);
    $user->load("preferences");
    $preferences = $user->formatted_preferences;

    $allowedNavigationPages = $sections->pluck("subSections")->flatten();
    $subSectionIds = $allowedNavigationPages->pluck("id")->toArray();
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

            $section = clone $section;
            $section->setRelation(
                "subSections",
                $section->subSections->filter(fn($page) => $page->dom_route === "home.index"
                    || in_array($page->id, $sectionFiltered, true))->values()
            );

            return $section;

        })
        ->filter(fn($section) => $section->subSections->isNotEmpty())
        ->values();

    $workspaceSection = $navigationSections->firstWhere("slug", "sc_workspace");
    $workspaceHome = $workspaceSection?->subSections->firstWhere("dom_route", "home.index");
    $worldNavigationSections = $navigationSections->filter(fn($section) => $section->slug !== "sc_workspace"
        || $section->subSections->contains(fn($page) => $page->dom_route !== "home.index"));
    $navigationWorldLabel = fn($section) => $section->slug === "sc_workspace" ? "Espacio de trabajo" : $section->dom_label;

    $activeNavigationSubSection = $allowedNavigationPages->firstWhere("dom_route", request()->route()?->getName());
    if(!$activeNavigationSubSection) {
        $routes = Illuminate\Support\Facades\Route::getRoutes();
        $matchedPageId = $allowedNavigationPages
            ->mapWithKeys(fn($page) => [$page->id => $routes->getByName($page->dom_route)?->uri()])
            ->filter(fn($uri) => $uri && request()->is(ltrim($uri, "/")."/*"))
            ->sortByDesc(fn($uri) => strlen($uri))
            ->keys()
            ->first();
        $activeNavigationSubSection = $allowedNavigationPages->firstWhere("id", $matchedPageId);
    }

    $activeNavigationSection = $activeNavigationSubSection
        ? $sections->firstWhere("id", $activeNavigationSubSection->section_id)
        : (request()->routeIs("home.index") ? null : $navigationSections->first());
    $hasNavigationContext = fn($section) => $section && ($section->has_sub_menu || $section->slug === "sc_workspace")
        && $section->subSections->count() > 1;
    $showNavigationContext = $hasNavigationContext($navigationSections->firstWhere("id", $activeNavigationSection?->id));
    $favoritesEnabled = \App\Services\System\General\AutomaticMasterDataService::enabled(
        \App\Services\System\General\AutomaticMasterDataService::MENU_FAVORITES
    ) && $allowedNavigationPages->contains("dom_route", "favorites.index");

    $favoritePreferences = collect($valuePreferences)
        ->filter(fn($preference) => data_get($preference, "is_favorite", false))
        ->pluck("sub_section_id")
        ->map(fn($id) => (int) $id)
        ->unique()
        ->values()
        ->toArray();
    $favoriteMenuGroups = collect();

    foreach($favoritesEnabled ? $navigationSections : [] as $section) {

        $favoriteSubSections = $section->subSections->whereIn("id", $favoritePreferences)
            ->whereNotIn("dom_route", ["home.index", "favorites.index"]);

        if($favoriteSubSections->isEmpty()) {

            continue;

        }

        $favoriteMenuGroups->push([
            "section" => $section->dom_label,
            "items" => $favoriteSubSections->map(fn($subSection) => [
                "label" => $subSection->dom_label,
                "url" => $subSection->dom_route_url,
                "icon" => $subSection->dom_icon ?: "fa-regular fa-file",
            ])->values(),
        ]);

    }

    $favoriteCount = $favoriteMenuGroups->sum(fn($group) => $group['items']->count());
    $navbarAnnouncementCount = ($tenantAnnouncements ?? collect())->count();
@endphp

<html
    lang="es"
    class="fg-app"
    dir="ltr"
    data-assets-path="{{ $systemAssetsPath }}"
    data-navigation-section-id="{{ $activeNavigationSection?->id }}"
    data-navigation-world="{{ $activeNavigationSection?->dom_label }}"
    data-navigation-group="{{ $activeNavigationSubSection?->menuGroup?->name }}"
    data-navigation-page="{{ $activeNavigationSubSection?->dom_label }}">
    <head>
        @include("System.layouts.partials.fg-up")
        <script>
            window.ownerApp = @json($ownerApp ?? null);
            window.user     = @json($user ?? null);
            window.company  = @json($company ?? null);
            window.numericInputConfig = @json(\App\Helpers\System\Numbers::inputConfig());
            window.sections = @json($navigationSections);
            window.preferences = @json($preferences ?? []);
        </script>
        @vite(["resources/css/System/fg/index.css", "resources/js/System/Layout/main.js"])
    </head>
    <body class="fg-theme">
        <div class="fg-layout">
            <div class="fg-layout__container">
                @include("System.layouts.partials.fg-navbar")
                @include("System.layouts.partials.fg-navigation")
                <div class="fg-layout-page">
                    <div class="fg-layout-content">
                        <div id="brNavigationContent" class="fg-layout-content__inner">
                            @yield('content')
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @include("System.layouts.partials.fg-down")
    </body>
</html>
