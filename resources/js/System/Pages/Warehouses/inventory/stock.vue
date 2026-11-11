<template>
    <Breadcrumb :list="breadcrumbTitles"/>

    <InitialLoadTransition>
        <InventorySkeleton v-if="initialLoading" :page="page"/>

        <section v-else class="br-inventory">
            <div class="row align-items-end g-3 br-inventory__context">
                <div class="col-12 col-md-6 col-lg-3 col-xl-3 br-inventory__warehouse">
                    <label for="inventoryWarehouse" class="form-label">Almacén de trabajo</label>
                    <WarehouseSelect
                        id="inventoryWarehouse"
                        v-model="filters.warehouse"
                        :options="warehouses"
                        :disabled="isCurrentViewLoading"
                        :select-class="config.forms.classes.select2"/>
                </div>

                <SearchInput
                    v-if="['stock', 'transfers'].includes(activeView)"
                    ref="productSearch"
                    v-model.trim="filters.productSearch"
                    hasDiv
                    title="Producto"
                    :titleClass="[config.forms.classes.title]"
                    placeholder="Buscar o escanear producto"
                    :inputClass="['form-control']"
                    :disabled="isCurrentViewLoading || !filters.warehouse?.code"
                    xl="3"
                    lg="3"
                    md="6"/>

                <div v-if="activeView === 'stock'" class="col-12 col-md-6 col-lg-2 col-xl-2">
                    <label for="stockSituation" class="form-label">Estado del stock</label>
                    <v-select
                        id="stockSituation"
                        v-model="filters.stockSituation"
                        :options="stockSituations"
                        :class="config.forms.classes.select2"
                        :disabled="isCurrentViewLoading || !filters.warehouse?.code"
                        :clearable="false"
                        :searchable="false"/>
                </div>

                <StockAdvancedFilters
                    v-if="activeView === 'stock'"
                    v-model:category="filters.stockCategory"
                    v-model:brand="filters.stockBrand"
                    v-model:minimum-status="filters.stockMinimumStatus"
                    v-model:inactive-days="filters.stockInactiveDays"
                    :categories="stockCategories"
                    :brands="stockBrands"
                    :minimum-options="stockMinimumOptions"
                    :inactivity-options="stockInactivityOptions"
                    :select-class="config.forms.classes.select2"
                    :disabled="isCurrentViewLoading || !filters.warehouse?.code"/>

                <div v-if="['kardex', 'valued'].includes(activeView)" class="col-12 col-md-6 col-lg-2 col-xl-2">
                    <label for="movementType" class="form-label">Movimiento</label>
                    <v-select
                        id="movementType"
                        v-model="filters.movementType"
                        :options="movementTypes"
                        :class="config.forms.classes.select2"
                        :disabled="isCurrentViewLoading || !filters.warehouse?.code"
                        :clearable="false"
                        :searchable="false"/>
                </div>

                <div v-if="activeView === 'guides'" class="col-12 col-md-6 col-lg-2 col-xl-2">
                    <label for="guideType" class="form-label">Tipo de guía</label>
                    <v-select
                        id="guideType"
                        v-model="filters.guideType"
                        :options="guideTypes"
                        :class="config.forms.classes.select2"
                        :disabled="isCurrentViewLoading || !filters.warehouse?.code"
                        :clearable="false"
                        :searchable="false"/>
                </div>

                <template v-if="['kardex', 'valued', 'guides'].includes(activeView)">
                    <div class="col-12 col-md-6 col-lg-2 col-xl-2">
                        <label for="inventoryDateFrom" class="form-label">Desde</label>
                        <input id="inventoryDateFrom" v-model="filters.dateFrom" type="date" class="form-control" :disabled="isCurrentViewLoading || !filters.warehouse?.code">
                    </div>
                    <div class="col-12 col-md-6 col-lg-2 col-xl-2">
                        <label for="inventoryDateTo" class="form-label">Hasta</label>
                        <input id="inventoryDateTo" v-model="filters.dateTo" type="date" class="form-control" :disabled="isCurrentViewLoading || !filters.warehouse?.code">
                    </div>
                </template>

                <div class="col-auto flex-grow-1 d-flex flex-wrap align-items-end gap-2">
                    <div class="br-inventory__context-actions d-flex flex-nowrap align-items-stretch gap-2">
                        <SearchButton
                            class="br-btn-sm"
                            :disabled="!filters.warehouse?.code || isCurrentViewLoading"
                            @click="searchCurrentView"/>
                        <ClearFiltersButton
                            :disabled="isCurrentViewLoading || !filters.warehouse?.code"
                            @clear="clearFilters"/>
                        <button
                            type="button"
                            class="br-btn br-btn-sm br-btn-actions"
                            :disabled="isCurrentViewLoading || !filters.warehouse?.code"
                            data-bs-toggle="dropdown"
                            aria-expanded="false">
                            <span>Acciones</span>
                            <i class="fa-solid fa-chevron-down br-btn-actions__chevron" aria-hidden="true"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end br-btn-actions-menu">
                            <li>
                                <button type="button" class="dropdown-item" :disabled="isCurrentViewLoading || exporting || !appliedFilters.warehouse?.code" @click="exportCurrentView">
                                    <i class="fa-solid fa-file-excel" aria-hidden="true"></i>
                                    <span>Descargar Excel</span>
                                </button>
                            </li>
                        </ul>
                    </div>
                    <div v-if="['stock', 'transfers'].includes(activeView)" class="br-inventory__context-actions d-flex flex-nowrap align-items-stretch gap-2 ms-auto">
                        <span v-if="activeView === 'stock'" class="vr align-self-center" aria-hidden="true"></span>
                        <button
                            v-if="activeView === 'transfers'"
                            type="button"
                            class="br-btn br-btn-sm br-btn-action-open-create waves-effect"
                            :disabled="isCurrentViewLoading"
                            data-bs-toggle="modal"
                            data-bs-target="#inventoryTransferModal"
                            @click="prepareTransfer">
                            <i class="fa-solid fa-plus" aria-hidden="true"></i>
                            <span>Registrar traslado</span>
                        </button>
                        <button
                            v-if="activeView === 'stock'"
                            type="button"
                            class="br-btn br-btn-sm br-btn-action-open-create"
                            :disabled="isCurrentViewLoading"
                            data-bs-toggle="modal"
                            data-bs-target="#inventoryMovementModal"
                            @click="prepareMovement()">
                            <i class="fa-solid fa-plus" aria-hidden="true"></i>
                            <span>Registrar operación</span>
                        </button>
                        <button
                            v-if="activeView === 'stock'"
                            v-tooltip
                            type="button"
                            class="br-btn br-btn-sm br-btn-warning br-inventory__alerts-button waves-effect waves-light"
                            :disabled="!canViewStockAlerts"
                            aria-label="Ver alertas"
                            title="Ver alertas"
                            data-bs-toggle="tooltip"
                            data-bs-placement="top"
                            @click="viewStockAlerts">
                            <i class="fa-solid fa-bell fa-shake" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="br-inventory__workspace">
                <section class="br-inventory__content">
                    <section class="br-entity-list" aria-label="Control de stock">
                        <div class="table-responsive">
                            <table class="table br-entity-table br-inventory-table-headings br-inventory__table br-inventory__table--single-warehouse mb-0">
                                <colgroup>
                                    <col class="br-inventory__col-product">
                                    <col class="br-inventory__col-current">
                                    <col class="br-inventory__col-minimum">
                                    <col class="br-inventory__col-difference">
                                    <col class="br-inventory__col-status">
                                    <col class="br-entity-table__col-actions">
                                </colgroup>
                                <thead class="br-table-header-surface">
                                    <tr>
                                        <th>Producto</th>
                                        <th>Stock actual</th>
                                        <th>Stock mínimo</th>
                                        <th>Diferencia</th>
                                        <th class="text-nowrap px-0">Estado del stock</th>
                                        <th><span class="visually-hidden">Acciones</span></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="br-table-state" v-if="loadingStock">
                                        <td colspan="6" class="py-4"><Loader/></td>
                                    </tr>
                                    <template v-else-if="stockRecords.total > 0">
                                        <tr
                                            v-for="record in stockRecords.data"
                                            :key="record.id"
                                            :class="{ 'br-inventory__scanned-row': scannedProductId === Number(record.id) }">
                                            <td>
                                                <ProductIdentity variant="expandable" :product="record"/>
                                            </td>
                                            <td class="text-end"><QuantityValue class="br-inventory__stock-current" :value="record.stock_quantity" :unit="record.measurement_unit || record"/></td>
                                            <td class="text-end"><QuantityValue :value="record.minimum_stock" :unit="record.measurement_unit || record"/></td>
                                            <td class="text-end"><StockDifference :current="record.stock_quantity" :minimum="record.minimum_stock" :unit="record.measurement_unit"/></td>
                                            <td :class="['br-inventory__situation', stockStatus(record).cellClass]">{{ stockStatus(record).label }}</td>
                                            <td class="text-center">
                                                <TableActionsDropdown :label="`Acciones de ${record.name}`" :disabled="loadingStock || loadingAlerts || loadingTraceability">
                                                    <li v-if="canUpdateStock">
                                                        <button type="button" class="dropdown-item" @click="editStock(record)">
                                                            <i class="fa-solid fa-pen" aria-hidden="true"></i>
                                                            <span>Modificar stock</span>
                                                        </button>
                                                    </li>
                                                    <li>
                                                        <button type="button" class="dropdown-item" @click="openTraceability(record)">
                                                            <i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i>
                                                            <span>Ver trazabilidad</span>
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
                                                type="image"/>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </section>
                </section>

                <Paginator
                    v-if="appliedFilters.warehouse?.code"
                    :links="currentRecords.links"
                    :pagination="currentRecords"
                    :loading="isCurrentViewLoading"
                    @clickPage="listCurrentView"/>
            </div>
        </section>
    </InitialLoadTransition>
    <StockAlertsModal
        :alerts="stockAlerts"
        :warehouse="appliedFilters.warehouse"
        v-model:product-search="alertProductSearch"
        v-model:minimum-status="alertMinimumStatus"
        :minimum-options="stockMinimumOptions"
        :select-class="config.forms.classes.select2"
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
    <FormModal
        modal-id="inventoryMovementModal"
        title="REGISTRAR OPERACIÓN"
        modal-class="br-modal-standard"
        modal-size="modal-lg"
        dialog-class="modal-dialog-scrollable"
        :keyboard="false"
        :show-submit="false">
        <template #body>
                    <div class="br-inventory-operation__config">
                        <div>
                            <label for="operationWarehouse" class="form-label colon-at-end">Almacén de la operación</label>
                            <span class="text-danger ms-1 fw-bold" aria-hidden="true">*</span>
                            <WarehouseSelect
                                id="operationWarehouse"
                                v-model="movementForm.warehouse"
                                :options="realWarehouses"
                                :select-class="config.forms.classes.select2"
                                placeholder="Selecciona un almacén"
                                append-to-body/>
                            <small v-if="movementErrors.warehouse_id" class="text-danger">
                                {{ firstError(movementErrors.warehouse_id) }}
                            </small>
                        </div>
                        <div class="br-inventory-operation__warehouse-info">
                            <div class="br-inventory-operation__effect">
                                <strong>El stock cambia solo en el almacén elegido.</strong>
                            </div>
                        </div>
                        <div>
                            <label class="form-label colon-at-end" for="inventoryOperation">Tipo de operación</label>
                            <span class="text-danger ms-1 fw-bold" aria-hidden="true">{{ config.forms.inputs.required }}</span>
                            <v-select
                                input-id="inventoryOperation"
                                v-model="movementForm.operation"
                                :disabled="!canConfigureMovement"
                                :options="inventoryOperations"
                                :selectable="option => !option.isGroup"
                                :getOptionKey="option => option.code"
                                :class="config.forms.classes.select2"
                                :clearable="false"
                                :searchable="false"
                                append-to-body
                                aria-required="true"
                                placeholder="Selecciona un tipo de operación">
                                <template #option="option">
                                    <span v-if="option.isGroup" class="br-inventory-operation__option-group">
                                        {{ option.label }}
                                    </span>
                                    <span v-else class="br-inventory-operation__option-label">
                                        {{ option.label }}
                                    </span>
                                </template>
                            </v-select>
                            <small v-if="movementErrors.operation" class="text-danger">
                                {{ firstError(movementErrors.operation) }}
                            </small>
                        </div>
                        <div
                            v-if="movementForm.operation"
                            :class="['br-inventory-operation__description', `is-${movementForm.operation.movementType}`]"
                            aria-live="polite">
                            <div class="br-inventory-operation__effect" :title="movementForm.operation?.description">
                                <strong>{{ inventoryOperationEffect }}</strong>
                                <small>{{ movementForm.operation?.description }}</small>
                            </div>
                        </div>
                        <InputText
                            v-model.trim="movementForm.reason"
                            :disabled="!canConfigureMovement"
                            hasDiv
                            title="Motivo"
                            :titleClass="[config.forms.classes.title]"
                            :divClass="['br-inventory-operation__reason']"
                            maxlength="100"
                            showCharCounter
                            isRequired
                            hasTextBottom
                            :textBottomInfo="movementErrors.reason || []"/>
                    </div>

                    <div class="br-inventory-operation__items">
                        <div class="br-inventory-operation__template">
                            <div class="br-inventory-operation__items-title">
                                <span class="form-label colon-at-end">Productos incluidos</span>
                                <span class="text-danger fw-bold" aria-hidden="true">*</span>
                            </div>

                            <TemplateFilePicker
                                v-if="movementForm.operation"
                                :key="movementForm.operation.code"
                                id="operationFile"
                                :template-href="operationTemplateUrl"
                                :file-name="operationFileName"
                                :busy="previewingOperation || downloadingOperation"
                                :busy-text="downloadingOperation ? 'Generando plantilla...' : 'Revisando archivo...'"
                                :download-disabled="!operationTemplateUrl || !canConfigureMovement || downloadingOperation || previewingOperation"
                                :upload-disabled="!canConfigureMovement || previewingOperation || downloadingOperation || savingMovement"
                                described-by="operationFileHelp"
                                @download="downloadOperationTemplate"
                                @select="previewOperation"/>
                            <small v-if="movementForm.operation" id="operationFileHelp" class="br-inventory-operation__file-help">
                                Plantilla para {{ movementForm.operation.label.toLocaleLowerCase("es") }}. Solo se cargarán las filas con {{ operationAmountLabel.toLocaleLowerCase("es") }}; máximo 100 productos.
                            </small>
                            <small v-if="operationFileName" class="br-inventory-operation__file-status" role="status">
                                {{ movementForm.items.length }} productos listos para revisar antes de guardar.
                            </small>
                        </div>

                        <div class="table-responsive br-inventory-operation__table-wrap">
                            <table
                                :class="['table', 'br-entity-table', 'br-inventory-table-headings', 'br-inventory-operation__table', 'mb-0', {'has-unit-cost': movementForm.operation?.movementType === 'entry'}]">
                                <thead class="br-table-header-surface">
                                    <tr>
                                        <th scope="col">Producto</th>
                                        <th scope="col">
                                            {{ operationAmountLabel }}
                                        </th>
                                        <th v-if="movementForm.operation?.movementType === 'entry'" scope="col">
                                            <span class="br-inventory-operation__cost-heading">
                                                Costo unitario
                                                <small>Opcional</small>
                                                <button
                                                    type="button"
                                                    class="br-field-help"
                                                    data-bs-toggle="tooltip"
                                                    data-bs-placement="top"
                                                    title="Si se omite, se conserva el costo promedio actual."
                                                    aria-label="Información sobre el costo unitario">
                                                    <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                                                </button>
                                            </span>
                                        </th>
                                        <th scope="col"><span class="visually-hidden">Quitar</span></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(item, index) in movementForm.items" :key="item.key">
                                        <td>
                                            <ProductSelect
                                                v-model="item.product"
                                                :disabled="!canConfigureMovement"
                                                :options="movementProductOptions(index)"
                                                :select-class="config.forms.classes.select2"/>
                                            <small v-if="item.product?.unitAbbreviation && item.currentStock === null" class="br-inventory-operation__current-stock">
                                                Unidad: {{ item.product.unitAbbreviation }}
                                            </small>
                                            <small
                                                v-if="item.currentStock !== null"
                                                class="br-inventory-operation__current-stock">
                                                Stock actual: <QuantityValue :value="item.currentStock" :unit="item.product?.measurement_unit || item.product"/>
                                            </small>
                                            <small v-if="movementItemError(index, 'item_id')" class="text-danger">
                                                {{ movementItemError(index, "item_id") }}
                                            </small>
                                        </td>
                                        <td>
                                            <InputNumber v-model="item.amount" :hasNegative="false" :disabled="!canConfigureMovement"/>
                                            <small v-if="movementItemError(index, movementAmountField)" class="text-danger">
                                                {{ movementItemError(index, movementAmountField) }}
                                            </small>
                                        </td>
                                        <td v-if="movementForm.operation?.movementType === 'entry'">
                                            <InputNumber v-model="item.unitCost" :hasNegative="false" :disabled="!canConfigureMovement">
                                                <template #inputGroupPrepend>
                                                    <span class="input-group-text br-currency-prefix">S/</span>
                                                </template>
                                            </InputNumber>
                                            <small v-if="movementItemError(index, 'unit_cost')" class="text-danger">
                                                {{ movementItemError(index, "unit_cost") }}
                                            </small>
                                        </td>
                                        <td class="text-center">
                                            <span
                                                class="br-inventory-operation__remove-help"
                                                title="Quitar producto"
                                                data-bs-toggle="tooltip"
                                                data-bs-placement="top">
                                                <button
                                                    type="button"
                                                    class="br-icon-danger-plain"
                                                    :disabled="savingMovement || !canConfigureMovement || movementForm.items.length === 1"
                                                    :aria-label="`Quitar producto ${index + 1}`"
                                                    @click="removeMovementItem(index)">
                                                    <i class="fa-solid fa-trash" aria-hidden="true"></i>
                                                </button>
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
        </template>
        <template #footerClose>
                    <button
                        ref="closeMovementModal"
                        type="button"
                        class="br-btn br-btn-cancel"
                        data-bs-dismiss="modal">
                        Cancelar
                    </button>
        </template>
        <template #footer>
                    <div class="br-entity-modal__footer-actions">
                        <button
                            type="button"
                            class="br-btn br-btn-add-row"
                            :disabled="savingMovement || previewingOperation || !canConfigureMovement || movementForm.items.length >= 100"
                            @click="addMovementItem">
                            Agregar producto
                        </button>
                        <button
                            type="button"
                            class="br-btn br-btn-action-create"
                            :disabled="savingMovement || confirmingMovement || previewingOperation || !movementForm.warehouse?.code"
                            @click="saveMovement">
                            Guardar operación
                        </button>
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
