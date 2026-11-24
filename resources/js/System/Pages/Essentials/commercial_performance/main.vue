<template>
    <main class="fg-performance">
        <header class="fg-page-header">
            <div>
                <h1 class="fg-title">Rendimiento comercial</h1>
                <p class="fg-help">Consulta cuánto vendiste por sucursal, producto, servicio o plan.</p>
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
            <FgEmptyState v-else-if="initFailed" title="No se pudieron cargar los filtros" description="Vuelve a intentar para consultar las ventas.">
                <FgButton tone="secondary" @click="initialize">Volver a intentar</FgButton>
            </FgEmptyState>
            <form v-else class="fg-filters fg-filter-grid fg-filter-grid--analytics" role="search"
                aria-label="Filtrar rendimiento comercial" @submit.prevent="search()">
                <FgField v-for="field in dates" :key="field.key" :label="field.label" :error="errors[field.key]"
                    class="fg-filter-grid__field fg-filter-grid__field--compact" v-slot="{id, invalid, describedBy}">
                    <div class="fg-field__control" :class="{'is-invalid': invalid}">
                        <input :id="id" v-model="draft[field.key]" type="date" class="fg-input"
                            :aria-invalid="invalid || undefined" :aria-describedby="describedBy" :disabled="loading" required/>
                    </div>
                </FgField>
                <FgField :label="filterLabels[2]" :error="errors.branch_id" class="fg-filter-grid__field">
                    <FgSelect v-model="draft.branch_id" :options="branches" :reduce="option => option.id"
                        :searchable="branches.length > 6" :clearable="false" :disabled="loading"/>
                </FgField>
                <FgField :label="filterLabels[3]" :error="errors.type" class="fg-filter-grid__field">
                    <FgSelect v-model="draft.type" :options="types" :reduce="option => option.code"
                        :searchable="false" :clearable="false" :disabled="loading"/>
                </FgField>
                <div class="fg-filter-grid__actions">
                    <FgFilterActions submit :disabled="loading" :loading="loading" @clear="clearFilters"/>
                </div>
            </form>
        </InitialLoadTransition>

        <template v-if="!initFailed">
            <section class="fg-performance__section" aria-labelledby="fg-performance-evolution">
                <header class="fg-performance__heading">
                    <h2 id="fg-performance-evolution" class="fg-section-title">Evolución de ventas</h2>
                    <p v-if="displayedFilters" class="fg-help">{{ displayedFilters }}</p>
                </header>
                <div v-if="initialLoading || (loading && !evolution)" class="fg-performance__chart-loading" aria-hidden="true">
                    <span v-if="initialLoading" class="fg-skeleton-control"/>
                </div>
                <div v-else-if="evolution?.series.length" class="fg-performance__charts">
                    <PerformanceChart v-for="series in evolution.series" :key="series.currency_id"
                        :days="evolution.days" :series="series"/>
                </div>
                <p v-else-if="evolution" class="fg-help">No hubo ventas vigentes para los filtros seleccionados.</p>
            </section>

            <section class="fg-performance__section" aria-labelledby="fg-performance-items">
                <header class="fg-performance__heading">
                    <h2 id="fg-performance-items" class="fg-section-title">Ventas por ítem</h2>
                </header>
                <ResponsiveTable>
                    <table class="fg-table fg-performance__table">
                        <thead><tr>
                            <th scope="col" class="text-start">Ítem</th>
                            <th scope="col" class="text-start">Tipo</th>
                            <th scope="col" class="text-end">Cantidad vendida</th>
                            <th scope="col" class="text-center">Moneda</th>
                            <th scope="col" class="text-end">Importe vendido</th>
                        </tr></thead>
                        <tbody>
                            <tr v-if="initialLoading || loading" class="fg-table__state">
                                <td colspan="5"><FgListLoading :label="initialLoading ? 'Preparando rendimiento comercial…' : 'Consultando ventas…'"/></td>
                            </tr>
                            <tr v-for="item in records.data" :key="`${item.item_id}-${item.type}-${item.currency_id}`">
                                <td>{{ item.name }}</td>
                                <td>{{ typeLabel(item.type) }}</td>
                                <td class="text-end text-nowrap">{{ separatorNumber(item.quantity) }}</td>
                                <td class="text-center">{{ item.code }}</td>
                                <td class="text-end text-nowrap">{{ formatCurrencyAmount(item.total, item) }}</td>
                            </tr>
                            <tr v-if="!initialLoading && !loading && !records.data.length" class="fg-table__state">
                                <td colspan="5">
                                    <FgEmptyState v-if="failed" title="No se pudo consultar el rendimiento" :description="failureMessage">
                                        <FgButton tone="secondary" @click="retry">Volver a intentar</FgButton>
                                    </FgEmptyState>
                                    <FgEmptyState v-else-if="Object.keys(errors).length" title="Revisa el periodo de búsqueda"
                                        description="Corrige los filtros marcados y pulsa Buscar."/>
                                    <FgEmptyState v-else title="No hay ventas para estos filtros"
                                        description="Prueba con otro periodo, sucursal o tipo de ítem."/>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </ResponsiveTable>
                <Paginator v-if="!initialLoading && !failed && !Object.keys(errors).length" :links="records.links" :pagination="records"
                    :loading="loading" @click-page="goToPage"/>
            </section>
        </template>
    </main>
</template>

