<template>
    <details v-if="total" class="fg-usage">
        <summary class="fg-usage__summary fg-link">
            {{ total }} {{ total === 1 ? "registro activo" : "registros activos" }}
            <span class="fg-chevron" aria-hidden="true"></span>
        </summary>
        <dl class="fg-usage__values">
            <div v-for="entry in entries" :key="entry.type" class="fg-usage__row">
                <dt>{{ entry.label }}</dt>
                <dd>{{ entry.count }}</dd>
            </div>
        </dl>
    </details>
    <span v-else class="fg-help">{{ entries.length ? "Sin uso" : "Sin módulos disponibles" }}</span>
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
