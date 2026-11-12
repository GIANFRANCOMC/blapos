const PREFERENCES_UPDATED_EVENT = "br:preferences-updated";

export function initializeMenu(createTooltip) {
    initializeNavbarSearch();
    initializeFavorites();
    initializeWorldNavigation(createTooltip);

    document.querySelectorAll(".br-navigation__logout, .br-navbar-user__logout").forEach(button => {
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

    const navigation = document.getElementById("layout-menu");

    if(!navigation) {

        return;

    }

    const modules = Array.from(navigation.querySelectorAll(".br-navigation__module"));
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
        document.documentElement.classList.toggle("br-navigation-without-context", !hasSubmenu);
        document.documentElement.classList.toggle("br-navigation-with-context", hasSubmenu);
        if(hasSubmenu && document.documentElement.classList.contains("layout-menu-collapsed")) {
            document.documentElement.classList.remove("layout-menu-collapsed");
            window.dispatchEvent(new Event("resize"));
        }
    }

    modules.forEach(function (moduleButton) {

        moduleButton.addEventListener("click", function (event) {

            window.bootstrap?.Tooltip?.getInstance(moduleButton)?.hide();

            if(moduleButton.tagName === "A") return;

            selectWorld(moduleButton.dataset.navigationSection);

            if(event.detail === 0) {
                panels.find(function (panel) {
                    return panel.dataset.navigationPanel === moduleButton.dataset.navigationSection;
                })?.querySelector(".br-navigation__page")?.focus();
            }

        });

    });

    document.addEventListener("click", function (event) {

        if(!showingOtherWorldMenu) return;
        if(event.target.closest(".br-navigation__context")) return;
        if(event.target.closest(".br-navigation__module")) return;
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
    const items = Array.from(root.querySelectorAll("[data-br-search-item]"))
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

    const btn = document.getElementById("brFabFavoritesToggle");
    const panel = document.getElementById("brFabFavoritesPanel");
    const closeBtn = document.getElementById("brFabFavoritesClose");
    const backdrop = document.getElementById("brFabFavoritesBackdrop");
    const body = document.getElementById("brFabFavoritesBody");
    const count = document.getElementById("brFabFavoritesCount");
    const homeUrl = root.dataset.homeUrl;

    function setOpen(open) {

        root.setAttribute("data-open", open ? "1" : "0");
        panel.classList.toggle("br-fab-favorites__panel--open", open);
        panel.setAttribute("aria-hidden", open ? "false" : "true");
        btn.setAttribute("aria-expanded", open ? "true" : "false");
        btn.setAttribute("aria-label", open ? "Cerrar favoritos" : "Abrir favoritos");

    }

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
        const totalItems = groups.reduce(function (total, group) {
            return total + group.items.length;
        }, 0);

        count.textContent = String(totalItems);
        count.hidden = totalItems === 0;
        body.replaceChildren();

        if (totalItems === 0) {

            const empty = document.createElement("p");
            const link = document.createElement("a");

            empty.className = "br-fab-favorites__empty mb-0";
            empty.append("Aún no tienes favoritos. Puedes configurarlos desde ");
            link.href = homeUrl;
            link.className = "fw-semibold";
            link.textContent = "Favoritos";
            empty.append(link, ".");
            body.appendChild(empty);

            return;

        }

        const list = document.createElement("ul");
        list.className = "br-fab-favorites__list list-unstyled mb-0";

        groups.forEach(function (group) {

            const groupItem = document.createElement("li");
            const groupHead = document.createElement("div");
            const groupIcon = document.createElement("i");
            const groupTitle = document.createElement("span");
            const groupList = document.createElement("ul");

            groupItem.className = "br-fab-favorites__group";
            groupHead.className = "br-fab-favorites__group-head";
            groupIcon.className = (group.icon || "") + " br-fab-favorites__group-icon";
            groupIcon.setAttribute("aria-hidden", "true");
            groupTitle.textContent = group.section;
            groupList.className = "br-fab-favorites__group-items list-unstyled mb-0";
            groupHead.append(groupIcon, groupTitle);

            group.items.forEach(function (item) {

                const listItem = document.createElement("li");
                const link = document.createElement("a");
                const content = document.createElement("span");
                const label = document.createElement("span");
                const description = document.createElement("span");
                const arrow = document.createElement("i");

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

    window.addEventListener(PREFERENCES_UPDATED_EVENT, function (event) {
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

}
