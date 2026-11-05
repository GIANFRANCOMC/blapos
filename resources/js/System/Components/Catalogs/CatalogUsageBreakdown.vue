<template>
    <div v-if="entries.length" class="br-catalog-usage" role="group" :aria-label="summary">
        <div class="br-catalog-usage__rows">
            <div v-for="entry in entries" :key="entry.type" class="br-catalog-usage__row">
                <span class="br-catalog-usage__label">
                    <span class="br-catalog-usage__dot" :class="`br-catalog-usage__dot--${entry.type}`" aria-hidden="true"></span>
                    {{ entry.label }}
                </span>
                <span class="br-catalog-usage__count">{{ entry.count }}</span>
            </div>
        </div>
        <div class="br-catalog-usage__track" aria-hidden="true">
            <span
                v-for="entry in entries"
                :key="entry.type"
                class="br-catalog-usage__segment"
                :class="`br-catalog-usage__segment--${entry.type}`"
                :style="{width: `${total ? entry.count / total * 100 : 0}%`}"></span>
        </div>
    </div>
    <span v-else class="br-entity-table__meta">Sin módulos disponibles</span>
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
        },
        summary() {
            return this.entries.map(({label, count}) => `${label}: ${count}`).join(", ");
        }
    }
};
</script>
