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
                <table :class="['table', 'br-entity-table', 'mb-0', {'br-entity-table--offering': isOffering}]">
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
                        <tr v-for="index in 8" :key="index" :class="['br-catalog-skeleton__row', {'br-catalog-skeleton__row--offering': isOffering}]">
                            <td v-for="column in columns" :key="column.title" :class="column.shape === 'identity' ? 'br-entity-table__identification-cell' : ''">
                                <span v-if="column.shape === 'identity'" class="br-catalog-skeleton__cell-copy">
                                    <span class="br-catalog-skeleton__line br-catalog-skeleton__identifier-label"></span>
                                    <span class="br-catalog-skeleton__line br-catalog-skeleton__identifier-value"></span>
                                </span>
                                <span v-else-if="column.shape === 'name'" class="br-catalog-skeleton__cell-copy">
                                    <span class="br-catalog-skeleton__line br-catalog-skeleton__name"></span>
                                    <span class="br-catalog-skeleton__line br-catalog-skeleton__description"></span>
                                </span>
                                <span v-else-if="column.shape === 'details'" class="br-catalog-skeleton__cell-copy">
                                    <span v-for="line in kind === 'subscriptions' ? 4 : 3" :key="line" class="br-catalog-skeleton__line br-catalog-skeleton__detail"></span>
                                </span>
                                <span v-else-if="column.shape === 'price'" class="br-catalog-skeleton__cell-copy br-catalog-skeleton__cell-copy--end">
                                    <span class="br-catalog-skeleton__line br-catalog-skeleton__price"></span>
                                    <span class="br-catalog-skeleton__line br-catalog-skeleton__price br-catalog-skeleton__price--short"></span>
                                </span>
                                <span v-else :class="['br-catalog-skeleton__line', `br-catalog-skeleton__${column.shape}`]"></span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <div class="br-catalog-skeleton__pagination" aria-hidden="true">
            <span v-for="index in 4" :key="index" class="br-catalog-skeleton__line br-catalog-skeleton__page"></span>
        </div>
    </main>
</template>

<script>
const COMMON_IDENTITY = {title: "Identificación", shape: "identity"};
const COMMON_STATUS = {title: "Estado", shape: "status", align: "text-center"};
const COMMON_ACTIONS = {title: "", shape: "actions", align: "text-center"};

const COLUMNS = {
    services: [
        {...COMMON_IDENTITY, width: "22%"},
        {title: "Servicio", shape: "name", width: "25%"},
        {title: "Detalles", shape: "details", width: "22%"},
        {title: "Precio", shape: "price", width: "17%", align: "text-end"},
        {...COMMON_STATUS, width: "8%"},
        {...COMMON_ACTIONS, width: "6%"}
    ],
    subscriptions: [
        {...COMMON_IDENTITY, width: "22%"},
        {title: "Membresía", shape: "name", width: "25%"},
        {title: "Detalles", shape: "details", width: "22%"},
        {title: "Precio", shape: "price", width: "17%", align: "text-end"},
        {...COMMON_STATUS, width: "8%"},
        {...COMMON_ACTIONS, width: "6%"}
    ],
    categories: [
        {...COMMON_IDENTITY, width: "20%"},
        {title: "Categoría", shape: "name", width: "44%"},
        {title: "Uso actual", shape: "usage", width: "16%", align: "text-center"},
        {...COMMON_STATUS, width: "13%"},
        {...COMMON_ACTIONS, width: "7%"}
    ],
    brands: [
        {...COMMON_IDENTITY, width: "20%"},
        {title: "Marca", shape: "name", width: "26%"},
        {title: "Sitio oficial", shape: "website", width: "19%"},
        {title: "Uso actual", shape: "usage", width: "16%", align: "text-center"},
        {...COMMON_STATUS, width: "11%"},
        {...COMMON_ACTIONS, width: "8%"}
    ]
};

export default {
    name: "CatalogListSkeleton",
    props: {
        kind: {type: String, required: true, validator: value => Object.hasOwn(COLUMNS, value)},
        label: {type: String, required: true}
    },
    computed: {
        columns() { return COLUMNS[this.kind] || []; },
        isOffering() { return this.kind === "services" || this.kind === "subscriptions"; }
    }
};
</script>
