<template>
    <main :class="['br-home', {'br-home--compact': !showDescriptions}]">
        <Breadcrumb :list="breadcrumbItems"/>

        <header class="br-home__header">
            <div class="br-home__filters">
                <div class="br-home-search">
                    <i class="fa-solid fa-magnifying-glass br-home-search__icon" aria-hidden="true"></i>
                    <input
                        id="homeModuleSearch"
                        v-model.trim="searchTerm"
                        type="search"
                        class="form-control br-home-search__input"
                        :placeholder="page.filters.search.placeholder"
                        :aria-label="page.filters.search.ariaLabel">
                    <button
                        v-if="searchTerm"
                        type="button"
                        class="br-home-search__clear"
                        data-bs-toggle="tooltip"
                        data-bs-placement="top"
                        :title="page.filters.search.clearTooltip"
                        :aria-label="page.filters.search.clearTooltip"
                        @click="clearSearch">
                        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                    </button>
                </div>

                <div class="form-check form-switch br-home__filter">
                    <input
                        id="toggleFavorites"
                        class="form-check-input"
                        type="checkbox"
                        v-model="forms.entity.createUpdate.config.show_only_favorites"
                        :disabled="isSaving"
                        data-bs-toggle="tooltip"
                        data-bs-placement="bottom"
                        :title="page.filters.favorites.tooltip"
                        @change="saveGlobalPreferences">
                    <label for="toggleFavorites" class="form-check-label">
                        {{ page.filters.favorites.label }}
                    </label>
                </div>

                <div class="form-check form-switch br-home__filter">
                    <input
                        id="toggleDescriptions"
                        class="form-check-input"
                        type="checkbox"
                        v-model="forms.entity.createUpdate.config.show_descriptions"
                        :disabled="isSaving"
                        data-bs-toggle="tooltip"
                        data-bs-placement="bottom"
                        :title="page.filters.descriptions.tooltip"
                        @change="saveGlobalPreferences">
                    <label for="toggleDescriptions" class="form-check-label">
                        {{ page.filters.descriptions.label }}
                    </label>
                </div>
            </div>
        </header>

        <section
            v-if="sections.length === 0"
            class="br-home__empty">
            <WithoutData type="image" :text="emptyStateText"/>
        </section>

        <section v-else class="br-home__grid" :aria-label="page.modulesAriaLabel">
            <article v-for="section in sections" :key="section.id" class="br-home-section">
                <header class="br-home-section__header">
                    <i :class="[section.dom_icon, 'br-home-section__icon']" aria-hidden="true"></i>
                    <h2 class="br-home-section__title">{{ section.dom_label }}</h2>
                </header>

                <nav class="br-home-section__links" :aria-label="section.dom_label">
                    <div
                        class="br-home-access"
                        v-for="subSection in section.sub_sections"
                        :key="subSection.id">
                        <a class="br-home-access__link" :href="subSection.dom_route_url">
                            <span class="br-home-access__content">
                                <span class="br-home-access__label">{{ subSection.dom_label }}</span>
                                <span v-if="showDescriptions && subSection.description" class="br-home-access__description">
                                    {{ subSection.description }}
                                </span>
                            </span>
                        </a>

                        <button
                            :key="`${subSection.id}-${isFavorite(subSection) ? 'favorite' : 'available'}`"
                            type="button"
                            :class="['br-home-favorite', {'is-active': isFavorite(subSection)}]"
                            :disabled="isSaving"
                            data-bs-toggle="tooltip"
                            data-bs-placement="top"
                            :title="favoriteTooltip(subSection)"
                            :aria-label="favoriteTooltip(subSection)"
                            @click="changeFavorite(section, subSection, $event)">
                            <i
                                :class="isFavorite(subSection) ? 'fa-solid fa-star' : 'fa-regular fa-star'"
                                aria-hidden="true"></i>
                        </button>
                    </div>
                </nav>
            </article>
        </section>
    </main>
</template>

<script>
import {markRaw} from "vue";
import * as Alerts from "@System/Helpers/Alerts.js";
import * as Constants from "@System/Helpers/Constants.js";
import * as Requests from "@System/Helpers/Requests.js";
import * as Utils from "@System/Helpers/Utils.js";

