<template>
    <section class="br-filter-bar">
        <div class="row align-items-end g-2">
            <InputSlot
                hasDiv
                :title="filterByTitle"
                :titleClass="titleClass"
                xl="3"
                lg="3">
                <template v-slot:input>
                    <v-select
                        :model-value="filterByValue"
                        @update:model-value="$emit('update:filterByValue', $event)"
                        :options="filterByOptions"
                        :class="selectClass"
                        :clearable="false"
                        :searchable="false"
                        :disabled="loading"
                        append-to-body>
                        <template #selected-option="option">
                            <span
                                class="br-select-selected-text"
                                :title="getOptionLabel(option)">
                                {{ getOptionLabel(option) }}
                            </span>
                        </template>
                        <template #option="option">
                            <span
                                class="br-select-option-text"
                                :title="getOptionLabel(option)">
                                {{ getOptionLabel(option) }}
                            </span>
                        </template>
                        <template #no-options>
                            <SelectNoOptions/>
                        </template>
                    </v-select>
                </template>
            </InputSlot>
            <InputText
                v-if="showSearchInput"
                :model-value="filterWordValue"
                @update:model-value="$emit('update:filterWordValue', $event)"
                @enterKeyPressed="$emit('search')"
                hasDiv
                :title="searchTitle"
                :titleClass="titleClass"
                :placeholder="searchPlaceholder"
                :disabled="loading"
                xl="4"
                lg="4"/>
            <slot name="extraFilters"></slot>
            <InputSlot
                hasDiv
                :isInputGroup="false"
                :divInputClass="['br-filter-bar__actions']"
                xl="5"
                lg="5">
                <template v-slot:input>
                    <button
                        v-if="showSearchButton"
                        type="button"
                        class="br-btn br-btn-sm br-btn-search waves-effect"
                        @click="$emit('search')"
                        :disabled="loading">
                        <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                        <span v-text="searchButtonText"></span>
                    </button>
                    <button
                        v-if="showAddButton"
                        type="button"
                        class="br-btn br-btn-sm br-btn-action-open-create waves-effect"
                        @click="$emit('add')"
                        :disabled="loading">
                        <i class="fa-solid fa-plus" aria-hidden="true"></i>
                        <span v-text="addButtonText"></span>
                    </button>
                    <button
                        v-if="groupSecondaryActions && (showImportButton || showDownloadButton || showLabelsButton)"
                        type="button"
                        class="br-btn br-btn-sm br-btn-actions"
                        data-bs-toggle="dropdown"
                        aria-expanded="false"
                        :disabled="loading">
                        <span v-text="secondaryActionsLabel"></span>
                        <i class="fa-solid fa-chevron-down br-btn-actions__chevron" aria-hidden="true"></i>
                    </button>
                    <ul v-if="groupSecondaryActions && (showImportButton || showDownloadButton || showLabelsButton)" class="dropdown-menu dropdown-menu-end br-btn-actions-menu">
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
                        <li v-if="showLabelsButton">
                            <button type="button" class="dropdown-item" :disabled="loading || labelsLoading" @click="$emit('labels')">
                                <i class="fa-solid fa-tags" aria-hidden="true"></i>
                                <span v-text="labelsButtonText"></span>
                            </button>
                        </li>
                    </ul>
                    <button
                        v-if="showImportButton && !groupSecondaryActions"
                        type="button"
                        class="br-btn br-btn-sm br-btn-action-import waves-effect"
                        @click="$emit('import')"
                        :disabled="loading || importing"
                        data-bs-toggle="tooltip"
                        data-bs-placement="top"
                        :title="importButtonTooltip"
                        :aria-label="importButtonTooltip">
                        <i class="fa-solid fa-file-arrow-up" aria-hidden="true"></i>
                        <span class="br-btn-action-import__label" v-text="importButtonText"></span>
                    </button>
                    <button
                        v-if="showDownloadButton && !groupSecondaryActions"
                        type="button"
                        :class="[
                            'br-btn',
                            'br-btn-sm',
                            downloadButtonClass,
                            'waves-effect',
                            {'br-btn-action-export--desktop-icon': downloadIconOnlyOnDesktop}
                        ]"
                        @click="$emit('download')"
                        :disabled="loading || downloading"
                        data-bs-toggle="tooltip"
                        data-bs-placement="top"
                        :title="downloadButtonTooltip"
                        :aria-label="downloadButtonTooltip">
                        <i :class="downloadButtonIcon" aria-hidden="true"></i>
                        <span class="br-btn-action-export__label" v-text="downloadButtonText"></span>
                    </button>
                    <button
                        v-if="showLabelsButton && !groupSecondaryActions"
                        type="button"
                        class="br-btn br-btn-sm br-btn-action-print waves-effect"
                        @click="$emit('labels')"
                        :disabled="loading || labelsLoading"
                        data-bs-toggle="tooltip"
                        data-bs-placement="top"
                        :title="labelsButtonTooltip"
                        :aria-label="labelsButtonTooltip">
                        <i class="fa-solid fa-tags" aria-hidden="true"></i>
                        <span class="br-btn-action-print__label" v-text="labelsButtonText"></span>
                    </button>
                </template>
            </InputSlot>
        </div>
    </section>
</template>

<script>
import SelectNoOptions from "@System/Components/Generics/SelectNoOptions.vue";

export default {
    name: "FiltersSection",
    components: {
        SelectNoOptions
    },
    emits: ["search", "add", "import", "download", "labels", "update:filterByValue", "update:filterWordValue"],
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
        searchButtonText: {
            type: String,
            required: false,
            default: "Buscar"
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
        showLabelsButton: {
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
            default: "br-btn-action-export"
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
        labelsButtonText: {
            type: String,
            required: false,
            default: "Etiquetas"
        },
        labelsButtonTooltip: {
            type: String,
            required: false,
            default: "Imprimir etiquetas"
        },
        labelsLoading: {
            type: Boolean,
            required: false,
            default: false
        },
        titleClass: {
            type: Array,
            required: false,
            default: () => []
        },
        selectClass: {
            type: String,
            required: false,
            default: "form-select form-select-sm"
        }
    },
    methods: {
        getOptionLabel(option) {

            return option?.label ?? option?.name ?? option?.value ?? "";

        }
    }
};
</script>

<style scoped>
</style>

