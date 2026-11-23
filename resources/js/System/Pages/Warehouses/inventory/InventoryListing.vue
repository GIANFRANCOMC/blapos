<template>
    <main class="fg-products">
        <header class="fg-page-header">
            <div><h1 class="fg-title">{{ view.title }}</h1><p class="fg-help">{{ view.description }}</p></div>
            <div class="fg-page-header__actions">
                <slot name="actions"/>
                <ActionsDropdown type="button" :label="`Acciones de ${view.title.toLocaleLowerCase('es')}`" :disabled="initialLoading || searchDisabled">
                    <li><button type="button" class="fg-menu__item" :disabled="exportDisabled" @click="$emit('export')">Descargar Excel</button></li>
                </ActionsDropdown>
                <slot name="tools"/>
            </div>
        </header>
        <p v-if="initialLoading" class="fg-sr-only" role="status">Preparando {{ view.title.toLocaleLowerCase('es') }}…</p>
        <InitialLoadTransition>
            <InventorySkeleton v-if="initialLoading" :fields="fields" :view="view"/>
            <div v-else>
                <section class="fg-filters fg-filter-grid" :aria-label="`Filtros de ${view.title.toLocaleLowerCase('es')}`">
                    <div v-for="field in fields" :key="field.key" class="fg-filter-grid__field" :class="{'fg-filter-grid__field--compact': field.compact}">
                        <SearchInput v-if="field.kind === 'search'" :ref="field.key === 'productSearch' ? setProductSearch : undefined"
                            :model-value="filters[field.key]" :title="field.label" :placeholder="field.placeholder" :maxlength="field.maxlength"
                            :disabled="fieldDisabled(field)" @update:model-value="updateFilter(field, $event)"/>
                        <InputDate v-else-if="field.kind === 'date'" :model-value="filters[field.key]" :title="field.label"
                            :disabled="fieldDisabled(field)" @update:model-value="updateFilter(field, $event)"/>
                        <FgField v-else :label="field.label" v-slot="{id}">
                            <WarehouseSelect v-if="field.kind === 'warehouse'" :id="id + '-select'" :input-id="id"
                                :model-value="filters[field.key]" :options="options[field.options]" :disabled="loading" @update:model-value="updateFilter(field, $event)"/>
                            <FgSelect v-else :input-id="id" :model-value="filters[field.key]" :options="options[field.options]"
                                :placeholder="field.placeholder" :disabled="fieldDisabled(field)" :searchable="field.searchable || false" :clearable="false"
                                @update:model-value="updateFilter(field, $event)"/>
                        </FgField>
                    </div>
                    <div class="fg-filter-grid__actions">
                        <FgFilterActions :disabled="!warehouseAvailable" :loading="loading" @search="$emit('search')" @clear="$emit('clear')"/>
                    </div>
                </section>
                <div class="fg-list-heading"><h2 class="fg-section-title">{{ view.listTitle }}</h2></div>
                <section :aria-label="view.listTitle">
                    <InventoryTable :columns="view.columns" :min-width="view.minWidth" :loading="loading" :loading-label="view.loading"
                        :error="error" :empty="!records.data?.length" :empty-title="warehouseAvailable ? 'No se encontraron registros' : 'No hay un almacén de trabajo'"
                        :empty-description="warehouseAvailable ? 'Revisa los filtros y vuelve a buscar.' : 'Selecciona un almacén y pulsa Buscar.'" @retry="$emit('retry')">
                        <slot/>
                    </InventoryTable>
                    <Paginator v-if="showResults" :aria-label="`Paginación de ${view.title.toLocaleLowerCase('es')}`" :links="records.links"
                        :pagination="records" :loading="loading" @click-page="$emit('page', $event)"/>
                </section>
            </div>
        </InitialLoadTransition>
    </main>
</template>

<script setup>
import {computed, shallowRef} from "vue";
import ActionsDropdown from "@System/Components/Generics/ActionsDropdown.vue";
import InitialLoadTransition from "@System/Components/Generics/InitialLoadTransition.vue";
import SearchInput from "@System/Components/Generics/SearchInput.vue";
import WarehouseSelect from "@System/Components/Generics/WarehouseSelect.vue";
import InputDate from "@System/Components/InputDate.vue";
import FgField from "@System/Components/Figma/FgField.vue";
import FgFilterActions from "@System/Components/Figma/FgFilterActions.vue";
import FgSelect from "@System/Components/Figma/FgSelect.vue";
import Paginator from "@System/Components/Paginator.vue";
import InventorySkeleton from "./InventorySkeleton.vue";
import InventoryTable from "./InventoryTable.vue";
import {inventoryFilters, inventoryViews} from "./inventoryPresentation.js";

const props = defineProps({
    page: {type: String, required: true},
    options: {type: Object, required: true},
    records: {type: Object, required: true},
    initialLoading: Boolean,
    loading: Boolean,
    exporting: Boolean,
    appliedWarehouse: Boolean,
    error: String
});
defineEmits(["search", "clear", "export", "page", "retry"]);
const filters = defineModel("filters", {type: Object, required: true});
const fields = computed(() => inventoryFilters[props.page]);
const view = computed(() => inventoryViews[props.page]);
const warehouseAvailable = computed(() => props.page === "transfers" || Boolean(filters.value.warehouse?.code));
const searchDisabled = computed(() => props.loading || !warehouseAvailable.value);
const exportDisabled = computed(() => searchDisabled.value || props.exporting || (props.page !== "transfers" && !props.appliedWarehouse));
const showResults = computed(() => props.page !== "stock" || props.appliedWarehouse);
const productSearch = shallowRef(null);
function setProductSearch(input) { productSearch.value = input; }
function fieldDisabled(field) { return props.loading || (field.kind !== "warehouse" && !warehouseAvailable.value); }
function updateFilter(field, value) {
    filters.value = {...filters.value, [field.key]: typeof value === "string" && field.kind === "search" ? value.trim() : value};
}
defineExpose({focusProductSearch: () => productSearch.value?.focus()});
</script>
