<template>
    <main>
        <header class="fg-page-header">
            <div>
                <h1 class="fg-title">Rotación y reposición</h1>
                <p class="fg-help">Identifica qué productos necesitan reposición en cada almacén.</p>
            </div>
        </header>

        <InitialLoadTransition>
            <div v-if="initialLoading" class="fg-filters fg-filter-grid fg-filter-grid--analytics fg-skeleton" inert>
                <div v-for="(label, index) in filterLabels" :key="label" class="fg-field fg-filter-grid__field"
                    :class="{'fg-filter-grid__field--compact': index < 2}">
                    <span class="fg-field__label">{{ label }}</span>
                    <span class="fg-skeleton-control"><span class="fg-skeleton-line fg-catalog-skeleton__value"/></span>
                </div>
                <div class="fg-filter-grid__actions"><FgSkeletonFilterActions/></div>
            </div>
            <FgEmptyState v-else-if="initFailed" title="No se pudieron cargar los filtros" description="Vuelve a intentar para consultar el inventario.">
                <FgButton tone="secondary" @click="initialize">Volver a intentar</FgButton>
            </FgEmptyState>
            <form v-else class="fg-filters fg-filter-grid fg-filter-grid--analytics" role="search"
                aria-label="Filtrar rotación y reposición" @submit.prevent="search">
                <FgField v-for="field in dates" :key="field.key" :label="field.label" :error="errors[field.key]"
                    class="fg-filter-grid__field fg-filter-grid__field--compact" v-slot="{id, invalid, describedBy}">
                    <div class="fg-field__control" :class="{'is-invalid': invalid}">
                        <input :id="id" v-model="draft[field.key]" type="date" class="fg-input"
                            :aria-invalid="invalid || undefined" :aria-describedby="describedBy" :disabled="loading" required/>
                    </div>
                </FgField>
                <FgField label="Sucursal" :error="errors.branch_id" class="fg-filter-grid__field">
                    <FgSelect :model-value="draft.branch_id" :options="branches" :reduce="option => option.id"
                        :clearable="false" :searchable="branches.length > 6" :disabled="loading" @update:model-value="selectBranch"/>
                </FgField>
                <FgField label="Almacén" :error="errors.warehouse_id" class="fg-filter-grid__field">
                    <FgSelect v-model="draft.warehouse_id" :options="warehouseOptions" :reduce="option => option.id"
                        :clearable="false" :searchable="warehouseOptions.length > 6" :disabled="loading"/>
                </FgField>
                <div class="fg-filter-grid__actions">
                    <FgFilterActions submit :disabled="loading" :loading="loading" @clear="clearFilters"/>
                </div>
                <label class="fg-check fg-turnover__replenishment">
                    <input v-model="draft.replenishment_only" type="checkbox" :disabled="loading"/>
                    <span>Solo productos por reponer</span>
                </label>
            </form>
        </InitialLoadTransition>

        <section v-if="!initFailed" class="fg-turnover__results" aria-labelledby="fg-turnover-items">
            <header class="fg-turnover__heading">
                <h2 id="fg-turnover-items" class="fg-section-title">Inventario por almacén</h2>
                <p v-if="displayedFilters" class="fg-help">{{ displayedFilters }}</p>
            </header>
            <p class="fg-help fg-turnover__note">El stock y el mínimo son actuales. Las salidas netas del periodo descuentan las devoluciones y las reposiciones por anulación.</p>
            <InventoryTable :columns="columns" min-width="48rem" responsive-cards :loading="initialLoading || loading"
                :loading-label="initialLoading ? 'Preparando rotación y reposición…' : 'Consultando inventario…'"
                :empty="!records.data.length" :empty-title="emptyTitle" :empty-description="emptyDescription"
                :error="failureMessage" @retry="query(lastPage)">
                <tr role="row" v-for="record in records.data" :key="record.id">
                    <td role="cell" class="fg-record-table__identity"><ProductIdentity variant="expandable" :product="record.product"/></td>
                    <td role="cell">
                        <span class="fg-record-table__label" aria-hidden="true">Sucursal / almacén</span>
                        <span class="fg-record-table__value"><span>{{ record.branch_name }}</span><span class="fg-table-note">{{ record.warehouse_name }}</span></span>
                    </td>
                    <td role="cell" class="fg-align-end">
                        <span class="fg-record-table__label" aria-hidden="true">Stock disponible</span>
                        <span class="fg-record-table__value"><QuantityValue class="fg-inventory-quantity" :value="record.quantity" :unit="record.measurement_unit"/></span>
                    </td>
                    <td role="cell" class="fg-align-end">
                        <span class="fg-record-table__label" aria-hidden="true">Stock mínimo</span>
                        <span class="fg-record-table__value"><QuantityValue v-if="record.minimum_stock !== null" :value="record.minimum_stock" :unit="record.measurement_unit"/><span v-else class="fg-help">Sin mínimo</span></span>
                    </td>
                    <td role="cell" class="fg-align-end">
                        <span class="fg-record-table__label" aria-hidden="true">Salidas netas</span>
                        <span class="fg-record-table__value"><QuantityValue :value="record.net_outgoing" :unit="record.measurement_unit"/></span>
                    </td>
                    <td role="cell" class="fg-record-table__state fg-align-center"><span :class="['fg-badge', 'fg-badge--' + inventoryBadgeTones[record.stock_status]]">{{ labels[record.stock_status] }}</span></td>
                </tr>
            </InventoryTable>
            <Paginator v-if="loaded" :links="records.links" :pagination="records" :loading="loading" @click-page="goToPage"/>
        </section>
    </main>