const PREFERENCES_UPDATED_EVENT = "br:preferences-updated";
const HIDDEN_HOME_DIRECTORY_SLUGS = new Set(["sc_home", "sc_dashboard"]);

const PAGE_CONFIG = {
    breadcrumb: [
        {title: "Administración"},
        {title: "Ajustes"},
        {title: "Menú y favoritos", active: true}
    ],
    modulesAriaLabel: "Módulos disponibles",
    active: true,
    menu: {
        id: "menu-parent-configuration"
    },
    filters: {
        search: {
            placeholder: "Buscar módulo o acceso",
            ariaLabel: "Buscar entre los módulos disponibles",
            clearTooltip: "Limpiar búsqueda"
        },
        favorites: {
            label: "Solo favoritos",
            tooltip: "Mostrar únicamente los accesos marcados como favoritos"
        },
        descriptions: {
            label: "Mostrar descripción",
            tooltip: "Mostrar u ocultar la descripción breve de cada acceso"
        }
    },
    empty: {
        favorites: "Aún no tienes accesos favoritos.",
        search: "No encontramos módulos que coincidan con tu búsqueda.",
        modules: "No hay módulos disponibles para tu empresa."
    },
    favorites: {
        addTooltip: "Agregar a favoritos",
        removeTooltip: "Quitar de favoritos"
    },
    confirmations: {
        add: {
            title: "Agregar a favoritos",
            message: "Se añadirá al menú de favoritos y podrás abrirlo rápidamente desde cualquier pantalla.",
            confirmText: "Agregar favorito"
        },
        remove: {
            title: "Quitar de favoritos",
            message: "Dejará de aparecer en favoritos, pero seguirá disponible en el menú principal.",
            confirmText: "Quitar favorito"
        },
        cancelText: "Cancelar"
    }
};

function createForms() {

    return {
        entity: {
            createUpdate: {
                config: {
                    show_only_favorites: false,
                    show_descriptions: true
                },
                errors: {}
            }
        }
    };

}

function normalizeSearchValue(value) {

    return String(value ?? "")
        .normalize("NFD")
        .replace(/[\u0300-\u036f]/g, "")
        .toLowerCase();

}

function createConfirmationContent(section, subSection, confirmation) {

    const container = document.createElement("div");
    const reference = document.createElement("p");
    const referenceLabel = document.createElement("span");
    const referenceValue = document.createElement("strong");
    const message = document.createElement("p");

    container.className = "br-home-confirmation__content";
    reference.className = "br-home-confirmation__reference";
    referenceLabel.className = "br-home-confirmation__reference-label";
    referenceValue.className = "br-home-confirmation__reference-value";
    message.className = "br-home-confirmation__message";

    referenceLabel.textContent = section?.has_sub_menu ? "Módulo y acceso" : "Módulo";
    referenceValue.textContent = section?.has_sub_menu
        ? `${section.dom_label} > ${subSection.dom_label}`
        : subSection.dom_label;
    message.textContent = confirmation.message;

    reference.append(referenceLabel, referenceValue);
    container.append(reference, message);

    return container;

}

