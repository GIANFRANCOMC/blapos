<template>
    <Breadcrumb :list="breadcrumbTitles"/>

    <InventorySkeleton v-if="initialLoading && skeletonEnabled" :page="page"/>

    <section v-else class="br-inventory">
        <div class="row align-items-end g-2 br-inventory__context">
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
                md="6"
                @search="activeView === 'stock' ? handleProductSearch() : searchCurrentView()"
                @clear="clearProductSearch"/>

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

            <div :class="contextActionColumns">
                <div class="br-filter-bar__actions br-inventory__context-actions">
                    <button
                        type="button"
                        class="br-btn br-btn-sm br-btn-search"
                        :disabled="!filters.warehouse?.code || isCurrentViewLoading"
                        @click="activeView === 'stock' ? handleProductSearch() : searchCurrentView()">
                        <span>Buscar</span>
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
                            <button type="button" class="dropdown-item" :disabled="isCurrentViewLoading || exporting || !filters.warehouse?.code" @click="exportCurrentView">
                                <i class="fa-solid fa-file-excel" aria-hidden="true"></i>
                                <span>Descargar Excel</span>
                            </button>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="br-inventory__workspace">
            <section class="br-inventory__content">
                 <section
                    v-if="openStockAlerts.length"
                    class="br-inventory-alerts"
                    aria-label="Alertas de stock">
                    <div class="br-inventory-alerts__summary">
                        <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
                        <strong>{{ openStockAlerts.length }} alerta{{ openStockAlerts.length === 1 ? "" : "s" }} de stock</strong>
                        <span>Productos en mínimo o sin existencias dentro del almacén seleccionado.</span>
                    </div>
                    <div class="br-inventory-alerts__list">
                        <span
                            v-for="alert in openStockAlerts.slice(0, 4)"
                            :key="alert.id"
                            class="br-inventory-alerts__item">
                            {{ alertProductName(alert) }}
                        </span>
                        <span v-if="openStockAlerts.length > 4" class="br-inventory-alerts__item is-muted">
                            +{{ openStockAlerts.length - 4 }} más
                        </span>
                    </div>
                </section>

                <section class="br-entity-list" aria-label="Control de stock">
                    <div class="table-responsive">
                        <table class="table br-entity-table br-inventory__table mb-0">
                            <colgroup>
                                <col class="br-inventory__col-product">
                                <col class="br-inventory__col-current">
                                <col class="br-inventory__col-minimum">
                                <col class="br-inventory__col-warehouses">
                                <col class="br-inventory__col-status">
                            </colgroup>
                            <thead class="br-table-header-surface">
                                <tr>
                                    <th>Producto</th>
                                    <th class="text-end">Stock actual</th>
                                    <th class="text-end">Stock mínimo</th>
                                    <th>Almacenes</th>
                                    <th>Situación</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="loadingStock">
                                    <td colspan="5" class="py-4"><Loader/></td>
                                </tr>
                                <template v-else-if="stockRecords.total > 0">
                                    <tr
                                        v-for="record in stockRecords.data"
                                        :key="record.id"
                                        :class="{ 'br-inventory__scanned-row': scannedProductId === Number(record.id) }">
                                        <td>
                                            <strong class="br-inventory__product-name">{{ record.name }}</strong>
                                            <span class="br-inventory__product-identifiers">
                                                <span>
                                                    <small>Cód. interno</small>
                                                    <strong>{{ record.internal_code }}</strong>
                                                </span>
                                                <span v-if="record.barcode">
                                                    <small>Cód. barras</small>
                                                    <strong>{{ record.barcode }}</strong>
                                                </span>
                                            </span>
                                        </td>
                                        <td class="text-end"><strong>{{ quantityWithUnit(record.stock_quantity, record) }}</strong></td>
                                        <td class="text-end">{{ quantityWithUnit(record.minimum_stock, record) }}</td>
                                        <td>
                                            <div class="br-inventory-warehouses">
                                                <span
                                                    v-for="warehouse in stockWarehouseBreakdown(record).slice(0, 3)"
                                                    :key="`${record.id}-${warehouse.warehouse_id}`"
                                                    :class="['br-inventory-warehouse-pill', {'is-alert': warehouse.requires_attention}]">
                                                    {{ warehouse.branch_name }} - {{ warehouse.warehouse_name }}:
                                                    <strong>{{ quantityWithUnit(warehouse.quantity, record) }}</strong>
                                                </span>
                                                <span v-if="stockWarehouseBreakdown(record).length > 3" class="br-inventory-warehouse-pill is-muted">
                                                    +{{ stockWarehouseBreakdown(record).length - 3 }} más
                                                </span>
                                            </div>
                                        </td>
                                        <td>
                                            <span :class="['br-badge', stockStatus(record).badgeClass]">
                                                <i :class="stockStatus(record).icon" aria-hidden="true"></i>
                                                {{ stockStatus(record).label }}
                                            </span>
                                        </td>
                                    </tr>
                                </template>
                                <tr v-else>
                                    <td colspan="5" class="py-4">
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

            <nav
                v-if="!isCurrentViewLoading && currentRecords.total > 0 && currentRecords.links"
                class="d-flex justify-content-center mt-3"
                :aria-label="`Paginación de ${currentView.title.toLocaleLowerCase('es')}`">
                <Paginator :links="currentRecords.links" @clickPage="listCurrentView"/>
            </nav>
        </div>
    </section>
    <div
        
        id="inventoryMovementModal"
        class="modal fade br-entity-modal br-modal-standard"
        data-bs-backdrop="static"
        data-bs-keyboard="false"
        tabindex="-1"
        aria-labelledby="inventoryMovementModalTitle"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header br-entity-modal__header">
                    <h2 id="inventoryMovementModalTitle" class="modal-title br-entity-modal__title">
                        REGISTRAR OPERACIÓN
                    </h2>
                    <button type="button" class="br-modal-close" data-bs-dismiss="modal" aria-label="Cerrar">
                        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                    </button>
                </div>

                <div class="modal-body br-entity-modal__body">
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
                                :class="['table', 'br-entity-table', 'br-inventory-operation__table', 'mb-0', {'has-unit-cost': movementForm.operation?.movementType === 'entry'}]">
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
                                            <small v-if="item.product && item.currentStock === null" class="br-inventory-operation__current-stock">
                                                Unidad: {{ item.product.unitPlural.toLocaleLowerCase("es") }}
                                            </small>
                                            <small
                                                v-if="item.currentStock !== null"
                                                class="br-inventory-operation__current-stock">
                                                Stock actual: {{ quantityWithUnit(item.currentStock, item.product) }}
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
                </div>

                <div class="modal-footer br-entity-modal__footer">
                    <button
                        ref="closeMovementModal"
                        type="button"
                        class="br-btn br-btn-cancel"
                        data-bs-dismiss="modal">
                        Cancelar
                    </button>
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
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import inventoryPage from "./inventoryState.js";

export default inventoryPage;
</script>
