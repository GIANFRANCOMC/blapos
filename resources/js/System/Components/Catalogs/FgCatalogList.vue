<template>
    <main class="fg-products">
        <header class="fg-page-header">
            <div><h1 class="fg-title">{{ title }}</h1><p class="fg-help">{{ description }}</p></div>
            <div class="fg-page-header__actions">
                <FgButton icon="fa-solid fa-plus" :disabled="initialLoading" @click="$emit('add')">{{ addLabel }}</FgButton>
                <ActionsDropdown type="button" text="Acciones" :label="`Acciones de ${plural}`" :disabled="initialLoading">
                    <li><button type="button" class="fg-menu__item" @click="$emit('import')">Importar {{ plural }}</button></li>
                    <li><button type="button" class="fg-menu__item" :disabled="exporting" @click="$emit('export')">Exportar {{ plural }}</button></li>
                </ActionsDropdown>
            </div>
        </header>

        <InitialLoadTransition>
            <CatalogListSkeleton v-if="initialLoading" :kind="kind" :label="plural"/>
            <div v-else>
                <form class="fg-filters" role="search" :aria-label="`Buscar ${plural}`" @submit.prevent="$emit('search')">
                    <FgField label="Filtrar por" v-slot="{id}">
                        <FgSelect :input-id="id" v-model="filterBy" :options="filterOptions" :searchable="false" :clearable="false" :disabled="loading"/>
                    </FgField>
                    <FgField label="Buscar" v-slot="{id}">
                        <div class="fg-field__control"><input :id="id" v-model="word" class="fg-input" type="search" :placeholder="placeholder" :disabled="loading" autocomplete="off"/></div>
                    </FgField>
                    <FgFilterActions submit :loading="loading" @clear="clearFilters"/>
                </form>

                <div class="fg-list-heading">
                    <h2 class="fg-section-title">{{ appliedWord ? `Resultados de «${appliedWord}»` : listTitle }}</h2>
                </div>

                <section class="fg-products-list" :aria-label="`Listado de ${plural}`">
                    <ResponsiveTable fg>
                        <table class="fg-table fg-table--catalog">
                            <colgroup><col v-for="column in columns" :key="column.title" :style="{width: column.width}"/></colgroup>
                            <thead><tr><th v-for="column in columns" :key="column.title" scope="col" :class="column.align">{{ column.title }}</th></tr></thead>
                            <tbody>
                                <tr v-if="loading" class="fg-table__state"><td :colspan="columns.length"><FgListLoading :label="`Buscando ${plural}…`"/></td></tr>
                                <template v-else-if="records.data?.length">
                                    <tr v-for="record in records.data" :key="record.id"><slot name="row" :record="record"/></tr>
                                </template>
                                <tr v-else class="fg-table__state">
                                    <td :colspan="columns.length">
                                        <FgEmptyState :title="emptyTitle" :description="emptyDescription">
                                            <div class="fg-empty__actions">
                                                <FgButton v-if="failed" @click="$emit('retry')">Volver a intentar</FgButton>
                                                <FgButton v-else-if="appliedWord" tone="secondary" @click="clearFilters">Limpiar filtros</FgButton>
                                                <template v-else>
                                                    <FgButton icon="fa-solid fa-plus" @click="$emit('add')">{{ addLabel }}</FgButton>
                                                    <FgButton tone="secondary" @click="$emit('import')">Importar {{ plural }}</FgButton>
                                                </template>
                                            </div>
                                        </FgEmptyState>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </ResponsiveTable>
                </section>
                <Paginator :singular="singular" :plural="plural" :aria-label="`Paginación de ${plural}`" :links="records.links" :pagination="records" :loading="loading" @click-page="$emit('page', $event)"/>
            </div>
        </InitialLoadTransition>
    </main>
</template>

<script setup>
import {computed} from "vue";
import ResponsiveTable from "@/Shared/ResponsiveTable.vue";
import ActionsDropdown from "@System/Components/Generics/ActionsDropdown.vue";
import InitialLoadTransition from "@System/Components/Generics/InitialLoadTransition.vue";
import FgButton from "@System/Components/Figma/FgButton.vue";
import FgFilterActions from "@System/Components/Figma/FgFilterActions.vue";
import FgEmptyState from "@System/Components/Figma/FgEmptyState.vue";
import FgField from "@System/Components/Figma/FgField.vue";
import FgListLoading from "@System/Components/Figma/FgListLoading.vue";
import FgSelect from "@System/Components/Figma/FgSelect.vue";
import Paginator from "@System/Components/Paginator.vue";
import {showFiltersCleared} from "@System/Helpers/Alerts.js";
import CatalogListSkeleton from "./CatalogListSkeleton.vue";
import {catalogColumns} from "./catalogColumns.js";

const props = defineProps({
    kind: {type: String, required: true},
    title: {type: String, required: true},
    description: {type: String, required: true},
    singular: {type: String, required: true},
    plural: {type: String, required: true},
    listTitle: {type: String, required: true},
    filterOptions: {type: Array, required: true},
    records: {type: Object, required: true},
    placeholder: String,
    appliedWord: String,
    initialLoading: Boolean,
    loading: Boolean,
    exporting: Boolean,
    failed: Boolean
});
defineEmits(["add", "import", "export", "search", "page", "retry"]);
const filterBy = defineModel("filterBy", {type: Object});
const word = defineModel("word", {type: String});
const columns = computed(() => catalogColumns[props.kind]);
const addLabel = computed(() => `Agregar ${props.singular}`);
const emptyTitle = computed(() => {
    if (props.failed) return "No se pudo cargar el listado";
    return props.appliedWord ? `No encontramos ${props.plural} con esta búsqueda` : `Aún no hay ${props.plural}`;
});
const emptyDescription = computed(() => {
    if (props.failed) return "Vuelve a intentar la consulta.";
    return props.appliedWord ? "Prueba con otro término o limpia los filtros y pulsa Buscar." : "Agrega un registro o importa un archivo para empezar.";
});

function clearFilters() {
    filterBy.value = props.filterOptions[0] ?? null;
    word.value = "";
    showFiltersCleared();
}
</script>
