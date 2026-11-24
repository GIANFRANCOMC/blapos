const PREFERENCES_UPDATED_EVENT = "br:preferences-updated";

export function initializeMenu(createTooltip) {
    initializeFgNavigationDrawer();
    initializeNavbarSearch();
    initializeFavorites();
    initializeWorldNavigation(createTooltip);

    document.querySelectorAll(".fg-world--logout, .fg-user-logout").forEach(button => {
        button.addEventListener("click", confirmLogout);
    });
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

    if(!navigation) {

        return;

    }

    const modules = Array.from(navigation.querySelectorAll(".fg-world[data-navigation-section]"));
    const panels = Array.from(navigation.querySelectorAll("[data-navigation-panel]"));
    const currentSectionId = document.documentElement.dataset.navigationSectionId || "";
    let showingOtherWorldMenu = false;

    if(window.bootstrap?.Tooltip) {

        navigation.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (element) {

            createTooltip(element, element.getAttribute("title") || "");

        });

    }

    function selectWorld(sectionId) {

        const selectedModule = modules.find(function (moduleButton) {
            return moduleButton.dataset.navigationSection === sectionId;
        });

        if(!selectedModule) return;

        const hasSubmenu = Number(selectedModule.dataset.navigationCount) > 1;

        showingOtherWorldMenu = hasSubmenu && sectionId !== currentSectionId;

        modules.forEach(function (moduleButton) {

            const selected = moduleButton === selectedModule;

            moduleButton.classList.remove("active", "open");
            moduleButton.classList.toggle("is-active", selected);
            if(moduleButton.tagName === "BUTTON") {
                moduleButton.setAttribute("aria-pressed", selected ? "true" : "false");
            }

        });

        const alreadyVisible = panels.some(function (panel) {
            return !panel.hidden && panel.dataset.navigationPanel === sectionId;
        });

        panels.forEach(function (panel) {
            const visible = hasSubmenu && panel.dataset.navigationPanel === sectionId;
            panel.hidden = !visible;
            panel.classList.toggle("is-entering", visible && !alreadyVisible);
        });

        navigation.classList.toggle("without-context", !hasSubmenu);
        navigation.classList.toggle("has-context", hasSubmenu);
    }

    modules.forEach(function (moduleButton) {

        moduleButton.addEventListener("click", function (event) {

            window.bootstrap?.Tooltip?.getInstance(moduleButton)?.hide();

            if(moduleButton.tagName === "A") return;

            selectWorld(moduleButton.dataset.navigationSection);

            if(event.detail === 0) {
                panels.find(function (panel) {
                    return panel.dataset.navigationPanel === moduleButton.dataset.navigationSection;
                })?.querySelector(".fg-context__page")?.focus();
            }

        });

    });

    document.addEventListener("click", function (event) {

        if(!showingOtherWorldMenu) return;
        if(event.target.closest(".fg-context")) return;
        if(event.target.closest(".fg-world")) return;
        selectWorld(currentSectionId);

    });

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

    const activeObserver = new MutationObserver(function (mutations) {

        mutations.forEach(function (mutation) {

            if(showingOtherWorldMenu) return;

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
                if(moduleLink.tagName === "BUTTON") {
                    moduleLink.setAttribute("aria-pressed", isActive ? "true" : "false");
                }

            });

        });

    });

    navigation.querySelectorAll("[class*='menu-']").forEach(function (element) {

        activeObserver.observe(element, {
            attributes: true,
            attributeFilter: ["class"]
        });

    });

}

const normalizeSearchText = value => String(value ?? "")
    .normalize("NFD")
    .replace(/[\u0300-\u036f]/g, "")
    .toLocaleLowerCase("es")
    .trim();

