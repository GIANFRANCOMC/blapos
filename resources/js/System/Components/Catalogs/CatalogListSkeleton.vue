<template>
    <div class="fg-catalog-skeleton fg-skeleton" role="status" aria-live="polite" aria-busy="true" :aria-label="`Cargando ${label}`">
        <span class="fg-sr-only">Cargando {{ label }}</span>
        <div aria-hidden="true">
            <FgSkeletonFilters/>
            <div class="fg-list-heading">
                <span class="fg-catalog-skeleton__heading"><span class="fg-skeleton-line"/></span>
                <div v-if="kind === 'products'" class="fg-catalog-skeleton__order"><span class="fg-skeleton-line fg-catalog-skeleton__order-label"/><span class="fg-skeleton-control"><span class="fg-skeleton-line fg-catalog-skeleton__value"/></span></div>
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

import {catalogColumns} from "./catalogColumns.js";

export default {
    components: {FgListLoading, FgSkeletonFilters},
    name: "CatalogListSkeleton",
    props: {
        kind: {type: String, required: true, validator: value => Object.prototype.hasOwnProperty.call(catalogColumns, value)},
        label: {type: String, required: true}
    },
    computed: {
        columns() { return catalogColumns[this.kind] || []; }
    }
};
</script>
