<template>
    <FormModal modal-id="inventoryStockAlertsModal" title="Alertas de stock" modal-class="fg-inventory-dialog" modal-size="modal-xl" :show-submit="false">
        <template #body>
            <div class="fg-stack">
                <div class="fg-field">
                    <span class="fg-field__label">Almacén de trabajo</span>
                    <span class="fg-inventory-quantity">{{ warehouse?.displayLabel || warehouse?.label || 'Almacén no seleccionado' }}</span>
                </div>
                <div class="fg-grid" role="search" aria-label="Filtros de alertas">
                    <SearchInput id="inventoryAlertProduct" title="Producto" :model-value="productSearch"
                        :placeholder="productSearchPlaceholder" :disabled="loading || !warehouse?.code" maxlength="100"
                        @update:model-value="$emit('update:productSearch', $event)"/>
                    <div class="fg-inventory-alert-filters">
                        <FgField label="Stock mínimo" input-id="inventoryAlertMinimumStatus">
                            <FgSelect :model-value="minimumStatus" :options="minimumOptions" :disabled="loading || !warehouse?.code"
                                :clearable="false" :searchable="false" @update:model-value="$emit('update:minimumStatus', $event)"/>
                        </FgField>
                        <FgFilterActions :show-clear="false" :disabled="!warehouse?.code" :loading="loading" @search="$emit('search')"/>
                        <ActionsDropdown type="button" label="Acciones de alertas de stock" :disabled="loading || downloading || !alerts.total">
                            <li><button type="button" class="fg-menu__item" :disabled="loading || downloading || !alerts.total" @click="$emit('download', 'pdf')">Descargar PDF</button></li>
                            <li><button type="button" class="fg-menu__item" :disabled="loading || downloading || !alerts.total" @click="$emit('download', 'excel')">Descargar Excel</button></li>
                        </ActionsDropdown>
                    </div>
                </div>

                <section aria-label="Alertas abiertas del almacén" :aria-busy="loading">
                    <InventoryTable :columns="columns" min-width="740px" scroll-records :loading="loading" loading-label="Buscando alertas de stock…"
                        :error="error" :empty="!alerts.data?.length"
                        :empty-title="hasSearch ? 'No se encontraron productos con alertas' : 'No hay alertas de stock abiertas'"
                        :empty-description="hasSearch ? 'Prueba con otro filtro o término de búsqueda.' : 'Este almacén no tiene alertas abiertas.'"
                        @retry="$emit('retry')">
                        <tr v-for="alert in alerts.data" :key="alert.id">
                            <td><ProductIdentity variant="expandable" :product="alert.warehouse_item?.item"/></td>
                            <td class="fg-align-end">
                                <QuantityValue class="fg-inventory-quantity" :value="alert.quantity" :unit="alert.warehouse_item?.item?.measurement_unit"/>
                                <small class="fg-table-note">{{ stockStatusLabel(alert) }}</small>
                            </td>
                            <td class="fg-align-end"><QuantityValue :value="alert.minimum_stock" :unit="alert.warehouse_item?.item?.measurement_unit"/></td>
                            <td class="fg-align-end"><StockDifference :current="alert.quantity" :minimum="alert.minimum_stock" :unit="alert.warehouse_item?.item?.measurement_unit"/></td>
                            <td class="fg-align-center">{{ formatDate(alert.detected_at) }}</td>
                            <td class="fg-align-center">
                                <TableActionsDropdown v-if="canEdit" :label="`Acciones de ${alert.warehouse_item?.item?.name || 'producto'}`" :disabled="loading">
                                    <li><button type="button" class="fg-menu__item" @click="$emit('edit', alert)">Modificar stock</button></li>
                                </TableActionsDropdown>
                            </td>
                        </tr>
                    </InventoryTable>
                    <Paginator aria-label="Paginación de alertas de stock" :links="alerts.links" :pagination="alerts" :loading="loading" @click-page="$emit('page', $event)"/>
                </section>
            </div>
        </template>
    </FormModal>
</template>

<script setup>
import ActionsDropdown from "@System/Components/Generics/ActionsDropdown.vue";
import FormModal from "@System/Components/Generics/FormModal.vue";
import ProductIdentity from "@System/Components/Generics/ProductIdentity.vue";
import QuantityValue from "@System/Components/Generics/QuantityValue.vue";
import SearchInput from "@System/Components/Generics/SearchInput.vue";
import TableActionsDropdown from "@System/Components/Generics/TableActionsDropdown.vue";
import FgField from "@System/Components/Figma/FgField.vue";
import FgFilterActions from "@System/Components/Figma/FgFilterActions.vue";
import FgSelect from "@System/Components/Figma/FgSelect.vue";
import Paginator from "@System/Components/Paginator.vue";
import {fromLevels, labels} from "../../../../Shared/StockStatus.js";
import InventoryTable from "./InventoryTable.vue";
import StockDifference from "./StockDifference.vue";
import {inventoryViews} from "./inventoryPresentation.js";

const columns = inventoryViews.stock.columns.map(column =>
    column.label === "Estado del stock" ? {label: "Detectada", width: "19%", align: "center"} : column
);

defineEmits(["page", "retry", "download", "update:productSearch", "update:minimumStatus", "search", "edit"]);
defineProps({
    alerts: {type: Object, required: true},
    warehouse: Object,
    productSearch: String,
    productSearchPlaceholder: {type: String, required: true},
    minimumStatus: Object,
    minimumOptions: {type: Array, required: true},
    hasSearch: Boolean,
    downloading: Boolean,
    loading: Boolean,
    canEdit: Boolean,
    error: String
});
function stockStatusLabel(alert) {
    return labels[fromLevels(alert.quantity, alert.minimum_stock)];
}
function formatDate(value) {
    if(!value) return "—";
    return new Intl.DateTimeFormat("es-PE", {dateStyle: "short", timeStyle: "short"}).format(new Date(value));
}
</script>
