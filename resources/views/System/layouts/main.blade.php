<!DOCTYPE html>

@php

    $ownerApp = config("app.owner_app");
    $user     = Auth::user();
    $company  = app(\App\Services\System\Tenancy\TenantCompanyContext::class)->get();
    $navigationLogomarkUrl = app(\App\Services\System\Organizations\Companies\CompanyLogomarkService::class)->url($company->logomark);
    $navbarCompanyName = $company->legal_name ?: $company->commercial_name ?: "Empresa";
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
            window.sections = @json($sections ?? []);
            window.preferences = @json($preferences ?? []);
        </script>
        @vite(["resources/css/System/fg/index.css", "resources/js/System/Layout/main.js"])
    </head>
    <body class="fg-theme">
        <div class="fg-layout">
            <div class="fg-layout__container">

                    @include("System.layouts.partials.fg-navigation")

                <div class="fg-layout-page">

                        @include("System.layouts.partials.fg-navbar")

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
