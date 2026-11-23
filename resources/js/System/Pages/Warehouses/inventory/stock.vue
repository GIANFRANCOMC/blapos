<template>
    <InventoryListing ref="inventoryListing" :page="page" v-model:filters="filters" :options="listingOptions"
        :initial-loading="initialLoading" :loading="isCurrentViewLoading" :records="currentRecords"
        :exporting="exporting" :applied-warehouse="Boolean(appliedFilters.warehouse?.code)" :error="listError"
        @search="searchCurrentView" @clear="clearFilters" @export="exportCurrentView" @page="listCurrentView" @retry="retryInventoryList">
        <template #actions>
            <FgButton icon="fa-solid fa-plus" :disabled="initialLoading || initializationFailed || isCurrentViewLoading" data-bs-toggle="modal" data-bs-target="#inventoryMovementModal" @click="prepareMovement()">Registrar operación</FgButton>
        </template>
        <template #tools>
            <FgButton tone="secondary" class="fg-button--icon fg-inventory-alert-trigger" icon="fa-solid fa-bell fa-shake" :disabled="initialLoading || !canViewStockAlerts" aria-label="Ver alertas" title="Ver alertas" data-bs-toggle="tooltip" @click="viewStockAlerts"/>
        </template>
        <tr v-for="record in stockRecords.data"
            :key="record.id"
            :class="{ 'fg-inventory-row--scanned': scannedProductId === Number(record.id) }">
            <td>
                <ProductIdentity variant="expandable" :product="record"/>
            </td>
            <td class="fg-align-end"><QuantityValue class="fg-inventory-quantity" :value="record.stock_quantity" :unit="record.measurement_unit || record"/></td>
            <td class="fg-align-end"><QuantityValue :value="record.minimum_stock" :unit="record.measurement_unit || record"/></td>
            <td class="fg-align-end"><StockDifference :current="record.stock_quantity" :minimum="record.minimum_stock" :unit="record.measurement_unit"/></td>
            <td class="fg-align-center"><span :class="['fg-badge', 'fg-badge--' + stockStatus(record).tone]">{{ stockStatus(record).label }}</span></td>
            <td class="fg-align-center">
                <TableActionsDropdown :label="`Acciones de ${record.name}`" :disabled="loadingStock || loadingAlerts || loadingTraceability">
                    <li v-if="canUpdateStock">
                        <button type="button" class="fg-menu__item" @click="editStock(record)">
                            <span>Modificar stock</span>
                        </button>
                    </li>
                    <li>
                        <button type="button" class="fg-menu__item" @click="openTraceability(record)">
                            <span>Ver trazabilidad</span>
                        </button>
                    </li>
                </TableActionsDropdown>
            </td>
        </tr>
    </InventoryListing>
    <StockAlertsModal
        :product-search-placeholder="productSearchPlaceholder"
        :alerts="stockAlerts"
        :warehouse="appliedFilters.warehouse"
        v-model:product-search="alertProductSearch"
        v-model:minimum-status="alertMinimumStatus"
        :minimum-options="stockMinimumOptions"
        :has-search="Boolean(appliedAlertProductSearch || appliedAlertMinimumStatus)"
        :downloading="downloadingAlerts"
        :loading="loadingAlerts"
        :can-edit="canUpdateStock"
        :error="stockAlertsError"
        @page="listStockAlerts"
        @retry="listStockAlerts({})"
        @search="searchStockAlerts"
        @edit="editStock($event, true)"
        @download="downloadStockAlerts"/>
    <StockItemEditor ref="stockEditor" :refresh="refreshEditedStock"/>
    <StockTraceabilityModal
        :product="traceabilityProduct"
        :warehouse="traceabilityWarehouse"
        :records="traceabilityRecords"
        :loading="loadingTraceability"
        :error="traceabilityError"
        :formatters="{formatDate, movementTypeLabel, originLabel}"
        @page="listTraceability"
        @retry="listTraceability({})"/>
    <FormModal modal-id="inventoryMovementModal" title="Registrar operación" modal-class="fg-inventory-dialog"
        modal-size="modal-lg" :keyboard="false" :show-submit="false" :close-disabled="savingMovement">
        <template #body>
            <div class="fg-stack">
                <div class="fg-section-heading"><h3 class="fg-section-title">Datos de la operación</h3><span class="fg-help"><span class="fg-required">*</span> Obligatorios</span></div>
                <div class="fg-grid">
                    <FgField label="Almacén de la operación" input-id="operationWarehouse" required :error="movementErrors.warehouse_id"
                        help="El stock cambia solo en el almacén elegido.">
                        <WarehouseSelect id="operationWarehouseSelect" input-id="operationWarehouse" v-model="movementForm.warehouse"
                            :options="realWarehouses" :disabled="savingMovement" placeholder="Selecciona un almacén"/>
                    </FgField>
                    <FgField label="Tipo de operación" input-id="inventoryOperation" required :error="movementErrors.operation" :help="movementForm.operation?.description">
                        <FgSelect v-model="movementForm.operation" :disabled="!canConfigureMovement || savingMovement" :options="inventoryOperations"
                            :selectable="option => !option.isGroup" :get-option-key="option => option.code" :clearable="false" :searchable="false"
                            placeholder="Selecciona un tipo de operación">
                            <template #option="option">
                                <span :class="option.isGroup && 'fg-help'">{{ option.label }}</span>
                            </template>
                        </FgSelect>
                    </FgField>
                </div>
                <p v-if="movementForm.operation" class="fg-inventory-notice" aria-live="polite">{{ inventoryOperationEffect }}</p>
                <InputText v-model.trim="movementForm.reason" :disabled="!canConfigureMovement || savingMovement" title="Motivo"
                    placeholder="Describe por qué se realiza la operación" maxlength="100" show-char-counter is-required has-text-bottom
                    :text-bottom-info="movementErrors.reason || []"/>

                <section class="fg-stack" aria-labelledby="inventoryOperationItemsTitle">
                    <h3 id="inventoryOperationItemsTitle" class="fg-section-title">Productos incluidos <span class="fg-required" aria-hidden="true">*</span></h3>
                    <div v-if="movementForm.operation" class="fg-field">
                        <TemplateFilePicker :key="movementForm.operation.code" id="operationFile"
                            :template-href="operationTemplateUrl" :file-name="operationFileName" :busy="previewingOperation || downloadingOperation"
                            :busy-text="downloadingOperation ? 'Generando plantilla…' : 'Revisando archivo…'"
                            :download-disabled="!operationTemplateUrl || !canConfigureMovement || downloadingOperation || previewingOperation"
                            :upload-disabled="!canConfigureMovement || previewingOperation || downloadingOperation || savingMovement"
                            described-by="operationFileHelp" @download="downloadOperationTemplate" @select="previewOperation"/>
                        <small id="operationFileHelp" class="fg-help">
                            Plantilla para {{ movementForm.operation.label.toLocaleLowerCase("es") }}. Solo se cargarán las filas con {{ operationAmountLabel.toLocaleLowerCase("es") }}. Máximo 100 productos.
                        </small>
                        <small v-if="operationFileName" class="fg-help" role="status">{{ movementForm.items.length }} productos listos para revisar antes de guardar.</small>
                    </div>
                    <div class="fg-table-scroll fg-table-scroll--records" role="region" aria-labelledby="inventoryOperationItemsTitle" tabindex="0">
                        <table class="fg-table fg-inventory-items">
                            <thead><tr>
                                <th scope="col">Producto</th>
                                <th scope="col">{{ operationAmountLabel }}</th>
                                <th v-if="movementForm.operation?.movementType === 'entry'" scope="col">
                                    Costo unitario
                                    <small class="fg-help">Opcional</small>
                                    <button type="button" class="fg-link" data-bs-toggle="tooltip" title="Si se omite, se conserva el costo promedio actual."
                                        aria-label="Información sobre el costo unitario"><i class="fa-solid fa-circle-info" aria-hidden="true"></i></button>
                                </th>
                                <th scope="col"><span class="fg-sr-only">Quitar</span></th>
                            </tr></thead>
                            <tbody>
                                <tr v-for="(item, index) in movementForm.items" :key="item.key">
                                    <td>
                                        <ProductSelect v-model="item.product" :disabled="!canConfigureMovement || savingMovement"
                                            :options="movementProductOptions(index)" :placeholder="productSearchPlaceholder" :aria-label="`Producto ${index + 1}`"/>
                                        <small v-if="item.product?.unitAbbreviation && item.currentStock === null" class="fg-table-note">Unidad: {{ item.product.unitAbbreviation }}</small>
                                        <small v-if="item.currentStock !== null" class="fg-table-note">
                                            Stock actual: <QuantityValue :value="item.currentStock" :unit="item.product?.measurement_unit || item.product"/>
                                        </small>
                                        <small v-if="movementItemError(index, 'item_id')" class="fg-error">{{ movementItemError(index, "item_id") }}</small>
                                    </td>
                                    <td>
                                        <InputNumber v-model="item.amount" :has-negative="false" :disabled="!canConfigureMovement || savingMovement"
                                            :aria-label="`${operationAmountLabel} del producto ${index + 1}`" has-text-bottom :text-bottom-info="[movementItemError(index, movementAmountField)].filter(Boolean)"/>
                                    </td>
                                    <td v-if="movementForm.operation?.movementType === 'entry'">
                                        <InputNumber v-model="item.unitCost" :has-negative="false" :disabled="!canConfigureMovement || savingMovement"
                                            :aria-label="`Costo unitario del producto ${index + 1}`" has-text-bottom :text-bottom-info="[movementItemError(index, 'unit_cost')].filter(Boolean)">
                                            <template #inputGroupPrepend><span class="fg-field__prefix">S/</span></template>
                                        </InputNumber>
                                    </td>
                                    <td class="fg-align-center">
                                        <FgButton tone="icon" class="fg-icon-danger" icon="fa-solid fa-trash"
                                            :disabled="savingMovement || confirmingMovement || previewingOperation" :aria-label="`Quitar producto ${index + 1}`"
                                            data-bs-toggle="tooltip" title="Quitar producto" @click="removeMovementItem(index)"/>
                                    </td>
                                </tr>
                                <tr v-if="!movementForm.items.length" class="fg-table__state">
                                    <td :colspan="movementForm.operation?.movementType === 'entry' ? 4 : 3"><p class="fg-inventory-empty-row">Agrega un producto a la operación.</p></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </template>
        <template #footer>
            <div class="fg-inventory-dialog-actions">
                <FgButton tone="secondary" :disabled="savingMovement || previewingOperation || !canConfigureMovement || movementForm.items.length >= 100" @click="addMovementItem">Agregar producto</FgButton>
                <FgButton :loading="savingMovement" :disabled="confirmingMovement || previewingOperation || !movementForm.warehouse?.code || !movementForm.items.length" @click="saveMovement">Guardar operación</FgButton>
            </div>
        </template>
    </FormModal>

</template>

<script>
import {createTooltip, dismissTooltip} from "@System/Helpers/Alerts.js";
import inventoryPage from "./inventoryState.js";

export default {
    extends: inventoryPage,
    directives: {
        tooltip: {
            mounted(button) {
                createTooltip(button);
            },
            beforeUnmount(button) {
                window.bootstrap?.Tooltip?.getInstance(button)?.dispose();
            }
        }
    },
    methods: {
        viewStockAlerts(event) {
            dismissTooltip(event.currentTarget);
            return this.listStockAlerts({showModal: true});
        }
    }
};
</script>
