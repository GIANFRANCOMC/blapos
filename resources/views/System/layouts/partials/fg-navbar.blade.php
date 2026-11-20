<header class="fg-topbar" id="layout-navbar">
    <button type="button" class="fg-button fg-button--icon" data-fg-menu-toggle aria-controls="fg-layout-menu" aria-expanded="false" aria-label="Alternar menú lateral"><i class="fa-solid fa-bars" aria-hidden="true"></i></button>
    <div class="fg-global-search" id="brNavbarSearch">
        <div class="fg-global-search__control"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i><input id="brNavbarSearchInput" type="search" autocomplete="off" placeholder="Buscar en Blapos…" aria-label="Buscar módulos y funciones" aria-controls="brNavbarSearchPanel" aria-expanded="false"><kbd>Ctrl + K</kbd></div>
        <div class="fg-global-search__panel" id="brNavbarSearchPanel" role="region" aria-label="Resultados de búsqueda" hidden>
            <div class="fg-help">Módulos</div>
            <div id="brNavbarSearchResults">
                @foreach($navigationSections as $section)
                    @foreach($section->subSections as $subSection)
                        @if($subSection->dom_route_url !== '#')
                            <a href="{{ $subSection->dom_route_url }}" class="fg-global-search__item" data-br-search-item data-search-label="{{ $subSection->dom_label }}" data-search-world="{{ $section->dom_label }}" data-search-description="{{ $subSection->description }}"><strong>{{ $subSection->dom_label }}</strong><small>{{ $section->dom_label }}</small></a>
                        @endif
                    @endforeach
                @endforeach
            </div>
            <p class="fg-help" id="brNavbarSearchEmpty" hidden>No se encontraron módulos con ese término.</p>
        </div>
    </div>
    <div class="fg-topbar__actions">
        @if($favoritesEnabled)
            <div class="fg-menu" id="brFabFavorites"><button id="brFabFavoritesToggle" class="fg-button fg-button--icon" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Abrir favoritos"><i class="fa-regular fa-star" aria-hidden="true"></i></button>
                <div class="dropdown-menu dropdown-menu-end fg-menu__panel fg-topbar__panel">
                    <strong class="fg-section-title">Favoritos</strong><span class="fg-help" id="brFabFavoritesCount" @if($favoriteCount === 0) hidden @endif>{{ $favoriteCount }}</span>
                    <div id="brFabFavoritesBody">
                    @forelse($favoriteMenuGroups as $group)
                        <p class="fg-help">{{ $group['section'] }}</p>
                        @foreach($group['items'] as $favoriteItem)<a href="{{ $favoriteItem['url'] }}" class="fg-menu__item">{{ $favoriteItem['label'] }}</a>@endforeach
                    @empty <p class="fg-help">Aún no tienes favoritos.</p> @endforelse
                    </div><a href="{{ route('home.index') }}" class="fg-menu__item">Configurar favoritos</a>
                </div>
            </div>
        @endif
        <div class="fg-menu"><button class="fg-button fg-button--icon" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Abrir avisos{{ $navbarAnnouncementCount > 0 ? ', '.$navbarAnnouncementCount.' vigentes' : '' }}"><i class="fa-regular fa-bell" aria-hidden="true"></i>@if($navbarAnnouncementCount > 0)<span class="fg-topbar__count">{{ $navbarAnnouncementCount }}</span>@endif</button>
            <div class="dropdown-menu dropdown-menu-end fg-menu__panel fg-topbar__panel">
                <strong class="fg-section-title">Notificaciones</strong>
                @forelse(($tenantAnnouncements ?? collect()) as $announcement)
                    <article class="fg-topbar__notice"><strong>{{ $announcement->title }}</strong><p>{{ $announcement->message }}</p>@if($announcement->created_at)<small class="fg-help">{{ $announcement->created_at->diffForHumans() }}</small>@endif</article>
                @empty <p class="fg-help">No hay avisos vigentes.</p> @endforelse
            </div>
        </div>
        <div class="fg-menu"><button class="fg-user" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Abrir menú de {{ $user->name }}"><span class="fg-user__avatar" aria-hidden="true">{{ $userInitials }}</span><span class="fg-user__identity"><span>{{ $user->name }}</span><small>{{ $navbarCompanyName }}</small></span><i class="fa-solid fa-chevron-down" aria-hidden="true"></i></button>
            <ul class="dropdown-menu dropdown-menu-end fg-menu__panel"><li class="fg-topbar__user-summary"><strong>{{ $user->name }}</strong><small class="fg-help">{{ $user->email }}</small></li><li><a href="{{ route('account.index') }}" class="fg-menu__item">Mi cuenta</a></li>@if($favoritesEnabled)<li><a href="{{ route('home.index') }}" class="fg-menu__item">Preferencias</a></li>@endif<li><button class="fg-menu__item fg-user-logout" type="button"><i class="fa-solid fa-power-off" aria-hidden="true"></i>Cerrar sesión</button></li></ul>
        </div>
    </div>
</header>
