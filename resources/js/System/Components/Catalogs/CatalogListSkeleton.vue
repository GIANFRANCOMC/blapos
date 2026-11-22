<template>
    <div class="fg-catalog-skeleton fg-skeleton" role="status" aria-live="polite" aria-busy="true" :aria-label="`Cargando ${label}`">
        <span class="fg-sr-only">Cargando {{ label }}</span>
        <div aria-hidden="true">
            <FgSkeletonFilters/>
            <div class="fg-list-heading">
                <span class="fg-catalog-skeleton__heading"><span class="fg-skeleton-line"/></span>
                <div class="fg-catalog-skeleton__order"><span class="fg-skeleton-line fg-catalog-skeleton__order-label"/><span class="fg-skeleton-control"><span class="fg-skeleton-line fg-catalog-skeleton__value"/></span></div>
            </div>
            <section class="fg-products-list">
                <div class="fg-table-scroll">
                    <table class="fg-table fg-catalog-skeleton__table" :class="{'fg-products-table': kind === 'products'}">
                        <colgroup><col v-for="column in columns" :key="column.title" :style="{width: column.width}"/></colgroup>
                        <thead><tr><th v-for="column in columns" :key="column.title" scope="col" :class="column.align">{{ column.title }}</th></tr></thead>
                        <tbody><tr class="fg-table__state"><td :colspan="columns.length"><FgListLoading :label="`Cargando ${label}…`"/></td></tr></tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</template>

<script>
import FgListLoading from "@System/Components/Figma/FgListLoading.vue";
import FgSkeletonFilters from "@System/Components/Figma/FgSkeletonFilters.vue";

const COMMON_IDENTITY = {title: "Identificación"};
const COMMON_INTERNAL_CODE = {title: "Código interno", align: "fg-align-center"};
const COMMON_STATUS = {title: "Estado", align: "fg-align-center"};
const COMMON_ACTIONS = {title: "Acciones", align: "fg-align-center"};

const offeringColumns = title => [
    {...COMMON_INTERNAL_CODE, width: "22%"},
    {title, width: "34%"},
    {title: "Detalle", width: "15%"},
    {title: "Precio", width: "15%", align: "fg-align-center"},
    {...COMMON_STATUS, width: "8%"},
    {...COMMON_ACTIONS, width: "6%"}
];

const COLUMNS = {
    products: [
        {...COMMON_IDENTITY, width: "22%"},
        {title: "Producto", width: "40%"},
        {title: "Precio", width: "16%", align: "fg-align-end"},
        {...COMMON_STATUS, width: "13%"},
        {...COMMON_ACTIONS, width: "9%"}
    ],
    services: offeringColumns("Servicio"),
    subscriptions: offeringColumns("Membresía"),
    categories: [
        {...COMMON_INTERNAL_CODE, width: "20%"},
        {title: "Categoría", width: "40%"},
        {title: "En uso", width: "20%", align: "fg-align-center"},
        {...COMMON_STATUS, width: "13%"},
        {...COMMON_ACTIONS, width: "7%"}
    ],
    brands: [
        {...COMMON_INTERNAL_CODE, width: "20%"},
        {title: "Marca", width: "28%"},
        {title: "Sitio oficial", width: "14%", align: "fg-align-center"},
        {title: "En uso", width: "19%", align: "fg-align-center"},
        {...COMMON_STATUS, width: "11%"},
        {...COMMON_ACTIONS, width: "8%"}
    ],
    recipes: [
        {...COMMON_INTERNAL_CODE, width: "18%"},
        {title: "Platillo", width: "32%"},
        {title: "Fórmula", width: "18%"},
        {title: "Operación", width: "16%"},
        {...COMMON_STATUS, width: "10%"},
        {...COMMON_ACTIONS, width: "6%"}
    ],
    branches: [
        {...COMMON_IDENTITY, width: "20%"},
        {title: "Sucursal", width: "18%"},
        {title: "Ubicación", width: "19%"},
        {title: "Contacto", width: "19%"},
        {title: "Aforo", width: "10%", align: "fg-align-center"},
        {...COMMON_STATUS, width: "8%"},
        {...COMMON_ACTIONS, width: "6%"}
    ],
    users: [
        {...COMMON_IDENTITY, width: "20%"},
        {title: "Colaborador", width: "20%"},
        {title: "Acceso", width: "20%"},
        {title: "Contacto", width: "25%"},
        {...COMMON_STATUS, width: "9%"},
        {...COMMON_ACTIONS, width: "6%"}
    ]
};

export default {
    components: {FgListLoading, FgSkeletonFilters},
    name: "CatalogListSkeleton",
    props: {
        kind: {type: String, required: true, validator: value => Object.prototype.hasOwnProperty.call(COLUMNS, value)},
        label: {type: String, required: true}
    },
    computed: {
        columns() { return COLUMNS[this.kind] || []; }
    }
};
</script>
