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

export function initializeNavbarSearch() {

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