</template>

<script setup>
import {computed, onMounted, reactive, ref, shallowRef} from "vue";
import {labels} from "@/Shared/StockStatus.js";
import FgButton from "@System/Components/Figma/FgButton.vue";
import FgEmptyState from "@System/Components/Figma/FgEmptyState.vue";
import FgField from "@System/Components/Figma/FgField.vue";
import FgFilterActions from "@System/Components/Figma/FgFilterActions.vue";
import FgSelect from "@System/Components/Figma/FgSelect.vue";
import FgSkeletonFilterActions from "@System/Components/Figma/FgSkeletonFilterActions.vue";
import InitialLoadTransition from "@System/Components/Generics/InitialLoadTransition.vue";
import ProductIdentity from "@System/Components/Generics/ProductIdentity.vue";
import QuantityValue from "@System/Components/Generics/QuantityValue.vue";
import Paginator from "@System/Components/Paginator.vue";
import {showFiltersCleared} from "@System/Helpers/Alerts.js";
import {emptyPagination, get} from "@System/Helpers/Requests.js";
import InventoryTable from "@System/Pages/Warehouses/inventory/InventoryTable.vue";
import {inventoryBadgeTones} from "@System/Pages/Warehouses/inventory/inventoryPresentation.js";

const props = defineProps({paramsUrl: {type: String, required: true}, listUrl: {type: String, required: true}});
const columns = [
    {label: "Producto", width: "28%", align: "start"},
    {label: "Sucursal / almacén", width: "18%", align: "start"},
    {label: "Stock disponible", width: "15%", align: "end"},
    {label: "Stock mínimo", width: "12%", align: "end"},
    {label: "Salidas netas", width: "12%", align: "end"},
    {label: "Reposición", width: "15%", align: "center"},
];
const filterLabels = ["Desde", "Hasta", "Sucursal", "Almacén"];
const dates = [{key: "from", label: filterLabels[0]}, {key: "to", label: filterLabels[1]}];
const all = "all";
const baseFilters = {from: "", to: "", branch_id: all, warehouse_id: all, replenishment_only: false};
const draft = reactive({...baseFilters});
const applied = shallowRef(null);
const branches = shallowRef([]);
const warehouses = shallowRef([]);
const records = shallowRef(emptyPagination());
const errors = shallowRef({});
const initialLoading = ref(true);
const initFailed = ref(false);
const loading = ref(false);
const loaded = ref(false);
const failureMessage = ref("");
let defaults = {...baseFilters};
let lastPage = 1;

