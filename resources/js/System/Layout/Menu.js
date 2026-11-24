const PREFERENCES_UPDATED_EVENT = "br:preferences-updated";
const EXCLUDED_FAVORITE_ROUTES = new Set(["home.index", "favorites.index"]);

export function canFavoritePage(page) {
    return !EXCLUDED_FAVORITE_ROUTES.has(page?.dom_route);
}

export function initializeMenu(createTooltip) {
    const closeMobilePanel = initializeWorldNavigation(createTooltip);
    initializeNavbarSearch(closeMobilePanel);
    initializeFavorites();

    document.querySelector(".fg-user-logout")?.addEventListener("click", confirmLogout);

}

export function navbarItem(id, {type = "active", addClass = null} = {}) {
    if(!id) return;

    for(const item of document.getElementsByClassName(id)) {
        item.classList.add(type);
    }

    const item = document.getElementById(id);
    item?.classList.add(type);
    if(addClass) item?.classList.add(addClass);
}

export function updateMenuPreferences(preferences) {
    window.preferences = preferences;
    window.dispatchEvent(new CustomEvent(PREFERENCES_UPDATED_EVENT, {detail: {preferences}}));
}

function initializeWorldNavigation(createTooltip) {

    const navigation = document.getElementById("fg-layout-menu");
    const trigger = document.querySelector("[data-fg-menu-toggle]");
    const layout = trigger?.closest(".fg-layout");

    if(!navigation || !layout) {

        return;

    }

    const modules = Array.from(navigation.querySelectorAll(".fg-world[data-navigation-section]"));
    const context = navigation.querySelector(".fg-context");
    const panels = Array.from(navigation.querySelectorAll("[data-navigation-panel]"));
    const content = layout.querySelector(".fg-layout-content");
    const backdrop = document.querySelector(".fg-nav-backdrop");
    const mobile = window.matchMedia("(max-width: 767px)");
    const currentSectionId = document.documentElement.dataset.navigationSectionId || "";
    const currentPanel = panels.find(panel => panel.dataset.navigationPanel === currentSectionId);
    const initialPanel = panels.find(panel => !panel.hidden);
    let lastPanel = initialPanel;
    let panelOpener = null;

    const desktopTooltips = layout.querySelectorAll("[data-fg-desktop-tooltip]");
    const syncRailTooltips = () => desktopTooltips.forEach(element => createTooltip(element));
    syncRailTooltips();

    function setVisiblePanel(selectedPanel, {animate = true, returnFocus = false, drawerOpen = mobile.matches && Boolean(selectedPanel)} = {}) {
        const previousPanel = lastPanel;
        const hasSubmenu = Boolean(selectedPanel);
        const renderedPanel = selectedPanel || previousPanel;
        const wasContextOpen = !context.inert;
        const selectedSectionId = selectedPanel?.dataset.navigationPanel || currentSectionId;
        if(hasSubmenu) lastPanel = selectedPanel;
        document.documentElement.classList.toggle("fg-menu-motion", animate);
        if(content) content.inert = drawerOpen;
        trigger.disabled = !lastPanel && (!mobile.matches || modules.length === 0);

        const closingFocusedDrawer = mobile.matches && !drawerOpen && navigation.contains(document.activeElement);
        if(!hasSubmenu && (returnFocus || closingFocusedDrawer || context.contains(document.activeElement))) {
            (mobile.matches ? trigger : panelOpener || trigger).focus({preventScroll: true});
        }

        modules.forEach(function (moduleButton) {
            const selected = moduleButton.dataset.navigationSection === selectedSectionId;
            moduleButton.classList.remove("active", "open");
            moduleButton.classList.toggle("is-active", selected);
            if(moduleButton.tagName === "BUTTON") {
                moduleButton.setAttribute("aria-expanded", String(selected && hasSubmenu));
            }
        });

        panels.forEach(function (panel) {
            const visible = panel === renderedPanel;
            if(!visible || !hasSubmenu) panel.classList.remove("is-entering");
            if(visible && panel.hidden && wasContextOpen) panel.classList.add("is-entering");
            panel.hidden = !visible;
        });

        context.inert = !hasSubmenu;
        context.setAttribute("aria-hidden", String(!hasSubmenu));
        navigation.classList.toggle("without-context", !hasSubmenu);
        navigation.classList.toggle("has-context", hasSubmenu);
        navigation.classList.toggle("fg-navigation--open", drawerOpen);
        navigation.inert = mobile.matches && !drawerOpen;
        const expanded = mobile.matches ? drawerOpen : hasSubmenu;
        const target = mobile.matches ? "menú lateral" : "submódulos";
        trigger.setAttribute("aria-controls", mobile.matches ? navigation.id : context.id);
        trigger.setAttribute("aria-expanded", String(expanded));
        trigger.setAttribute("aria-label", `${expanded ? "Ocultar" : "Mostrar"} ${target}`);
        if(backdrop) backdrop.inert = !drawerOpen;
    }

    function openPanel(panel, opener, moveFocus) {
        panelOpener = opener;
        const moveFocusFromPanel = !context.inert && lastPanel !== panel && context.contains(document.activeElement);
        if(moveFocusFromPanel && opener) opener.focus({preventScroll: true});
        setVisiblePanel(panel);
        if(moveFocus || moveFocusFromPanel) panel.querySelector(".fg-context__page")?.focus();
    }

    function isNavigationOpen() {
        return mobile.matches ? navigation.classList.contains("fg-navigation--open") : !context.inert;
    }

    function closePanel(returnFocus = true) {
        if(isNavigationOpen()) {
            setVisiblePanel(null, {returnFocus});
            desktopTooltips.forEach(element => {
                window.bootstrap?.Tooltip?.getInstance(element)?.hide();
            });
        }
    }

    setVisiblePanel(mobile.matches ? null : initialPanel, {animate: false});
    mobile.addEventListener("change", () => {
        syncRailTooltips();
        setVisiblePanel(mobile.matches || context.inert ? null : lastPanel, {animate: false});
    });

    trigger.addEventListener("click", () => {
        if(isNavigationOpen()) {
            closePanel(false);
        } else if(mobile.matches) {
            setVisiblePanel(lastPanel, {drawerOpen: true});
            (modules.find(button => button.classList.contains("is-active")) || lastPanel?.querySelector(".fg-context__page") || modules[0])
                ?.focus({preventScroll: true});
        } else if(lastPanel) {
            openPanel(lastPanel, trigger, true);
        }
    });

    modules.forEach(function (moduleButton) {

        moduleButton.addEventListener("click", event => {
            window.bootstrap?.Tooltip?.getInstance(moduleButton)?.hide();
            if(moduleButton.tagName === "A") return;

            const panel = panels.find(candidate => candidate.dataset.navigationPanel === moduleButton.dataset.navigationSection);
            if(panel) openPanel(panel, moduleButton, mobile.matches || event.detail === 0);
        });

    });

    document.querySelectorAll("[data-fg-menu-close]").forEach(button => {
        button.addEventListener("click", () => closePanel());
    });

    document.addEventListener("click", event => {
        if(!isNavigationOpen() || navigation.contains(event.target) || trigger.contains(event.target)) return;
        if(mobile.matches || !currentPanel) closePanel(false);
        else if(lastPanel !== currentPanel) openPanel(currentPanel, null, false);
    });

    document.addEventListener("keydown", event => {
        if(event.defaultPrevented || !isNavigationOpen()) return;
        if(event.key === "Escape" && (mobile.matches || navigation.contains(event.target) || trigger.contains(event.target))) {
            event.preventDefault();
            closePanel();
        }
    });

    const closeMobilePanel = () => {
        if(mobile.matches) closePanel();
    };

    function sectionIdFor(target) {

        const directSection = target.closest("[data-navigation-section]")?.dataset.navigationSection;

        if(directSection) {

            return directSection;

        }

        const targetTokens = Array.from(target.classList);
        const matchingModule = modules
            .find(function (moduleLink) {

                return targetTokens.some(function (token) {

                    return token.startsWith("menu-parent-") && moduleLink.classList.contains(token);

                });

            });

        return matchingModule?.dataset.navigationSection || null;

    }

    if(document.documentElement.dataset.navigationPage) return closeMobilePanel;

    const activeObserver = new MutationObserver(function (mutations) {

        mutations.forEach(function (mutation) {

            if(!context.inert && lastPanel !== currentPanel) return;

            const target = mutation.target;

            if(!target.classList.contains("active") && !target.classList.contains("open")) {

                return;

            }

            const sectionId = sectionIdFor(target);

            if(!sectionId) {

                return;

            }

            modules.forEach(function (moduleLink) {

                const isActive = moduleLink.dataset.navigationSection === sectionId;
                moduleLink.classList.toggle("is-active", isActive);

            });

        });

    });

    navigation.querySelectorAll("[class*='menu-']").forEach(function (element) {

        activeObserver.observe(element, {
            attributes: true,
            attributeFilter: ["class"]
        });

    });

    return closeMobilePanel;

}

