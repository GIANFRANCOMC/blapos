<template>
    <section class="fg-filter-bar fg-filter-bar--compact" aria-label="Filtros">
        <div class="row align-items-end g-2">
            <div class="col-12 col-sm-5 col-md-4 col-lg-3">
                <FgField :label="filterByTitle" v-slot="{id}">
                    <FgSelect
                        :input-id="id"
                        :model-value="filterByValue"
                        @update:model-value="$emit('update:filterByValue', $event)"
                        :options="filterByOptions"
                        :class="selectClass"
                        :clearable="false"
                        :searchable="false"
                        :disabled="loading">
                        <template #selected-option="option">
                            <span class="fg-select-selected-text" :title="getOptionLabel(option)">{{ getOptionLabel(option) }}</span>
                        </template>
                        <template #option="option">
                            <span class="fg-select-option-text" :title="getOptionLabel(option)">{{ getOptionLabel(option) }}</span>
                        </template>
                        <template #no-options>
                            <SelectNoOptions/>
                        </template>
                    </FgSelect>
                </FgField>
            </div>
            <div v-if="showSearchInput" class="col-12 col-sm">
                <SearchInput
                    :model-value="filterWordValue"
                    @update:model-value="$emit('update:filterWordValue', $event)"
                    :title="searchTitle"
                    :placeholder="searchPlaceholder"
                    :disabled="loading"/>
            </div>
            <slot name="extraFilters"></slot>
            <div class="col-12 col-lg-auto">
                <div class="fg-filter-bar__actions">
                    <SearchButton
                        v-if="showSearchButton"
                        class="fg-button--small"
                        @click="$emit('search')"
                        :disabled="loading"/>
                    <ClearFiltersButton v-if="showClearButton" :disabled="loading" @clear="clearFilters"/>
                    <button
                        v-if="showAddButton"
                        type="button"
                        class="fg-button fg-button--small waves-effect"
                        @click="$emit('add')"
                        :disabled="loading">
                        <i class="fa-solid fa-plus" aria-hidden="true"></i>
                        <span v-text="addButtonText"></span>
                    </button>
                    <ActionsDropdown
                        v-if="groupSecondaryActions && (showImportButton || showDownloadButton)"
                        :type="secondaryActionsType"
                        :text="secondaryActionsLabel"
                        :disabled="loading">
                        <li v-if="showImportButton">
                            <button type="button" class="dropdown-item" :disabled="loading || importing" @click="$emit('import')">
                                <i class="fa-solid fa-file-arrow-up" aria-hidden="true"></i>
                                <span v-text="importButtonText"></span>
                            </button>
                        </li>
                        <li v-if="showDownloadButton">
                            <button type="button" class="dropdown-item" :disabled="loading || downloading" @click="$emit('download')">
                                <i :class="downloadButtonIcon" aria-hidden="true"></i>
                                <span v-text="downloadButtonText"></span>
                            </button>
                        </li>
                    </ActionsDropdown>
                    <button
                        v-if="showImportButton && !groupSecondaryActions"
                        type="button"
                        class="fg-button fg-button--small fg-button--secondary waves-effect"
                        @click="$emit('import')"
                        :disabled="loading || importing"
                        data-bs-toggle="tooltip"
                        data-bs-placement="top"
                        :title="importButtonTooltip"
                        :aria-label="importButtonTooltip">
                        <i class="fa-solid fa-file-arrow-up" aria-hidden="true"></i>
                        <span class="fg-button__label" v-text="importButtonText"></span>
                    </button>
                    <button
                        v-if="showDownloadButton && !groupSecondaryActions"
                        type="button"
                        :class="[
                            'fg-button',
                            'fg-button--small',
                            downloadButtonClass,
                            'waves-effect',
                            {'fg-button--desktop-icon': downloadIconOnlyOnDesktop}
                        ]"
                        @click="$emit('download')"
                        :disabled="loading || downloading"
                        data-bs-toggle="tooltip"
                        data-bs-placement="top"
                        :title="downloadButtonTooltip"
                        :aria-label="downloadButtonTooltip">
                        <i :class="downloadButtonIcon" aria-hidden="true"></i>
                        <span class="fg-button__label" v-text="downloadButtonText"></span>
                    </button>
                    <slot name="secondaryActions"></slot>
                </div>
            </div>
        </div>
    </section>
