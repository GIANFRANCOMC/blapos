<template>
    <nav
        v-if="shouldShowPaginator"
        class="br-pagination mt-2"
        role="navigation"
        aria-label="Navegación de páginas">
        <ul class="pagination br-pagination__list d-flex flex-wrap justify-content-center align-items-center mb-0">
            <template v-for="(link, index) in displayLinks" :key="itemKey(link, index)">
                <li
                    class="page-item br-pagination__item"
                    :class="{ active: link.active, disabled: !link.url, 'br-pagination__item--boundary': ['first', 'last'].includes(link.kind) }">
                    <button
                        type="button"
                        class="page-link br-pagination__link"
                        :aria-label="ariaLabel(link, index)"
                        :aria-current="link.active ? 'page' : undefined"
                        :aria-disabled="ariaDisabled(link) ? 'true' : undefined"
                        :disabled="!navigable(link)"
                        @click="onPageClick(link)"
                        v-text="displayLabel(link, index)"></button>
                </li>
            </template>
        </ul>
    </nav>
</template>

<script>
export default {
    name: "Paginator",
    emits: ["clickPage"],
    props: {
        links: {
            type: Array,
            required: false,
            default: () => []
        }
    },
    watch: {
        links: {
            handler(list) {
                if(list != null && !Array.isArray(list)) {
                    console.warn("[Paginator] la prop \"links\" debe ser un array");
                }
            },
            immediate: true,
            deep: true
        }
    },
    computed: {
        displayLinks() {
            return this.buildWindowedLinks(this.links);
        },
        shouldShowPaginator() {
            if(!Array.isArray(this.links) || this.links.length === 0) return false;

            return this.displayLinks.some(link => link.active || this.parsePageNumber(link.label) !== null);
        }
    },
    methods: {
        stripHtml(value) {
            return String(value).replace(/<[^>]+>/g, "").trim();
        },
        parsePageNumber(label) {
            if(label == null) return null;

            const text = this.stripHtml(label);

            if(text === "..." || text === "…") return null;

            const n = parseInt(text, 10);

            return Number.isFinite(n) ? n : null;
        },
        isPreviousLink(index) {
            return index === 0;
        },
        isNextLink(index) {
            return index === this.displayLinks.length - 1;
        },
        displayLabel(link, index) {
            if(link?.kind === "first") return "Primera";
            if(link?.kind === "last") return "Última";

            const pageNumber = this.parsePageNumber(link?.label);

            if(pageNumber !== null) return String(pageNumber);
            if(link?.kind === "previous" || this.isPreviousLink(index)) return "Anterior";
            if(link?.kind === "next" || this.isNextLink(index)) return "Siguiente";

            return this.stripHtml(link?.label ?? "...");
        },
        ariaLabel(link, index) {
            const label = this.displayLabel(link, index);

            if(link?.kind === "first") return this.ariaDisabled(link) ? "Primera página no disponible" : "Ir a la primera página";
            if(link?.kind === "last") return this.ariaDisabled(link) ? `Última página, ${link.lastPage}, no disponible` : `Ir a la última página, ${link.lastPage}`;
            if(link?.active) return `Página actual, ${label}`;
            if(this.ariaDisabled(link)) return `${label} no disponible`;

            return `Ir a ${label.toLowerCase()}`;
        },
        buildPageUrl(baseUrl, page) {
            if(!baseUrl) return null;

            try {
                const u = new URL(baseUrl, window.location.origin);

                u.searchParams.set("page", String(page));

                return `${u.pathname}${u.search}${u.hash}`;
            } catch {
                return baseUrl;
            }
        },
        buildWindowedLinks(raw) {
            if(!Array.isArray(raw) || raw.length === 0) return [];

            if(raw.length < 3) return raw;

            // Anterior/Siguiente aparte; solo páginas numéricas.
            const windowSize = 5;
            const prevLink = raw[0];
            const nextLink = raw[raw.length - 1];
            const middle = raw.slice(1, -1);
            const templateUrl = raw.find(item => item.url)?.url ?? null;

            const pageMap = new Map();
            let lastPage = 1;

            for(const item of middle) {
                const n = this.parsePageNumber(item.label);

                if(n === null) continue;

                pageMap.set(n, item);
                lastPage = Math.max(lastPage, n);
            }

            if(pageMap.size === 0) return raw;

            const activeItem = middle.find(item => item.active);
            let current = activeItem ? this.parsePageNumber(activeItem.label) : null;

            if(current === null) current = pageMap.keys().next().value ?? 1;

            const half = Math.floor(windowSize / 2);
            let start = Math.max(1, Math.min(current - half, lastPage - windowSize + 1));
            let end = Math.min(lastPage, start + windowSize - 1);

            if(end - start < windowSize - 1) start = Math.max(1, end - windowSize + 1);

            const windowLinks = [];

            for(let p = start; p <= end; p++) {
                const existing = pageMap.get(p);

                if(existing) {
                    windowLinks.push({...existing, active: p === current});
                } else {
                    windowLinks.push({
                        url: this.buildPageUrl(templateUrl, p),
                        label: String(p),
                        active: p === current
                    });
                }
            }

            return [
                {kind: "first", label: "Primera", url: current > 1 ? this.buildPageUrl(templateUrl, 1) : null, active: false},
                {...prevLink, kind: "previous"},
                ...windowLinks,
                {...nextLink, kind: "next"},
                {kind: "last", label: "Última", lastPage, url: current < lastPage ? this.buildPageUrl(templateUrl, lastPage) : null, active: false}
            ];
        },
        itemKey(link, index) {
            const label = typeof link?.label === "string" ? this.stripHtml(link.label) : index;

            return `${index}-${link?.url ?? "no-url"}-${label}`;
        },
        navigable(link) {
            return !!(link?.url && !link.active);
        },
        ariaDisabled(link) {
            return !link?.active && !link?.url;
        },
        onPageClick(link) {
            if(!this.navigable(link)) return;

            this.$emit("clickPage", {url: link.url});
        }
    }
};
</script>
