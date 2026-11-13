<template>
    <main class="br-entity br-catalog-skeleton" role="status" aria-live="polite" :aria-label="`Cargando ${label}`">
        <span class="visually-hidden">Cargando {{ label }}</span>

        <section class="br-filter-bar br-catalog-skeleton__filters" aria-hidden="true">
            <div class="br-catalog-skeleton__filter">
                <span class="br-catalog-skeleton__line br-catalog-skeleton__label"></span>
                <span class="br-catalog-skeleton__control"><span class="br-catalog-skeleton__line br-catalog-skeleton__select-value"></span></span>
            </div>
            <div class="br-catalog-skeleton__filter">
                <span class="br-catalog-skeleton__line br-catalog-skeleton__label"></span>
                <span class="br-catalog-skeleton__control"><span class="br-catalog-skeleton__line br-catalog-skeleton__search-value"></span></span>
            </div>
            <div class="br-catalog-skeleton__filter br-catalog-skeleton__filter--actions">
                <div class="br-filter-bar__actions">
                    <span class="br-catalog-skeleton__line br-catalog-skeleton__button"></span>
                    <span class="br-catalog-skeleton__line br-catalog-skeleton__button br-catalog-skeleton__button--add"></span>
                    <span class="br-catalog-skeleton__line br-catalog-skeleton__button br-catalog-skeleton__button--more"></span>
                </div>
            </div>
        </section>

        <section class="br-entity-list" aria-hidden="true">
            <div class="table-responsive">
                <table :class="['table', 'br-entity-table', 'mb-0', {'br-entity-table--offering': isOffering, 'br-entity-table--directory': isDirectory, 'br-entity-table--catalog-usage': isCatalogUsage, 'br-entity-table--commercial-headings': isOffering || isCatalogUsage}]">
                    <colgroup>
                        <col v-for="column in columns" :key="column.title" :class="column.className" :style="{width: column.width}">
                    </colgroup>
                    <thead class="br-table-header-surface">
                        <tr>
                            <th v-for="column in columns" :key="column.title" :class="column.align">
                                <span v-if="column.title" v-text="column.title"></span>
                                <span v-else class="visually-hidden">Acciones</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="br-table-state">
                            <td :colspan="columns.length" class="py-4"><Loader/></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</template>

<script>
import Loader from "@System/Components/Loader.vue";

const COMMON_IDENTITY = {title: "Identificación"};
const COMMON_INTERNAL_CODE = {title: "Código interno", align: "text-center"};
const COMMON_STATUS = {title: "Estado", align: "text-center"};
const COMMON_ACTIONS = {title: "", align: "text-center"};

const offeringColumns = title => [
    {...COMMON_INTERNAL_CODE, className: "br-entity-table__col-code"},
    {title, className: "br-entity-table__col-offering"},
    {title: "Detalles", className: "br-entity-table__col-detail"},
    {title: "Precio", className: "br-entity-table__col-offering-price", align: "text-center"},
    {...COMMON_STATUS, className: "br-entity-table__col-status"},
    {...COMMON_ACTIONS, className: "br-entity-table__col-actions"}
];

const COLUMNS = {
    services: offeringColumns("Servicio"),
    subscriptions: offeringColumns("Membresía"),
    categories: [
        {...COMMON_INTERNAL_CODE, width: "20%"},
        {title: "Categoría", width: "34%"},
        {title: "En uso", width: "26%", align: "text-center"},
        {...COMMON_STATUS, width: "13%"},
        {...COMMON_ACTIONS, width: "7%"}
    ],
    brands: [
        {...COMMON_INTERNAL_CODE, width: "20%"},
        {title: "Marca", width: "22%"},
        {title: "Sitio oficial", width: "14%"},
        {title: "En uso", width: "25%", align: "text-center"},
        {...COMMON_STATUS, width: "11%"},
        {...COMMON_ACTIONS, width: "8%"}
    ],
    branches: [
        {...COMMON_IDENTITY, width: "20%"},
        {title: "Sucursal", width: "18%"},
        {title: "Ubicación", width: "19%"},
        {title: "Contacto", width: "19%"},
        {title: "Aforo", width: "10%", align: "text-center"},
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
    components: {Loader},
    name: "CatalogListSkeleton",
    props: {
        kind: {type: String, required: true, validator: value => Object.prototype.hasOwnProperty.call(COLUMNS, value)},
        label: {type: String, required: true}
    },
    computed: {
        columns() { return COLUMNS[this.kind] || []; },
        isOffering() { return this.kind === "services" || this.kind === "subscriptions"; },
        isDirectory() { return this.kind === "branches" || this.kind === "users"; },
        isCatalogUsage() { return this.kind === "categories" || this.kind === "brands"; }
    }
};
</script>
