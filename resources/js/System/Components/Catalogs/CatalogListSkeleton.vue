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
                        <col v-for="column in columns" :key="column.title" :style="{width: column.width}">
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
                            <td :colspan="columns.length" class="py-4"><div class="br-list-initial-space" aria-hidden="true"></div></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</template>

<script>

const COMMON_IDENTITY = {title: "Identificación"};
const COMMON_STATUS = {title: "Estado", align: "text-center"};
const COMMON_ACTIONS = {title: "", align: "text-center"};

const COLUMNS = {
    services: [
        {...COMMON_IDENTITY, width: "22%"},
        {title: "Servicio", width: "25%"},
        {title: "Detalles", width: "22%"},
        {title: "Precio", width: "17%", align: "text-end"},
        {...COMMON_STATUS, width: "8%"},
        {...COMMON_ACTIONS, width: "6%"}
    ],
    subscriptions: [
        {...COMMON_IDENTITY, width: "22%"},
        {title: "Membresía", width: "25%"},
        {title: "Detalles", width: "22%"},
        {title: "Precio", width: "17%", align: "text-end"},
        {...COMMON_STATUS, width: "8%"},
        {...COMMON_ACTIONS, width: "6%"}
    ],
    categories: [
        {...COMMON_IDENTITY, width: "20%"},
        {title: "Categoría", width: "34%"},
        {title: "En uso", width: "26%", align: "text-center"},
        {...COMMON_STATUS, width: "13%"},
        {...COMMON_ACTIONS, width: "7%"}
    ],
    brands: [
        {...COMMON_IDENTITY, width: "20%"},
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
