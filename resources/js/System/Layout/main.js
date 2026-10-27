// Comportamientos compartidos del layout del sistema.

import {createTooltip} from "../Helpers/Alerts.js";
import {writeClipboard} from "../Helpers/Clipboard.js";

const copyResetTimers = new WeakMap();

document.addEventListener("click", async function (event) {

    const button = event.target.closest("[data-br-copy-value]");

    if(!button) return;

    try {

        await writeClipboard(button.dataset.brCopyValue);
        createTooltip(button, "Copiado")?.show();

    }catch(error) {

        createTooltip(button, "No se pudo copiar")?.show();

    }

    window.clearTimeout(copyResetTimers.get(button));
    copyResetTimers.set(button, window.setTimeout(function () {

        createTooltip(button, "Copiar");

    }, 1400));

});

function confirmLogout() {

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

}

document.querySelectorAll(".br-navigation__logout, .br-navbar-user__logout").forEach(function (button) {

    button.addEventListener("click", confirmLogout);

});

(function () {

    var root = document.getElementById("brFabFavorites");

    if (!root) return;

    var btn = document.getElementById("brFabFavoritesToggle");
    var panel = document.getElementById("brFabFavoritesPanel");
    var closeBtn = document.getElementById("brFabFavoritesClose");
    var backdrop = document.getElementById("brFabFavoritesBackdrop");
    var body = document.getElementById("brFabFavoritesBody");
    var count = document.getElementById("brFabFavoritesCount");
    var homeUrl = root.dataset.homeUrl;

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
        count.hidden = totalItems === 0;
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

(function () {

    var navigation = document.getElementById("layout-menu");

    if(!navigation) {

        return;

    }

    var modules = Array.from(navigation.querySelectorAll(".br-navigation__module"));
    var panels = Array.from(navigation.querySelectorAll("[data-navigation-panel]"));
    var currentSectionId = document.documentElement.dataset.navigationSectionId || "";
    var showingOtherWorldMenu = false;

    if(window.bootstrap?.Tooltip) {

        navigation.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (element) {

            createTooltip(element, element.getAttribute("title") || "");

        });

    }

    function selectWorld(sectionId) {

        var selectedModule = modules.find(function (moduleButton) {
            return moduleButton.dataset.navigationSection === sectionId;
        });

        if(!selectedModule) return;

        var hasSubmenu = Number(selectedModule.dataset.navigationCount) > 1;

        showingOtherWorldMenu = hasSubmenu && sectionId !== currentSectionId;

        modules.forEach(function (moduleButton) {

            var selected = moduleButton === selectedModule;

            moduleButton.classList.remove("active", "open");
            moduleButton.classList.toggle("is-active", selected);
            if(moduleButton.tagName === "BUTTON") {
                moduleButton.setAttribute("aria-pressed", selected ? "true" : "false");
            }

        });

        var alreadyVisible = panels.some(function (panel) {
            return !panel.hidden && panel.dataset.navigationPanel === sectionId;
        });

        panels.forEach(function (panel) {
            var visible = hasSubmenu && panel.dataset.navigationPanel === sectionId;
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

            if(showingOtherWorldMenu) return;

            var target = mutation.target;

            if(!target.classList.contains("active") && !target.classList.contains("open")) {

                return;

            }

            var sectionId = sectionIdFor(target);

            if(!sectionId) {

                return;

            }

            modules.forEach(function (moduleLink) {

                var isActive = moduleLink.dataset.navigationSection === sectionId;
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

})();