</template>

<script>
import FgField from "@System/Components/Figma/FgField.vue";
import FgSelect from "@System/Components/Figma/FgSelect.vue";
import SearchButton from "@/Shared/SearchButton.vue";
import ActionsDropdown from "./ActionsDropdown.vue";
import ClearFiltersButton from "./ClearFiltersButton.vue";
import {showFiltersCleared} from "@System/Helpers/Alerts.js";
import SelectNoOptions from "@System/Components/Generics/SelectNoOptions.vue";
import SearchInput from "@System/Components/Generics/SearchInput.vue";

export default {
    name: "FiltersSection",
    components: {
        ActionsDropdown,
        FgField,
        FgSelect,
        ClearFiltersButton,
        SearchButton,
        SelectNoOptions,
        SearchInput
    },
    emits: ["search", "add", "import", "download", "update:filterByValue", "update:filterWordValue"],
    props: {
        filterByValue: {
            type: Object,
            required: true
        },
        filterWordValue: {
            type: String,
            required: true,
            default: ""
        },
        filterByOptions: {
            type: Array,
            required: true,
            default: []
        },
        searchPlaceholder: {
            type: String,
            required: false,
            default: "Buscar..."
        },
        loading: {
            type: Boolean,
            required: false,
            default: false
        },
        filterByTitle: {
            type: String,
            required: false,
            default: "Filtrar por"
        },
        searchTitle: {
            type: String,
            required: false,
            default: "Búsqueda"
        },
        showSearchInput: {
            type: Boolean,
            required: false,
            default: true
        },
        showSearchButton: {
            type: Boolean,
            required: false,
            default: true
        },
        showClearButton: Boolean,
        addButtonText: {
            type: String,
            required: false,
            default: "Agregar"
        },
        showAddButton: {
            type: Boolean,
            required: false,
            default: true
        },
        showDownloadButton: {
            type: Boolean,
            required: false,
            default: false
        },
        showImportButton: {
            type: Boolean,
            required: false,
            default: false
        },
        groupSecondaryActions: {
            type: Boolean,
            default: false
        },
        secondaryActionsLabel: {
            type: String,
            default: "Acciones"
        },
        secondaryActionsType: {
            type: String,
            default: "icon",
            validator: value => ["icon", "button"].includes(value)
        },
        importButtonText: {
            type: String,
            required: false,
            default: "Carga masiva"
        },
        importButtonTooltip: {
            type: String,
            required: false,
            default: "Carga masiva"
        },
        importing: {
            type: Boolean,
            required: false,
            default: false
        },
        downloadButtonText: {
            type: String,
            required: false,
            default: "Descargar Excel"
        },
        downloadButtonIcon: {
            type: String,
            required: false,
            default: "fa-solid fa-file-excel"
        },
        downloadButtonClass: {
            type: String,
            required: false,
            default: "fg-button--secondary"
        },
        downloading: {
            type: Boolean,
            required: false,
            default: false
        },
        downloadIconOnlyOnDesktop: {
            type: Boolean,
            required: false,
            default: false
        },
        downloadButtonTooltip: {
            type: String,
            required: false,
            default: "Descargar Excel"
        },
        titleClass: {
            type: Array,
            required: false,
            default: () => []
        },
        selectClass: {
            type: String,
            required: false,
            default: "form-select"
        }
    },
    methods: {
        clearFilters() {
            this.$emit("update:filterByValue", this.filterByOptions[0] ?? null);
            this.$emit("update:filterWordValue", "");
            showFiltersCleared();
        },
        getOptionLabel(option) {

            return option?.label ?? option?.name ?? option?.value ?? "";

        }
    }
};
</script>
