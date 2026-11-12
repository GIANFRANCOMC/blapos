<template>
    <FormModal
        modal-id="inventoryStockAlertsModal"
        title="ALERTAS DE STOCK"
        modal-class="br-modal-standard br-inventory-alerts"
        modal-size="modal-xl"
        body-class="br-inventory-alerts__body"
        :show-submit="false"
        close-button-text="Cerrar">
        <template #body>
            <header class="br-inventory-alerts__summary">
                <div class="br-field-summary">
                    <span class="form-label">Almacén de trabajo</span>
                    <strong class="br-field-summary__value" :title="warehouse?.displayLabel || warehouse?.label || ''">{{ warehouse?.displayLabel || warehouse?.label || 'Almacén no seleccionado' }}</strong>
                </div>
            </header>

            <div class="br-inventory-alerts__toolbar">
                <div class="br-inventory-alerts__filters" role="search">
                    <div class="col-12 col-sm-6 br-inventory-alerts__product-filter">
                        <label for="inventoryAlertProduct" class="form-label">Producto</label>
                        <SearchInput
                            id="inventoryAlertProduct"
                            :model-value="productSearch"
                            :input-class="['form-control']"
                            placeholder="Buscar o escanear producto"
                            :disabled="loading || !warehouse?.code"
                            maxlength="100"
                            @update:model-value="$emit('update:productSearch', $event)"/>
                    </div>
                    <div class="col-12 col-sm-4 col-xl-3">
                        <label for="inventoryAlertMinimumStatus" class="form-label">Stock mínimo</label>
                        <v-select
                            id="inventoryAlertMinimumStatus"
                            :model-value="minimumStatus"
                            :options="minimumOptions"
                            :class="selectClass"
                            :disabled="loading || !warehouse?.code"
                            :clearable="false"
                            :searchable="false"
                            @update:model-value="$emit('update:minimumStatus', $event)"/>
                    </div>
                    <SearchButton :disabled="loading || !warehouse?.code" @click="$emit('search')"/>
                    <div class="dropdown br-inventory-alerts__actions">
                        <button
                            type="button"
                            class="br-btn br-btn-actions"
                            :disabled="loading || downloading || !alerts.total"
                            data-bs-toggle="dropdown"
                            aria-expanded="false">
                            <span>Acciones</span>
                            <i class="fa-solid fa-chevron-down br-btn-actions__chevron" aria-hidden="true"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end br-btn-actions-menu">
                            <li>
                                <button type="button" class="dropdown-item" :disabled="loading || downloading || !alerts.total" @click="$emit('download', 'pdf')">
                                    <i class="fa-solid fa-file-pdf" aria-hidden="true"></i>
                                    <span>Descargar PDF</span>
                                </button>
                            </li>
                            <li>
                                <button type="button" class="dropdown-item" :disabled="loading || downloading || !alerts.total" @click="$emit('download', 'excel')">
                                    <i class="fa-solid fa-file-excel" aria-hidden="true"></i>
                                    <span>Descargar Excel</span>
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <section class="br-entity-list br-inventory-alerts__list" aria-label="Alertas abiertas del almacén" :aria-busy="loading">
                <div class="table-responsive br-inventory-alerts__table-shell">
                    <div class="br-inventory-alerts__table-group">
                        <div class="br-inventory-alerts__table-head">
                            <table class="table br-entity-table br-inventory-table-headings br-inventory-alerts__table mb-0" aria-hidden="true">
                                <colgroup><col><col><col><col><col><col></colgroup>
                                <thead class="br-table-header-surface">
                                    <tr>
                                        <th>Producto</th>
                                        <th>Stock actual</th>
                                        <th>Stock mínimo</th>
                                        <th>Diferencia</th>
                                        <th>Detectada</th>
                                        <th><span class="visually-hidden">Acciones</span></th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                        <div class="br-inventory-alerts__table-scroll">
                            <table class="table br-entity-table br-inventory-table-headings br-inventory-alerts__table mb-0" aria-label="Listado de alertas de stock">
                                <colgroup><col><col><col><col><col><col></colgroup>
                                <thead class="visually-hidden">
                                    <tr>
                                        <th scope="col">Producto</th>
                                        <th scope="col">Stock actual</th>
                                        <th scope="col">Stock mínimo</th>
                                        <th scope="col">Diferencia</th>
                                        <th scope="col">Detectada</th>
                                        <th scope="col"><span class="visually-hidden">Acciones</span></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="br-table-state" v-if="loading && !alerts.data?.length">
                                        <td colspan="6" class="py-4">
                                            <WithoutData
                                                class="br-entity-empty-state"
                                                type="image"
                                                text="Preparando resultados"
                                                description="El listado aparecerá al terminar la consulta."/>
                                        </td>
                                    </tr>
                                    <tr class="br-table-state" v-else-if="error && !alerts.data?.length">
                                        <td colspan="6" class="py-4 text-center">
                                            <span class="text-danger">{{ error }}</span>
                                            <button type="button" class="br-btn br-btn-sm br-btn-cancel ms-2" @click="$emit('retry')">Reintentar</button>
                                        </td>
                                    </tr>
                                    <template v-else-if="alerts.data?.length">
                                        <tr v-for="alert in alerts.data" :key="alert.id">
                                            <td>
                                                <ProductIdentity variant="expandable" :product="alert.warehouse_item?.item"/>
                                            </td>
                                            <td class="text-end">
                                                <QuantityValue class="br-inventory__stock-current" :value="alert.quantity" :unit="alert.warehouse_item?.item?.measurement_unit"/>
                                                <small class="br-entity-table__meta">{{ stockStatusLabel(alert) }}</small>
                                            </td>
                                            <td class="text-end"><QuantityValue :value="alert.minimum_stock" :unit="alert.warehouse_item?.item?.measurement_unit"/></td>
                                            <td class="text-end"><StockDifference :current="alert.quantity" :minimum="alert.minimum_stock" :unit="alert.warehouse_item?.item?.measurement_unit"/></td>
                                            <td class="text-center">{{ formatDate(alert.detected_at) }}</td>
                                            <td class="text-center">
                                                <TableActionsDropdown v-if="canEdit" :label="`Acciones de ${alert.warehouse_item?.item?.name || 'producto'}`" :disabled="loading">
                                                    <li>
                                                        <button type="button" class="dropdown-item" @click="$emit('edit', alert)">
                                                            <i class="fa-solid fa-pen" aria-hidden="true"></i>
                                                            <span>Modificar stock</span>
                                                        </button>
                                                    </li>
                                                </TableActionsDropdown>
                                            </td>
                                        </tr>
                                    </template>
                                    <tr class="br-table-state" v-else>
                                        <td colspan="6" class="py-4">
                                            <WithoutData
                                                class="br-entity-empty-state"
                                                type="image"
                                                :text="hasSearch ? 'No se encontraron productos con alertas.' : 'No hay alertas de stock abiertas.'"
                                                :description="hasSearch ? 'Prueba con otro filtro o término de búsqueda.' : 'Este almacén no tiene alertas abiertas.'"/>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>
            <p v-if="error && alerts.data?.length" class="br-inventory-alerts__error" role="alert">{{ error }}</p>
            <Paginator aria-label="Paginación de alertas de stock" :links="alerts.links" :pagination="alerts" :loading="loading" @clickPage="$emit('page', $event)"/>
        </template>
    </FormModal>