export function rankNavigationItems(items, query, limit = 8) {

    const search = normalizeSearchText(query);

    if(!search) return items.slice(0, limit);

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

function initializeNavbarSearch() {

    const root = document.getElementById("brNavbarSearch");

    if(!root) return;

    const input = root.querySelector("#brNavbarSearchInput");
    const panel = root.querySelector("#brNavbarSearchPanel");
    const results = root.querySelector("#brNavbarSearchResults");
    const empty = root.querySelector("#brNavbarSearchEmpty");
    const items = Array.from(root.querySelectorAll("[data-fg-search-item]"))
        .map(element => ({
            element,
            label: element.dataset.searchLabel,
            world: element.dataset.searchWorld,
            description: element.dataset.searchDescription,
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

    }

    function open() {

        render();
        panel.hidden = false;
        input.setAttribute("aria-expanded", "true");

    }

    function close() {

        panel.hidden = true;
        input.setAttribute("aria-expanded", "false");

    }

    input.addEventListener("focus", open);
    input.addEventListener("input", open);
    input.addEventListener("keydown", event => {

        if(event.key === "ArrowDown" || event.key === "ArrowUp") {

            event.preventDefault();
            if(panel.hidden) open();
            const target = event.key === "ArrowDown" ? visible[0] : visible.at(-1);
            target?.element.focus();

        }else if(event.key === "Enter" && !panel.hidden && visible.length > 0) {

            event.preventDefault();
            visible[0].element.click();

        }else if(event.key === "Escape") {

            close();
            input.blur();

        }

    });

    panel.addEventListener("keydown", event => {

        if(event.key === "Escape") {

            event.preventDefault();
            close();
            input.focus({preventScroll: true});
            return;

        }

        if(event.key !== "ArrowDown" && event.key !== "ArrowUp") return;

        event.preventDefault();
        const index = visible.findIndex(item => item.element === document.activeElement);
        const next = index + (event.key === "ArrowDown" ? 1 : -1);

        if(next < 0) input.focus({preventScroll: true});
        else visible[Math.min(next, visible.length - 1)]?.element.focus({preventScroll: true});

    });

    panel.addEventListener("focusin", event => {
        setPrimary(visible.find(item => item.element === event.target));
    });

    document.addEventListener("keydown", event => {

        if(!(event.ctrlKey || event.metaKey) || event.altKey || event.key.toLowerCase() !== "k") return;

        event.preventDefault();
        input.focus({preventScroll: true});
        input.select();
        open();

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
        const groups = getFavoriteItems(preferences);
        const totalItems = groups.reduce((total, group) => total + group.items.length, 0);
        count.textContent = String(totalItems);
        count.hidden = totalItems === 0;
        body.replaceChildren();
        if(!totalItems) {
            const empty = document.createElement("p");
            empty.className = "fg-help";
            empty.textContent = "Aún no tienes favoritos.";
            body.append(empty);
        }
        groups.forEach(group => {
            const title = document.createElement("p");
            title.className = "fg-help";
            title.textContent = group.section;
            body.append(title);
            group.items.forEach(item => {
                const link = document.createElement("a");
                link.className = "fg-menu__item";
                link.href = item.url;
                link.textContent = item.label;
                body.append(link);
            });
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

function initializeFgNavigationDrawer() {
    const navigation = document.querySelector(".fg-navigation");
    const trigger = document.querySelector("[data-fg-menu-toggle]");
    if(!navigation || !trigger) return;
    const backdrop = document.querySelector(".fg-nav-backdrop");
    const mobile = window.matchMedia("(max-width: 1000px)");
    const setOpen = (open, restoreFocus = true) => {
        navigation.classList.toggle("fg-navigation--open", open);
        navigation.inert = mobile.matches && !open;
        navigation.toggleAttribute("inert", mobile.matches && !open);
        navigation.setAttribute("aria-hidden", String(mobile.matches && !open));
        trigger.setAttribute("aria-expanded", String(open));
        if(backdrop) backdrop.hidden = !mobile.matches || !open;
        if(open && mobile.matches) navigation.querySelector("button, a")?.focus({preventScroll: true});
        if(!open && restoreFocus) trigger.focus({preventScroll: true});
    };
    setOpen(!mobile.matches, false);
    mobile.addEventListener("change", () => { navigation.classList.remove("fg-navigation--collapsed"); setOpen(!mobile.matches, false); });
    trigger.addEventListener("click", () => {
        if(mobile.matches) setOpen(!navigation.classList.contains("fg-navigation--open"));
        else {
            const collapsed = navigation.classList.toggle("fg-navigation--collapsed");
            navigation.inert = collapsed;
            navigation.toggleAttribute("inert", collapsed);
            navigation.setAttribute("aria-hidden", String(collapsed));
            trigger.setAttribute("aria-expanded", String(!collapsed));
        }
    });
    document.querySelectorAll("[data-fg-menu-close]").forEach(button => button.addEventListener("click", () => setOpen(false)));
    document.addEventListener("keydown", event => {
        if(!mobile.matches || !navigation.classList.contains("fg-navigation--open")) return;
        if(event.key === "Escape") { event.preventDefault(); setOpen(false); }
        if(event.key === "Tab") {
            const focusable = [...navigation.querySelectorAll("a[href], button:not(:disabled)")].filter(element => element.getClientRects().length);
            const first = focusable[0], last = focusable.at(-1);
            if(event.shiftKey && document.activeElement === first) { event.preventDefault(); last?.focus(); }
            else if(!event.shiftKey && document.activeElement === last) { event.preventDefault(); first?.focus(); }
        }
    });
}
