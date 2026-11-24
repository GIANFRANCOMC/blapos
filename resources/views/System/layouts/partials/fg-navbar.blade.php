<header class="fg-topbar" id="layout-navbar">
    <div class="fg-topbar__start">
        @if($workspaceHome)
            <a href="{{ $workspaceHome->dom_route_url }}" @if($workspaceSection->subSections->count() === 1) id="{{ $workspaceSection->dom_id }}" @endif
               class="fg-topbar__brand" aria-current="{{ request()->routeIs('home.index') ? 'page' : 'false' }}" aria-label="Inicio">
                <img src="{{ $navbarLogomarkUrl }}" alt="" width="32" height="32">
            </a>
        @else
            <span class="fg-topbar__brand"><img src="{{ $navbarLogomarkUrl }}" alt="Blapos" width="32" height="32"></span>
        @endif
        <button type="button" class="fg-topbar__toggle fg-button fg-button--icon" data-fg-menu-toggle aria-controls="fg-navigation-context"
                aria-expanded="{{ $showNavigationContext ? 'true' : 'false' }}" aria-label="{{ $showNavigationContext ? 'Ocultar submódulos' : 'Mostrar submódulos' }}" @disabled(!$showNavigationContext)>
            <span class="fg-topbar__menu-icon" aria-hidden="true"></span>
        </button>
    </div>
    <div class="fg-global-search" id="brNavbarSearch" role="search" aria-label="Accesos de Blapos">
        <label class="fg-global-search__control fg-field__control" for="brNavbarSearchInput">
            <i class="fa-solid fa-magnifying-glass fg-field__prefix" aria-hidden="true"></i>
            <input class="fg-input" id="brNavbarSearchInput" type="search" autocomplete="off" placeholder="Buscar módulos…" data-compact-placeholder="Buscar…" aria-label="Buscar módulos en Blapos" aria-controls="brNavbarSearchPanel" aria-keyshortcuts="Control+K Meta+K">
            <kbd aria-hidden="true">Ctrl + K</kbd>
        </label>
        <p class="visually-hidden" id="brNavbarSearchStatus" role="status"></p>
        <div class="fg-global-search__panel" id="brNavbarSearchPanel" role="region" aria-label="Módulos encontrados" hidden>
            <div class="fg-global-search__results" id="brNavbarSearchResults">
                @foreach($navigationSections as $section)
                    @foreach($section->subSections as $subSection)
                        @if($subSection->dom_route_url !== '#' && $subSection->dom_route !== 'home.index')
                            @php
                                $isCurrentResult = $activeNavigationSubSection?->id === $subSection->id;
                            @endphp
                            <a href="{{ $subSection->dom_route_url }}" class="fg-global-search__item" data-fg-search-item data-search-label="{{ $subSection->dom_label }}" data-search-world="{{ $section->dom_label }}" data-search-description="{{ $subSection->description }}" @if($isCurrentResult) aria-current="page" @endif>
                                <span class="fg-topbar__module-icon" aria-hidden="true"><i class="{{ $subSection->dom_icon ?: 'fa-regular fa-file' }}"></i></span>
                                <span class="fg-global-search__item-text">
                                    <strong>{{ $subSection->dom_label }}</strong>
                                    <small>{{ $section->dom_label }}</small>
                                </span>
                                @if($isCurrentResult)
                                    <span class="fg-global-search__current" aria-hidden="true">Estás aquí</span>
                                @endif
                            </a>
                        @endif
                    @endforeach
                @endforeach
            </div>
            <p class="fg-help" id="brNavbarSearchEmpty" hidden>No se encontraron módulos con ese término.</p>
        </div>
    </div>
    <div class="fg-topbar__actions">
        @if($favoritesEnabled)
            <div class="fg-menu" id="brFabFavorites">
                <button id="brFabFavoritesToggle" class="fg-button fg-button--icon" type="button" data-bs-toggle="dropdown" data-bs-display="static" aria-controls="brFabFavoritesPanel" aria-expanded="false" aria-label="Abrir favoritos">
                    <i class="fa-regular fa-star" aria-hidden="true"></i>
                </button>
                @component("System.components.navbar-panel", ["id" => "brFabFavoritesPanel"])
                    @slot("heading")
                        <h2 class="fg-topbar__panel-title" id="brFabFavoritesPanel-title">Favoritos</h2>
                        <span class="fg-topbar__panel-count" id="brFabFavoritesCount" @if($favoriteCount === 0) hidden @endif>{{ $favoriteCount }}</span>
                    @endslot
                    <div id="brFabFavoritesBody">
                        @foreach($favoriteMenuGroups as $group)
                            <section class="fg-topbar__favorite-group">
                                <h3 class="fg-topbar__group-label">{{ $group['section'] }}</h3>
                                @foreach($group['items'] as $favoriteItem)
                                    <a href="{{ $favoriteItem['url'] }}" class="dropdown-item fg-menu__item">
                                        <span class="fg-topbar__module-icon" aria-hidden="true"><i class="{{ $favoriteItem['icon'] }}"></i></span>
                                        {{ $favoriteItem['label'] }}
                                    </a>
                                @endforeach
                            </section>
                        @endforeach
                    </div>
                    <p class="fg-topbar__empty" id="brFabFavoritesEmpty" @if($favoriteCount > 0) hidden @endif>
                        <i class="fa-regular fa-star" aria-hidden="true"></i>Aún no tienes favoritos.
                    </p>
                    @slot("footer")
                        <a href="{{ route('favorites.index') }}" class="dropdown-item fg-menu__item fg-topbar__panel-action">
                            <i class="fa-solid fa-gear" aria-hidden="true"></i>Configurar favoritos
                        </a>
                    @endslot
                @endcomponent
            </div>
        @endif
        <div class="fg-menu">
            <button class="fg-button fg-button--icon" type="button" data-bs-toggle="dropdown" data-bs-display="static" aria-controls="fg-topbar-notices" aria-expanded="false"
                    aria-label="Abrir avisos{{ $navbarAnnouncementCount > 0 ? ', '.$navbarAnnouncementCount.($navbarAnnouncementCount === 1 ? ' vigente' : ' vigentes') : '' }}">
                <i class="fa-regular fa-bell" aria-hidden="true"></i>
                @if($navbarAnnouncementCount > 0)<span class="fg-topbar__count" aria-hidden="true">{{ $navbarAnnouncementCount }}</span>@endif
            </button>
            @component("System.components.navbar-panel", ["id" => "fg-topbar-notices", "focusable" => $navbarAnnouncementCount > 0])
                @slot("heading")
                    <h2 class="fg-topbar__panel-title" id="fg-topbar-notices-title">Notificaciones</h2>
                    @if($navbarAnnouncementCount > 0)
                        <span class="fg-topbar__panel-count">{{ $navbarAnnouncementCount }}</span>
                    @endif
                @endslot
                @forelse(($tenantAnnouncements ?? collect()) as $announcement)
                    @php
                        [$noticeIcon, $noticeLabel] = match($announcement->severity) {
                            'success' => ['fa-solid fa-circle-check', 'Confirmación'],
                            'warning' => ['fa-solid fa-triangle-exclamation', 'Advertencia'],
                            'danger' => ['fa-solid fa-circle-exclamation', 'Aviso importante'],
                            default => ['fa-solid fa-circle-info', 'Información'],
                        };
                    @endphp
                    <article class="fg-topbar__notice" data-severity="{{ $announcement->severity }}">
                        <span class="fg-topbar__module-icon" aria-hidden="true"><i class="{{ $noticeIcon }}"></i></span>
                        <div class="fg-topbar__notice-content">
                            <h3><span class="visually-hidden">{{ $noticeLabel }}. </span>{{ $announcement->title }}</h3>
                            <p>{{ $announcement->message }}</p>
                            @if($announcement->created_at)
                                <time datetime="{{ $announcement->created_at->toIso8601String() }}">{{ $announcement->created_at->diffForHumans() }}</time>
                            @endif
                        </div>
                    </article>
                @empty
                    <p class="fg-topbar__empty"><i class="fa-regular fa-bell" aria-hidden="true"></i>No hay avisos vigentes.</p>
                @endforelse
            @endcomponent
        </div>
        <div class="fg-menu">
            <button class="fg-button fg-button--icon fg-user" type="button" data-bs-toggle="dropdown" data-bs-display="static" aria-controls="fg-topbar-account" aria-expanded="false" aria-label="Abrir menú de {{ $user->name }}">
                <span class="fg-user__avatar" aria-hidden="true">{{ $navbarUserInitial }}</span>
            </button>
            @component("System.components.navbar-panel", ["id" => "fg-topbar-account"])
                @slot("heading")
                    <div class="fg-topbar__user-summary">
                        <span class="fg-topbar__identity-avatar" aria-hidden="true">{{ $navbarUserInitial }}</span>
                        <div class="fg-topbar__identity-details">
                            <h2 id="fg-topbar-account-title">{{ $user->name }}</h2>
                            <span>{{ $navbarCompanyName }}</span>
                            <small>{{ $user->email }}</small>
                            @if($role?->name)
                                <small class="fg-topbar__identity-role">Perfil: {{ $role->name }}</small>
                            @endif
                        </div>
                    </div>
                @endslot
                <a href="{{ route('account.index') }}" class="dropdown-item fg-menu__item"><i class="fa-regular fa-user" aria-hidden="true"></i>Mi cuenta</a>
                @if($favoritesEnabled)
                    <a href="{{ route('favorites.index') }}" class="dropdown-item fg-menu__item"><i class="fa-regular fa-star" aria-hidden="true"></i>Favoritos</a>
                @endif
                @slot("footer")
                    <button class="dropdown-item fg-menu__item fg-user-logout" type="button"><i class="fa-solid fa-power-off" aria-hidden="true"></i>Cerrar sesión</button>
                @endslot
            @endcomponent
            <form method="POST" action="{{ route('logout') }}" id="logout" hidden>@csrf</form>
        </div>
    </div>
</header>