const dateFormat = new Intl.DateTimeFormat("es-PE", {dateStyle: "medium", timeZone: "UTC"});
const displayDate = day => dateFormat.format(new Date(`${day}T00:00:00Z`));
const warehouseOptions = computed(() => [
    {id: all, label: "Todos los almacenes"},
    ...warehouses.value.filter(warehouse => draft.branch_id === all || warehouse.branch_id === draft.branch_id)
        .map(warehouse => ({
            id: warehouse.id,
            label: draft.branch_id === all ? `${warehouse.branch_name} · ${warehouse.name}` : warehouse.name,
        })),
]);
const displayedFilters = computed(() => {
    if (!loaded.value) return "";

    const {from, to, branch_id, warehouse_id, replenishment_only} = applied.value;
    const warehouse = warehouses.value.find(option => option.id === warehouse_id);
    const branch = branches.value.find(option => option.id === branch_id);
    const location = warehouse ? `${warehouse.branch_name} · ${warehouse.name}` : branch?.label;
    return [
        from === to ? displayDate(from) : `${displayDate(from)} – ${displayDate(to)}`,
        location,
        replenishment_only ? "Solo por reponer" : null,
    ].filter(Boolean).join(" · ");
});
const invalidFilters = computed(() => Object.keys(errors.value).length > 0);
const emptyTitle = computed(() => {
    if (invalidFilters.value) return "Revisa los filtros";
    if (!warehouses.value.length) return "No hay almacenes disponibles";
    return applied.value?.replenishment_only ? "No hay productos por reponer" : "No hay productos para estos filtros";
});
const emptyDescription = computed(() => {
    if (invalidFilters.value) return "Corrige los campos marcados y pulsa Buscar.";
    if (!warehouses.value.length) return "Esta consulta requiere acceso a un almacén activo.";
    return "Prueba con otra sucursal o almacén.";
});

async function initialize() {
    initialLoading.value = true;
    initFailed.value = false;
    const response = await get({route: props.paramsUrl});
    if (!response.bool) {
        initFailed.value = true;
        initialLoading.value = false;
        return;
    }

    const {data} = response.data;
    defaults = {...baseFilters, ...data.defaults};
    Object.assign(draft, defaults);
    branches.value = [
        {id: all, label: "Todas las sucursales"},
        ...data.branches.map(branch => ({id: branch.id, label: branch.name})),
    ];
    warehouses.value = data.warehouses;
    initialLoading.value = false;
    await search();
}

function selectBranch(id) {
    draft.branch_id = id;
    if (!warehouseOptions.value.some(warehouse => warehouse.id === draft.warehouse_id)) {
        draft.warehouse_id = all;
    }
}

function clearFilters() {
    Object.assign(draft, defaults);
    errors.value = {};
    showFiltersCleared();
}

function search() {
    if (loading.value) return;
    applied.value = {...draft};
    return query(1);
}

function goToPage({url}) {
    const page = new URL(url, props.listUrl).searchParams.get("page");
    if (page) return query(page);
}

async function query(page) {
    if (loading.value) return;
    lastPage = page;
    records.value = emptyPagination();
    loaded.value = false;
    errors.value = {};
    failureMessage.value = "";
    loading.value = true;
    const {from, to, branch_id, warehouse_id, replenishment_only} = applied.value;
    const response = await get({
        route: props.listUrl,
        data: {
            from, to, page,
            branch_id: branch_id === all ? null : branch_id,
            warehouse_id: warehouse_id === all ? null : warehouse_id,
            replenishment_only: replenishment_only ? 1 : 0,
        }
    });
    if (response.bool) {
        records.value = response.data.data;
        loaded.value = true;
    } else if (response.code === 422 && response.data.errors) {
        errors.value = response.data.errors;
    } else {
        failureMessage.value = response.code === 403
            ? "Tu perfil no tiene acceso al informe o a la ubicación seleccionada."
            : "Vuelve a intentar la consulta.";
    }
    loading.value = false;
}

onMounted(initialize);
</script>

<style>
.fg-turnover__replenishment { grid-column: 1 / -1; min-width: 0; }
.fg-turnover__results { margin-block-start: var(--fg-space-6); min-width: 0; }
.fg-turnover__heading {
    display: flex;
    flex-wrap: wrap;
    align-items: baseline;
    justify-content: space-between;
    gap: var(--fg-space-2) var(--fg-space-4);
    margin-block-end: var(--fg-space-2);
}
.fg-turnover__note { margin-block-end: var(--fg-space-3); }
</style>
