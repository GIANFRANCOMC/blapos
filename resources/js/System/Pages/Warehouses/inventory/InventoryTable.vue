<template>
    <ResponsiveTable :scroll-y="scrollRecords" :class="{'fg-table-scroll--records': scrollRecords}">
        <table class="fg-table fg-inventory-table" :style="{minWidth}">
            <colgroup><col v-for="column in columns" :key="column.label" :style="{width: column.width}"/></colgroup>
            <thead>
                <tr>
                    <th v-for="column in columns" :key="column.label" scope="col" :class="column.align && `fg-align-${column.align}`">
                        <span :class="{'fg-sr-only': column.hidden}">{{ column.label }}</span>
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr v-if="loading" class="fg-table__state">
                    <td :colspan="columns.length"><FgListLoading :label="loadingLabel"/></td>
                </tr>
                <tr v-else-if="error || empty" class="fg-table__state">
                    <td :colspan="columns.length">
                        <FgEmptyState :title="error ? 'No pudimos cargar el listado' : emptyTitle" :description="error || emptyDescription">
                            <FgButton v-if="error" tone="secondary" @click="$emit('retry')">Volver a intentar</FgButton>
                        </FgEmptyState>
                    </td>
                </tr>
                <slot v-else/>
            </tbody>
        </table>
    </ResponsiveTable>
</template>

<script setup>
import ResponsiveTable from "@/Shared/ResponsiveTable.vue";
import FgListLoading from "@System/Components/Figma/FgListLoading.vue";
import FgEmptyState from "@System/Components/Figma/FgEmptyState.vue";
import FgButton from "@System/Components/Figma/FgButton.vue";

defineEmits(["retry"]);
defineProps({
    columns: {type: Array, required: true},
    minWidth: String,
    scrollRecords: Boolean,
    loading: Boolean,
    loadingLabel: String,
    error: String,
    empty: Boolean,
    emptyTitle: {type: String, default: "No se encontraron registros"},
    emptyDescription: String
});
</script>