</template>

<script>
import SearchButton from "@/Shared/SearchButton.vue";
import FormModal from "@System/Components/Generics/FormModal.vue";
import ProductIdentity from "@System/Components/Generics/ProductIdentity.vue";
import QuantityValue from "@System/Components/Generics/QuantityValue.vue";
import SearchInput from "@System/Components/Generics/SearchInput.vue";
import WithoutData from "@System/Components/WithoutData.vue";
import StockDifference from "./StockDifference.vue";
import TableActionsDropdown from "@System/Components/Generics/TableActionsDropdown.vue";
import {fromLevels, labels} from "../../../../Shared/StockStatus.js";

export default {
    name: "StockAlertsModal",
    components: {SearchButton, FormModal, ProductIdentity, QuantityValue, SearchInput, WithoutData, StockDifference, TableActionsDropdown},
    emits: ["page", "retry", "download", "update:productSearch", "update:minimumStatus", "search", "edit"],
    props: {
        alerts: {type: Object, required: true},
        warehouse: {type: Object, default: null},
        productSearch: {type: String, default: ""},
        minimumStatus: {type: Object, default: null},
        minimumOptions: {type: Array, required: true},
        selectClass: {type: [String, Array, Object], default: ""},
        hasSearch: {type: Boolean, default: false},
        downloading: {type: Boolean, default: false},
        loading: {type: Boolean, default: false},
        canEdit: {type: Boolean, default: false},
        error: {type: String, default: ""}
    },
    methods: {
        stockStatusLabel(alert) {
            return labels[fromLevels(alert.quantity, alert.minimum_stock)];
        },
        formatDate(value) {
            if(!value) return "—";
            return new Intl.DateTimeFormat("es-PE", {dateStyle: "short", timeStyle: "short"}).format(new Date(value));
        }
    }
};
</script>