export default {
    async mounted() {

        Utils.navbarItem(this.page.menu.id, {});
        Alerts.swals({type: "initParams"});

        const initParams = await this.initParams({});

        this.restoreGlobalPreferences();
        this.refreshTooltips();

        if(initParams) {

            Alerts.swals({show: false});

        }

    },
    beforeUnmount() {

        Alerts.tooltips({show: false});

    },
    data() {

        return {
            forms: createForms(),
            isSaving: false,
            searchTerm: "",
            config: {
                ...Constants.generalConfig,
                entity: {
                    ...Requests.config({entity: "home"}),
                    page: markRaw(PAGE_CONFIG)
                }
            }
        };
    },
    methods: {
        async initParams({}) {

            const initParams = await Requests.get({
                route: this.config.entity.routes.initParams,
                data: {page: "main"},
                showAlert: true
            });

            return Requests.valid({result: initParams});

        },
        restoreGlobalPreferences() {

            this.forms.entity.createUpdate.config.show_only_favorites =
                this.preferenceCompaniesSubSection?.show_only_favorites ?? false;
            this.forms.entity.createUpdate.config.show_descriptions =
                this.preferenceCompaniesSubSection?.show_descriptions ?? true;

        },
        refreshTooltips() {

            this.$nextTick(() => Alerts.tooltips({}));

        },
        isFavorite(subSection) {

            return this.favoriteSubSectionIdSet.has(Number(subSection?.id));

        },
        isVisibleHomeDirectorySection(section) {

            return !HIDDEN_HOME_DIRECTORY_SLUGS.has(String(section?.slug ?? ""));

        },
        isVisibleHomeDirectorySubSection(subSection) {

            return !HIDDEN_HOME_DIRECTORY_SLUGS.has(String(subSection?.slug ?? ""));

        },
        favoriteTooltip(subSection) {

            return this.isFavorite(subSection)
                ? this.page.favorites.removeTooltip
                : this.page.favorites.addTooltip;

        },
        clearSearch() {

            this.searchTerm = "";
            this.refreshTooltips();

        },
        async persistPreferences(data, subSectionId = 0) {

            this.isSaving = true;

            try {

                const payload = Utils.cloneJson({
                    show_actions: false,
                    show_only_favorites: this.forms.entity.createUpdate.config.show_only_favorites,
                    show_descriptions: this.forms.entity.createUpdate.config.show_descriptions,
                    ...data
                });
                const isGlobalPreference = Number(subSectionId) === 0;
                const result = await Requests.patch({
                    route: isGlobalPreference
                        ? `${this.config.entity.routes.update}/0`
                        : this.config.entity.routes.update,
                    data: payload,
                    id: isGlobalPreference ? "" : subSectionId
                });
                const isValid = Requests.valid({result});

                if(isValid) {

                    const responsePreferences = this.resolvePreferencesFromResult(result);
                    const preferences = this.buildUpdatedPreferences(
                        responsePreferences ?? this.config.essential.preferences,
                        payload,
                        subSectionId
                    );

                    this.applyPreferences(preferences);

                }else {

                    this.restoreGlobalPreferences();

                }

                return isValid;

            }catch(error) {

                this.restoreGlobalPreferences();
                console.error("No fue posible guardar las preferencias de Home.", error);

                return false;

            }finally {

                this.isSaving = false;
                this.refreshTooltips();

            }

        },
        resolvePreferencesFromResult(result) {

            return result?.data?.data?.preferences
                ?? result?.data?.preferences
                ?? result?.data?.data?.data?.preferences
                ?? null;

        },
        buildUpdatedPreferences(preferences, payload, subSectionId = 0) {

            const nextPreferences = Utils.cloneJson(preferences || {});
            const slug = "config_companies_sub_sections";
            const current = nextPreferences[slug] || {};
            const subSections = Array.isArray(current.sub_sections)
                ? current.sub_sections
                : [];

            nextPreferences[slug] = {
                ...current,
                show_actions: Boolean(payload.show_actions ?? current.show_actions ?? false),
                show_only_favorites: Boolean(payload.show_only_favorites ?? current.show_only_favorites ?? false),
                show_descriptions: Boolean(payload.show_descriptions ?? current.show_descriptions ?? true),
                sub_sections: subSections
            };

            if(subSectionId > 0 && Object.prototype.hasOwnProperty.call(payload, "is_favorite")) {

                const normalizedId = Number(subSectionId);
                const index = subSections.findIndex(preference =>
                    Number(preference?.sub_section_id) === normalizedId
                );
                const previous = index >= 0 ? subSections[index] : {};
                const nextRecord = {
                    ...previous,
                    sub_section_id: normalizedId,
                    visible_in_menu: previous?.visible_in_menu ?? true,
                    is_favorite: Boolean(payload.is_favorite)
                };

                if(index >= 0) {

                    subSections.splice(index, 1, nextRecord);

                }else {

                    subSections.push(nextRecord);

                }

            }

            return nextPreferences;

        },
        applyPreferences(preferences) {

            this.config.essential = {
                ...this.config.essential,
                preferences
            };
            window.preferences = preferences;

            window.dispatchEvent(new CustomEvent(PREFERENCES_UPDATED_EVENT, {
                detail: {preferences}
            }));

        },
        async saveGlobalPreferences() {

            await this.persistPreferences({});

        },
        async changeFavorite(section, subSection, event) {

            Alerts.tooltips({show: false});
            event?.currentTarget?.blur();

            const shouldFavorite = !this.isFavorite(subSection);
            const confirmation = shouldFavorite
                ? this.page.confirmations.add
                : this.page.confirmations.remove;

            const result = await Swal.fire({
                title: confirmation.title,
                html: createConfirmationContent(section, subSection, confirmation),
                icon: shouldFavorite ? "question" : "warning",
                allowOutsideClick: false,
                allowEnterKey: false,
                showCancelButton: true,
                buttonsStyling: false,
                confirmButtonText: confirmation.confirmText,
                cancelButtonText: this.page.confirmations.cancelText,
                customClass: {
                    popup: `br-home-confirmation br-home-confirmation--${shouldFavorite ? "question" : "warning"}`,
                    confirmButton: shouldFavorite
                        ? "br-btn br-btn-secondary"
                        : "br-btn br-btn-danger",
                    cancelButton: "br-btn br-btn-outline-secondary ms-2"
                }
            });

            if(!result.isConfirmed) {

                this.$nextTick(() => Alerts.tooltips({time: 250}));
                return;

            }

            await this.persistPreferences(
                {is_favorite: shouldFavorite},
                Number(subSection.id)
            );

        }
    },
    computed: {
        page() {

            return this.config.entity.page;

        },
        preferenceCompaniesSubSection() {

            return this.config?.essential?.preferences?.config_companies_sub_sections;

        },
        availableSubSectionIdSet() {

            return new Set(
                (this.config?.essential?.sections ?? [])
                    .filter(section => this.isVisibleHomeDirectorySection(section))
                    .flatMap(section =>
                        (section.sub_sections ?? [])
                            .filter(subSection => this.isVisibleHomeDirectorySubSection(subSection))
                            .map(subSection => Number(subSection.id))
                    )
            );

        },
        favoriteSubSectionIdSet() {

            return new Set(
                (this.preferenceCompaniesSubSection?.sub_sections ?? [])
                    .filter(preference => preference?.is_favorite)
                    .map(preference => Number(preference.sub_section_id))
                    .filter(id => this.availableSubSectionIdSet.has(id))
            );

        },
        sections() {

            const sections = (this.config?.essential?.sections ?? [])
                .filter(section => this.isVisibleHomeDirectorySection(section));
            const query = normalizeSearchValue(this.searchTerm);
            const onlyFavorites = this.forms.entity.createUpdate.config.show_only_favorites;

            return sections
                .map(section => {

                    const sectionMatches = normalizeSearchValue(section?.dom_label).includes(query);
                    const subSections = (section.sub_sections ?? [])
                        .filter(subSection => this.isVisibleHomeDirectorySubSection(subSection))
                        .filter(subSection => {

                        const matchesFavorite = !onlyFavorites || this.isFavorite(subSection);
                        const matchesSearch = !query
                            || sectionMatches
                            || normalizeSearchValue(subSection?.dom_label).includes(query)
                            || normalizeSearchValue(subSection?.description).includes(query);

                        return matchesFavorite && matchesSearch;

                        });

                    return {...section, sub_sections: subSections};

                })
                .filter(section => section.sub_sections.length > 0);

        },
        emptyStateText() {

            if(this.searchTerm) {

                return this.page.empty.search;

            }

            if(this.forms.entity.createUpdate.config.show_only_favorites) {

                return this.page.empty.favorites;

            }

            return this.page.empty.modules;

        },
        showDescriptions() {

            return this.forms.entity.createUpdate.config.show_descriptions;

        },
        breadcrumbItems() {

            return this.page.breadcrumb;

        }
    },
    watch: {
        searchTerm() {

            this.refreshTooltips();

        }
    }
};
</script>