<script setup>
import {computed, onMounted, reactive, ref, shallowRef} from "vue";
import ResponsiveTable from "@/Shared/ResponsiveTable.vue";
import {formatCurrencyAmount, separatorNumber} from "@/Shared/Numbers.js";
import FgButton from "@System/Components/Figma/FgButton.vue";
import FgEmptyState from "@System/Components/Figma/FgEmptyState.vue";
import FgField from "@System/Components/Figma/FgField.vue";
import FgFilterActions from "@System/Components/Figma/FgFilterActions.vue";
import FgListLoading from "@System/Components/Figma/FgListLoading.vue";
import FgSelect from "@System/Components/Figma/FgSelect.vue";
import FgSkeletonFilterActions from "@System/Components/Figma/FgSkeletonFilterActions.vue";
import InitialLoadTransition from "@System/Components/Generics/InitialLoadTransition.vue";
import Paginator from "@System/Components/Paginator.vue";
import {showFiltersCleared} from "@System/Helpers/Alerts.js";
import {emptyPagination, get} from "@System/Helpers/Requests.js";
import PerformanceChart from "./PerformanceChart.vue";

const props = defineProps({paramsUrl: {type: String, required: true}, listUrl: {type: String, required: true}});
const filterLabels = ["Desde", "Hasta", "Sucursal", "Tipo de ítem"];
const dates = [{key: "from", label: filterLabels[0]}, {key: "to", label: filterLabels[1]}];
const dateFormat = new Intl.DateTimeFormat("es-PE", {dateStyle: "medium", timeZone: "UTC"});
const displayDate = day => dateFormat.format(new Date(`${day}T00:00:00Z`));
const defaults = ref({});
const all = "all";
const draft = reactive({from: "", to: "", branch_id: all, type: all});
const applied = shallowRef(null);
const branches = shallowRef([]);
const types = shallowRef([]);
const records = shallowRef(emptyPagination());
const evolution = shallowRef(null);
const errors = shallowRef({});
const initialLoading = ref(true);
const initFailed = ref(false);
const loading = ref(false);
const failed = ref(false);
const failureMessage = ref("");
let lastQuery;

const typeLabel = code => types.value.find(option => option.code === code)?.label ?? code;
const displayedFilters = computed(() => {
    if (!applied.value || !evolution.value) return "";
    const {from, to, branch_id, type} = applied.value;
    return [
        from === to ? displayDate(from) : `${displayDate(from)} – ${displayDate(to)}`,
        branches.value.find(option => option.id === branch_id)?.label,
        type !== all ? typeLabel(type) : null,
    ].filter(Boolean).join(" · ");
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
    defaults.value = {...data.defaults, branch_id: all, type: all};
    Object.assign(draft, defaults.value);
    branches.value = [
        {id: all, label: "Todas las sucursales permitidas"},
        ...data.branches.map(branch => ({
            id: branch.id,
            label: branch.status === "active" ? branch.name : `${branch.name} (inactiva)`,
        })),
    ];
    types.value = [
        {code: all, label: "Todos los tipos"},
        ...data.types.map(type => type.code === "subscription" ? {...type, label: "Plan de membresía"} : type),
    ];
    initialLoading.value = false;
    await search();
}

function clearFilters() {
    Object.assign(draft, defaults.value);
    showFiltersCleared();
}

function search() {
    if (loading.value) return;
    applied.value = {...draft};
    return query(1, true);
}

function goToPage({url}) {
    if (loading.value) return;
    const page = new URL(url, props.listUrl).searchParams.get("page");
    if (page) return query(page, false);
}

function retry() {
    if (!loading.value && lastQuery) return query(lastQuery.page, lastQuery.includeEvolution);
}

async function query(page, includeEvolution) {
    lastQuery = {page, includeEvolution};
    records.value = emptyPagination();
    if (includeEvolution) evolution.value = null;
    errors.value = {};
    failed.value = false;
    loading.value = true;

    const {from, to, branch_id, type} = applied.value;
    const response = await get({
        route: props.listUrl,
        data: {from, to, branch_id: branch_id === all ? null : branch_id, type: type === all ? null : type, page, include_evolution: includeEvolution ? 1 : 0},
    });
    if (response.bool) {
        records.value = response.data.data.items;
        if (includeEvolution) evolution.value = response.data.data.evolution;
    } else if (response.code === 422 && response.data.errors) {
        errors.value = response.data.errors;
    } else {
        failed.value = true;
        failureMessage.value = response.code === 403
            ? "Tu perfil no tiene acceso al informe o a la sucursal seleccionada."
            : "Intenta consultar las ventas nuevamente.";
    }
    loading.value = false;
}

onMounted(initialize);
</script>

<style>
.fg-performance__section {
    margin-block-start: var(--fg-space-6);
    min-width: 0;
}

.fg-performance__heading {
    display: flex;
    flex-wrap: wrap;
    align-items: baseline;
    justify-content: space-between;
    gap: var(--fg-space-2) var(--fg-space-4);
    margin-block-end: var(--fg-space-3);
}

.fg-performance__charts {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(min(100%, 24rem), 1fr));
    gap: var(--fg-space-4);
}

.fg-performance__chart-loading {
    min-height: 8rem;
    display: grid;
    place-items: center;
    border: 1px solid var(--fg-border);
    border-radius: var(--fg-panel-radius);
    background: var(--fg-surface);
}

.fg-performance__chart-loading .fg-skeleton-control {
    width: 80%;
}

.fg-performance__table {
    min-width: 35rem;
}

.fg-performance__table th:first-child { width: 40%; }
.fg-performance__table th:nth-child(4) { width: 12%; }

.fg-performance__table td:first-child {
    overflow-wrap: anywhere;
}

</style>
