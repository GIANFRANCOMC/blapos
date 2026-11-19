<template>
    <details v-if="total" class="br-catalog-disclosure">
        <summary class="br-link br-catalog-disclosure__summary">
            {{ total }} {{ total === 1 ? "registro activo" : "registros activos" }}
            <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
        </summary>
        <dl class="br-catalog-disclosure__values">
            <div v-for="entry in entries" :key="entry.type" class="br-catalog-disclosure__row">
                <dt class="colon-at-end">{{ entry.label }}</dt>
                <dd>{{ entry.count }}</dd>
            </div>
        </dl>
    </details>
    <span v-else class="br-entity-table__meta mt-0 text-center">{{ entries.length ? "Sin uso" : "Sin módulos disponibles" }}</span>
</template>

<script>
const TYPES = [
    {type: "product", label: "Productos", field: "active_products_count"},
    {type: "service", label: "Servicios", field: "active_services_count"},
    {type: "subscription", label: "Membresías", field: "active_subscriptions_count"}
];

export default {
    name: "CatalogUsageBreakdown",
    props: {
        record: {type: Object, required: true},
        enabledTypes: {type: Array, default: () => []}
    },
    computed: {
        entries() {
            return TYPES
                .filter(({type}) => this.enabledTypes.includes(type))
                .map(({type, label, field}) => ({type, label, count: Math.max(0, Number(this.record[field]) || 0)}));
        },
        total() {
            return this.entries.reduce((sum, entry) => sum + entry.count, 0);
        }
    }
};
</script>