const normalizeSearchText = value => String(value ?? "")
    .normalize("NFD")
    .replace(/[\u0300-\u036f]/g, "")
    .toLocaleLowerCase("es")
    .trim();

export function rankNavigationItems(items, query, limit = items.length) {

    const search = normalizeSearchText(query);

    if(!search) {
        const current = items.find(item => item.isCurrent);
        const ordered = current ? [current, ...items.filter(item => item !== current)] : items;
        return ordered.slice(0, limit);
    }

    const terms = search.split(/\s+/);

    return items
        .map((item, index) => {

            const label = normalizeSearchText(item.label);
            const world = normalizeSearchText(item.world);
            const description = normalizeSearchText(item.description);
            const combined = `${label} ${world} ${description}`;

            if(!terms.every(term => combined.includes(term))) return null;

            const score = label === search ? 100
                : label.startsWith(search) ? 80
                : label.includes(search) ? 60
                : world.includes(search) ? 30
                : 10;

            return {item, index, score};

        })
        .filter(Boolean)
        .sort((left, right) => (right.score - left.score) || (left.index - right.index))
        .slice(0, limit)
        .map(result => result.item);

}

function initializeNavbarSearch(closeMobilePanel) {

    const root = document.getElementById("brNavbarSearch");

    if(!root) return;

    const input = root.querySelector("#brNavbarSearchInput");
    const compact = window.matchMedia("(max-width: 480px)");
    const defaultPlaceholder = input.placeholder;
    const updatePlaceholder = () => {
        input.placeholder = compact.matches ? input.dataset.compactPlaceholder : defaultPlaceholder;
    };
    updatePlaceholder();
    compact.addEventListener("change", updatePlaceholder);

    const panel = root.querySelector("#brNavbarSearchPanel");
    const results = root.querySelector("#brNavbarSearchResults");
    const empty = root.querySelector("#brNavbarSearchEmpty");
    const status = root.querySelector("#brNavbarSearchStatus");
    const items = Array.from(root.querySelectorAll("[data-fg-search-item]"))
        .map(element => ({
            element,
            label: element.dataset.searchLabel,
            world: element.dataset.searchWorld,
            description: element.dataset.searchDescription,
            isCurrent: element.getAttribute("aria-current") === "page",
        }));
    let visible = [];

    function setPrimary(item) {

        items.forEach(candidate => {
            candidate.element.classList.toggle("is-primary-result", candidate === item);
        });

    }

    function render() {

        visible = rankNavigationItems(items, input.value);
        const shown = new Set(visible.map(item => item.element));

        items.forEach(item => {
            item.element.hidden = !shown.has(item.element);
        });

        visible.forEach(item => results.appendChild(item.element));
        setPrimary(visible[0]);

        empty.textContent = items.length === 0
            ? "No tienes módulos disponibles para buscar."
            : "No se encontraron módulos con ese término.";
        empty.hidden = visible.length > 0;
        status.textContent = visible.length === 0 ? empty.textContent
            : visible.length === 1 ? "1 módulo disponible."
            : `${visible.length} módulos disponibles.`;

    }

    function open() {

        render();
        panel.hidden = false;
        results.scrollTop = 0;

    }

    function close() {

        panel.hidden = true;
        status.textContent = "";

    }

    input.addEventListener("focus", () => { if(panel.hidden) open(); });
    input.addEventListener("input", open);
    input.addEventListener("click", () => { if(panel.hidden) open(); });
    input.addEventListener("keydown", event => {

        if(event.key === "ArrowDown" || event.key === "ArrowUp") {

            event.preventDefault();
            if(panel.hidden) open();
            const target = event.key === "ArrowDown" ? visible[0] : visible.at(-1);
            target?.element.focus();

        }else if(event.key === "Enter" && !panel.hidden && visible.length > 0) {

            event.preventDefault();
            visible[0].element.click();

        }

    });

    panel.addEventListener("keydown", event => {

        if(event.key !== "ArrowDown" && event.key !== "ArrowUp") return;

        event.preventDefault();
        const index = visible.findIndex(item => item.element === document.activeElement);
        const next = index + (event.key === "ArrowDown" ? 1 : -1);

        if(next < 0) input.focus({preventScroll: true});
        else visible[Math.min(next, visible.length - 1)]?.element.focus();

    });

    panel.addEventListener("focusin", event => {
        setPrimary(visible.find(item => item.element === event.target));
    });

    root.addEventListener("keydown", event => {
        if(event.key !== "Escape" || panel.hidden) return;
        event.preventDefault();
        input.focus({preventScroll: true});
        close();
    });

    document.addEventListener("keydown", event => {

        if(!(event.ctrlKey || event.metaKey) || event.altKey || event.key.toLowerCase() !== "k") return;

        event.preventDefault();
        closeMobilePanel?.();
        input.focus({preventScroll: true});
        input.select();
        if(panel.hidden) open();

    });

    document.addEventListener("pointerdown", event => {
        if(!root.contains(event.target)) close();
    });

    document.addEventListener("focusin", event => {
        if(!root.contains(event.target)) close();
    });

}
function initializeFavorites() {
    const root = document.getElementById("brFabFavorites");
    if (!root) return;
    const body = document.getElementById("brFabFavoritesBody");
    const count = document.getElementById("brFabFavoritesCount");
    const empty = document.getElementById("brFabFavoritesEmpty");
    function getFavoriteItems(preferences) {

        const preferenceConfig = preferences?.config_companies_sub_sections;
        const favoriteIds = new Set(
            (preferenceConfig?.sub_sections || [])
                .filter(function (preference) { return preference?.is_favorite; })
                .map(function (preference) { return Number(preference.sub_section_id); })
        );
        const groups = [];

        (window.sections || []).forEach(function (section) {

            const subSections = section?.sub_sections || section?.subSections || [];
            const items = [];

            subSections.forEach(function (subSection) {

                if(!canFavoritePage(subSection) || !favoriteIds.has(Number(subSection.id))) return;

                items.push({
                    label: subSection.dom_label,
                    url: subSection.dom_route_url,
                    icon: subSection.dom_icon || "fa-regular fa-file",
                });

            });

            if(items.length > 0) {

                groups.push({
                    section: section.dom_label,
                    items: items
                });

            }

        });

        return groups;

    }


    function renderFavoriteItems(preferences) {
        const groups = getFavoriteItems(preferences);
        const totalItems = groups.reduce((total, group) => total + group.items.length, 0);
        count.textContent = String(totalItems);
        count.hidden = totalItems === 0;
        empty.hidden = totalItems > 0;
        body.replaceChildren();
        groups.forEach(group => {
            const section = document.createElement("section");
            section.className = "fg-topbar__favorite-group";
            const title = document.createElement("h3");
            title.className = "fg-topbar__group-label";
            title.textContent = group.section;
            section.append(title);
            group.items.forEach(item => {
                const link = document.createElement("a");
                link.className = "dropdown-item fg-menu__item";
                link.href = item.url;
                const iconSlot = document.createElement("span");
                iconSlot.className = "fg-topbar__module-icon";
                iconSlot.setAttribute("aria-hidden", "true");
                const icon = document.createElement("i");
                icon.className = item.icon;
                iconSlot.append(icon);
                link.append(iconSlot, item.label);
                section.append(link);
            });
            body.append(section);
        });
    }
    window.addEventListener(PREFERENCES_UPDATED_EVENT, event => {
        renderFavoriteItems(event.detail?.preferences || window.preferences || {});
    });
}

function confirmLogout() {

    const submitLogout = function () {

        document.getElementById("logout")?.requestSubmit();

    };

    if (!window.Swal) {

        if (window.confirm("Cerrar sesión: tendrás que volver a ingresar tus credenciales.")) submitLogout();

        return;

    }

    window.Swal.fire({
        allowEscapeKey: false,
        title: "¿Cerrar sesión?",
        text: "Tendrás que volver a ingresar tus credenciales para continuar.",
        showCancelButton: true,
        showCloseButton: true,
        closeButtonAriaLabel: "Cerrar confirmación",
        confirmButtonText: "Cerrar sesión",
        buttonsStyling: false,
        customClass: {
            container: "fg-swal-layer",
            popup: "fg-swal",
            confirmButton: "fg-button fg-button--danger",
            cancelButton: "fg-button fg-button--secondary"
        },
        reverseButtons: true
    }).then(function (result) {

        if (result.isConfirmed) submitLogout();

    });

}
