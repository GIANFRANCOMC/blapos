<template>
    <div class="fg-skeleton" inert>
        <div class="fg-filters fg-filter-grid" aria-hidden="true">
            <div v-for="field in fields" :key="field.key" class="fg-field fg-filter-grid__field" :class="{'fg-filter-grid__field--compact': field.compact}">
                <span class="fg-field__label">{{ field.label }}</span>
                <span class="fg-skeleton-control"><span class="fg-skeleton-line fg-catalog-skeleton__value"/></span>
            </div>
            <div class="fg-filter-grid__actions">
                <FgSkeletonFilterActions/>
            </div>
        </div>
        <div class="fg-list-heading" aria-hidden="true"><span class="fg-catalog-skeleton__heading"><span class="fg-skeleton-line"/></span></div>
        <InventoryTable :columns="view.columns" :min-width="view.minWidth" loading :loading-label="`Preparando ${view.title.toLocaleLowerCase('es')}…`"/>
    </div>
</template>

<script setup>
import FgSkeletonFilterActions from "@System/Components/Figma/FgSkeletonFilterActions.vue";
import InventoryTable from "./InventoryTable.vue";

defineProps({
    fields: {type: Array, required: true},
    view: {type: Object, required: true}
});
</script>
