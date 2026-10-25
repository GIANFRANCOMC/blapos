<template>
    <Breadcrumb :list="breadcrumbTitles"/>

    <section class="br-inventory">
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
                    v-if="activeView === 'stock' && openStockAlerts.length"
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

                <section v-if="activeView === 'stock'" class="br-entity-list" aria-label="Control de stock">
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

                <div v-else-if="['kardex', 'valued'].includes(activeView)" class="br-inventory__kardex">
                    <section class="br-entity-list" :aria-label="activeView === 'valued' ? 'Kardex valorizado' : 'Kardex'">
                        <div class="table-responsive">
                            <table class="table br-entity-table br-inventory__kardex-table mb-0">
                                <thead class="br-table-header-surface">
                                    <tr v-if="activeView === 'kardex'">
                                        <th>Fecha y responsable</th>
                                        <th>Producto</th>
                                        <th>Movimiento</th>
                                        <th class="text-end">Variación</th>
                                        <th class="text-end">Saldo</th>
                                        <th>Motivo y origen</th>
                                    </tr>
                                    <tr v-else>
                                        <th>Fecha y producto</th>
                                        <th>Movimiento</th>
                                        <th class="text-end">Variación</th>
                                        <th class="text-end">Costo unitario</th>
                                        <th class="text-end">Valor movimiento</th>
                                        <th class="text-end">Valor resultante</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-if="loadingKardex">
                                        <td colspan="6" class="py-4"><Loader/></td>
                                    </tr>
                                    <template v-else-if="kardexRecords.total > 0">
                                        <template v-if="activeView === 'kardex'">
                                            <tr v-for="movement in kardexRecords.data" :key="movement.id">
                                                <td>
                                                    <strong>{{ formatDate(movement.created_at) }}</strong>
                                                    <span class="br-inventory__meta">{{ movement.user?.name || "Proceso del sistema" }}</span>
                                                </td>
                                                <td>
                                                    <strong>{{ movement.item?.name }}</strong>
                                                    <span class="br-inventory__meta">{{ movement.item?.internal_code }}</span>
                                                </td>
                                                <td>
                                                    <span :class="['br-inventory-movement', `is-${movement.movement_type}`]">
                                                        {{ movementTypeLabel(movement.movement_type) }}
                                                    </span>
                                                </td>
                                                <td
                                                    :class="[
                                                        'text-end fw-semibold',
                                                        Number(movement.quantity_change) > 0 ? 'text-success' : 'text-danger'
                                                    ]">
                                                    {{ signedQuantity(movement.quantity_change, movement.item) }}
                                                </td>
                                                <td class="text-end">
                                                    <strong>{{ quantityWithUnit(movement.quantity_after, movement.item) }}</strong>
                                                    <span class="br-inventory__meta">
                                                        Antes: {{ quantityWithUnit(movement.quantity_before, movement.item) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <strong>{{ movement.reason }}</strong>
                                                    <span class="br-inventory__meta">
                                                        {{ originLabel(movement.origin_type) }}
                                                        <template v-if="movement.reference"> · {{ movement.reference }}</template>
                                                    </span>
                                                </td>
                                            </tr>
                                        </template>
                                        <template v-else>
                                            <tr v-for="movement in kardexRecords.data" :key="`valued-${movement.id}`">
                                                <td>
                                                    <strong>{{ formatDate(movement.created_at) }}</strong>
                                                    <span class="br-inventory__meta">
                                                        {{ movement.item?.name }} · {{ movement.item?.internal_code }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span :class="['br-inventory-movement', `is-${movement.movement_type}`]">
                                                        {{ movementTypeLabel(movement.movement_type) }}
                                                    </span>
                                                    <span class="br-inventory__meta">{{ originLabel(movement.origin_type) }}</span>
                                                </td>
                                                <td class="text-end fw-semibold">{{ signedQuantity(movement.quantity_change, movement.item) }}</td>
                                                <td class="text-end">{{ currencyNumber(movement.unit_cost, 4) }}</td>
                                                <td
                                                    :class="[
                                                        'text-end fw-semibold',
                                                        Number(movement.value_change) >= 0 ? 'text-success' : 'text-danger'
                                                    ]">
                                                    {{ signedCurrency(movement.value_change) }}
                                                </td>
                                                <td class="text-end">
                                                    <strong>{{ currencyNumber(movement.value_after) }}</strong>
                                                    <span class="br-inventory__meta">
                                                        Antes: {{ currencyNumber(movement.value_before) }}
                                                    </span>
                                                </td>
                                            </tr>
                                        </template>
                                    </template>
                                    <tr v-else>
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
                </div>

                <div v-else-if="activeView === 'transfers'" class="br-inventory-transfer">
                    <Teleport to="body">
                        <div
                            id="inventoryTransferModal"
                            class="modal fade br-entity-modal br-modal-standard"
                            data-bs-backdrop="static"
                            data-bs-keyboard="false"
                            tabindex="-1"
                            aria-labelledby="inventoryTransferModalTitle"
                            aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
                                <div class="modal-content">
                                    <div class="modal-header br-entity-modal__header">
                                        <h2 id="inventoryTransferModalTitle" class="modal-title br-entity-modal__title">
                                            REGISTRAR TRASLADO
                                        </h2>
                                        <button
                                            type="button"
                                            class="br-modal-close"
                                            :disabled="savingTransfer"
                                            data-bs-dismiss="modal"
                                            aria-label="Cerrar">
                                            <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                                        </button>
                                    </div>

                                    <div class="modal-body br-entity-modal__body">
                                        <div class="br-inventory-operation__config">
                                            <div>
                                                <label for="transferSource" class="form-label colon-at-end">Almacén de origen</label>
                                                <span class="text-danger ms-1 fw-bold" aria-hidden="true">*</span>
                                                <WarehouseSelect
                                                    id="transferSource"
                                                    v-model="transferForm.source"
                                                    :options="realWarehouses"
                                                    :select-class="config.forms.classes.select2"
                                                    placeholder="Selecciona un almacén"
                                                    append-to-body/>
                                                <small v-if="transferErrors.source_warehouse_id" class="text-danger">
                                                    {{ firstError(transferErrors.source_warehouse_id) }}
                                                </small>
                                            </div>

                                            <div>
                                                <label for="transferDestination" class="form-label colon-at-end">Almacén de destino</label>
                                                <span class="text-danger ms-1 fw-bold" aria-hidden="true">*</span>
                                                <WarehouseSelect
                                                    id="transferDestination"
                                                    v-model="transferForm.destination"
                                                    :options="destinationWarehouses"
                                                    :select-class="config.forms.classes.select2"
                                                    :disabled="!transferForm.source?.code"
                                                    :placeholder="transferForm.source ? 'Selecciona un almacén' : 'Selecciona primero el origen'"
                                                    append-to-body/>
                                                <small v-if="transferErrors.destination_warehouse_id" class="text-danger">
                                                    {{ firstError(transferErrors.destination_warehouse_id) }}
                                                </small>
                                            </div>

                                            <InputText
                                                v-model.trim="transferForm.reason"
                                                :disabled="!canConfigureTransfer"
                                                hasDiv
                                                title="Motivo del traslado"
                                                :titleClass="[config.forms.classes.title]"
                                                :divClass="['br-inventory-operation__reason']"
                                                placeholder="Describe por qué se realiza el traslado"
                                                maxlength="255"
                                                showCharCounter
                                                isRequired
                                                hasTextBottom
                                                :textBottomInfo="transferErrors.reason || []"/>
                                        </div>

                                        <div class="br-inventory-operation__items">
                                            <div class="br-inventory-operation__items-header">
                                                <div class="br-inventory-operation__items-title">
                                                    <span class="form-label colon-at-end">Productos incluidos</span>
                                                    <span class="text-danger fw-bold" aria-hidden="true">*</span>
                                                </div>
                                            </div>

                                            <div class="table-responsive br-inventory-operation__table-wrap">
                                                <table class="table br-entity-table br-inventory-operation__table mb-0">
                                                    <thead class="br-table-header-surface">
                                                        <tr>
                                                            <th scope="col">Producto</th>
                                            <th scope="col">Cantidad</th>
                                                            <th scope="col"><span class="visually-hidden">Quitar</span></th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr v-for="(item, index) in transferForm.items" :key="item.key">
                                                            <td>
                                                                <ProductSelect
                                                                    v-model="item.product"
                                                                    :disabled="!canConfigureTransfer"
                                                                    :options="transferProductOptions(index)"
                                                                    :select-class="config.forms.classes.select2"/>
                                                                <small v-if="item.product" class="br-inventory-operation__current-stock">
                                                                    Unidad: {{ item.product.unitPlural.toLocaleLowerCase("es") }}
                                                                </small>
                                                                <small v-if="transferItemError(index, 'item_id')" class="text-danger">
                                                                    {{ transferItemError(index, "item_id") }}
                                                                </small>
                                                            </td>
                                                            <td>
                                                                <InputNumber v-model="item.quantity" :hasNegative="false" :disabled="!canConfigureTransfer"/>
                                                                <small v-if="transferItemError(index, 'quantity')" class="text-danger">
                                                                    {{ transferItemError(index, "quantity") }}
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
                                                                        :disabled="savingTransfer || !canConfigureTransfer || transferForm.items.length === 1"
                                                                        :aria-label="`Quitar producto ${index + 1}`"
                                                                        @click="removeTransferItem(index)">
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
                                            ref="closeTransferModal"
                                            type="button"
                                            class="br-btn br-btn-cancel"
                                            :disabled="savingTransfer"
                                            data-bs-dismiss="modal">
                                            Cancelar
                                        </button>
                                        <div class="br-entity-modal__footer-actions">
                                            <button
                                                type="button"
                                                class="br-btn br-btn-add-row"
                                                :disabled="savingTransfer || !canConfigureTransfer || transferForm.items.length >= 100"
                                                @click="addTransferItem">
                                                Agregar producto
                                            </button>
                                            <button
                                                type="button"
                                                class="br-btn br-btn-action-create"
                                                :disabled="savingTransfer || !transferForm.source?.code || !transferForm.destination?.code"
                                                @click="saveTransfer">
                                                Guardar traslado
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </Teleport>

                    <section class="br-entity-list" aria-label="Traslados registrados">
                        <div class="table-responsive">
                            <table class="table br-entity-table br-inventory-transfer__table mb-0">
                                <colgroup>
                                    <col class="br-inventory-transfer__col-date">
                                    <col class="br-inventory-transfer__col-product">
                                    <col class="br-inventory-transfer__col-source">
                                    <col class="br-inventory-transfer__col-destination">
                                    <col class="br-inventory-transfer__col-movement">
                                    <col class="br-inventory-transfer__col-quantity">
                                    <col class="br-inventory-transfer__col-reference">
                                </colgroup>
                                <thead class="br-table-header-surface">
                                    <tr>
                                        <th>Fecha y responsable</th>
                                        <th>Producto</th>
                                        <th>Almacén origen</th>
                                        <th>Almacén destino</th>
                                        <th>Movimiento</th>
                                        <th class="text-end">Cantidad</th>
                                        <th>Referencia</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-if="loadingKardex">
                                        <td colspan="7" class="py-4"><Loader/></td>
                                    </tr>
                                    <template v-else-if="kardexRecords.total > 0">
                                        <tr v-for="movement in kardexRecords.data" :key="`transfer-${movement.id}`">
                                            <td>
                                                <strong>{{ formatDate(movement.created_at) }}</strong>
                                                <span class="br-inventory__meta">{{ movement.user?.name || "Proceso del sistema" }}</span>
                                            </td>
                                            <td>
                                                <strong>{{ movement.item?.name }}</strong>
                                                <span class="br-inventory__meta">{{ movement.item?.internal_code }}</span>
                                            </td>
                                            <td>
                                                <strong>{{ movement.transfer_source_warehouse?.name || "No disponible" }}</strong>
                                                <span v-if="movement.transfer_source_warehouse?.branch_name" class="br-inventory__meta">
                                                    {{ movement.transfer_source_warehouse.branch_name }}
                                                </span>
                                            </td>
                                            <td>
                                                <strong>{{ movement.transfer_destination_warehouse?.name || "No disponible" }}</strong>
                                                <span v-if="movement.transfer_destination_warehouse?.branch_name" class="br-inventory__meta">
                                                    {{ movement.transfer_destination_warehouse.branch_name }}
                                                </span>
                                            </td>
                                            <td>
                                                <span :class="['br-inventory-movement', `is-${movement.movement_type}`]">
                                                    {{ originLabel(movement.origin_type) }}
                                                </span>
                                            </td>
                                            <td class="text-end fw-semibold">{{ signedQuantity(movement.quantity_change, movement.item) }}</td>
                                            <td>
                                                <strong>{{ movement.metadata?.reference || movement.reference || "-" }}</strong>
                                                <span class="br-inventory__meta">{{ movement.reason }}</span>
                                            </td>
                                        </tr>
                                    </template>
                                    <tr v-else>
                                        <td colspan="7" class="py-4">
                                            <WithoutData
                                                class="br-entity-empty-state"
                                                type="image"/>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>

                <div v-else-if="activeView === 'guides'" class="br-inventory__kardex">
                    <section class="br-entity-list" aria-label="Guías de inventario">
                        <div class="table-responsive">
                            <table class="table br-entity-table br-inventory__kardex-table mb-0">
                                <thead class="br-table-header-surface">
                                    <tr>
                                        <th>Fecha y número</th>
                                        <th>Almacén</th>
                                        <th>Tipo</th>
                                        <th>Detalle</th>
                                        <th>Referencia</th>
                                        <th>Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-if="loadingGuides">
                                        <td colspan="6" class="py-4"><Loader/></td>
                                    </tr>
                                    <template v-else-if="guideRecords.total > 0">
                                        <tr v-for="guide in guideRecords.data" :key="`guide-${guide.id}`">
                                            <td>
                                                <strong>{{ formatSimpleDate(guide.issue_date) }}</strong>
                                                <span class="br-inventory__meta">{{ guide.number }}</span>
                                            </td>
                                            <td>
                                                <strong>{{ guide.warehouse?.name }}</strong>
                                                <span class="br-inventory__meta">{{ guide.warehouse?.branch?.name }}</span>
                                            </td>
                                            <td>
                                                <span :class="['br-inventory-movement', guide.guide_type === 'entry' ? 'is-entry' : 'is-exit']">
                                                    {{ guide.guide_type === "entry" ? "Entrada" : "Salida" }}
                                                </span>
                                            </td>
                                            <td>
                                                <strong>{{ (guide.items || []).length }} producto{{ (guide.items || []).length === 1 ? "" : "s" }}</strong>
                                                <span class="br-inventory__meta">
                                                    {{ (guide.items || []).slice(0, 2).map(item => `${item.item?.name}: ${quantityWithUnit(item.quantity, item.item)}`).join(", ") }}
                                                </span>
                                            </td>
                                            <td>
                                                <strong>{{ guide.reference || "-" }}</strong>
                                                <span class="br-inventory__meta">{{ guide.reason }}</span>
                                            </td>
                                            <td>
                                                <span class="br-status-label br-status-active">
                                                    {{ guide.status === "confirmed" ? "Confirmada" : guide.status }}
                                                </span>
                                            </td>
                                        </tr>
                                    </template>
                                    <tr v-else>
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
                </div>

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
        v-if="activeView === 'stock'"
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
import * as Alerts from "@System/Helpers/Alerts.js";
import * as Constants from "@System/Helpers/Constants.js";
import * as Requests from "@System/Helpers/Requests.js";
import * as Utils from "@System/Helpers/Utils.js";
import ProductSelect from "@System/Components/Generics/ProductSelect.vue";
import SearchInput from "@System/Components/Generics/SearchInput.vue";
import TemplateFilePicker from "@System/Components/Generics/TemplateFilePicker.vue";
import WarehouseSelect from "@System/Components/Generics/WarehouseSelect.vue";

const VIEWS = [
    {code: "stock", title: "Control de stock"},
    {code: "kardex", title: "Kardex"},
    {code: "transfers", title: "Traslados"},
    {code: "valued", title: "Kardex valorizado"},
    {code: "guides", title: "Guías"}
];

const VIEW_MENU_MAP = {
    stock: "menu-inventory-stock",
    kardex: "menu-inventory-kardex",
    transfers: "menu-inventory-transfers",
    valued: "menu-inventory-valued",
    guides: "menu-inventory-guides"
};

const MOVEMENT_TYPES = [
    {code: "", label: "Todos los movimientos"},
    {code: "entry", label: "Entrada"},
    {code: "exit", label: "Salida"},
    {code: "correction", label: "Corrección"}
];

const GUIDE_TYPES = [
    {code: "", label: "Todas las guías"},
    {code: "entry", label: "Entrada"},
    {code: "exit", label: "Salida"}
];

const INVENTORY_OPERATIONS = [
    {code: "manual_entry", label: "Entrada manual", amountLabel: "Cantidad de entrada", movementType: "entry", originType: "manual", description: "Suma las unidades indicadas."},
    {code: "manual_exit", label: "Salida manual", amountLabel: "Cantidad de salida", movementType: "exit", originType: "manual", description: "Resta las unidades indicadas."},
    {code: "physical_count", label: "Toma física", amountLabel: "Saldo contado", movementType: "correction", originType: "physical_count", description: "Usa el saldo contado."},
    {code: "customer_return", label: "Devolución de cliente", amountLabel: "Cantidad devuelta", movementType: "entry", originType: "customer_return", description: "Devuelve unidades al stock."},
    {code: "supplier_return", label: "Devolución a proveedor", amountLabel: "Cantidad al proveedor", movementType: "exit", originType: "supplier_return", description: "Resta unidades devueltas."}
];

const INVENTORY_OPERATION_GROUPS = [
    {code: "correction", label: "Actualización", effect: "Actualiza el saldo"},
    {code: "entry", label: "Ingresos", effect: "Aumenta el stock"},
    {code: "exit", label: "Salidas", effect: "Disminuye el stock"}
];

const INVENTORY_OPERATION_OPTIONS = INVENTORY_OPERATION_GROUPS.flatMap(group => [
    {code: `group-${group.code}`, label: group.label, isGroup: true},
    ...INVENTORY_OPERATIONS.filter(operation => operation.movementType === group.code)
]);

export default {
    components: {ProductSelect, SearchInput, TemplateFilePicker, WarehouseSelect},
    props: {
        page: {
            type: String,
            required: true,
            validator: value => VIEWS.some(view => view.code === value)
        }
    },
    async mounted() {
        Utils.navbarItem("menu-parent-items", {addClass: "open"});
        Utils.navbarItem(this.activeMenuId(), {});
        Alerts.swals({type: "initParams"});
        const initialized = await this.initParams();
        Alerts.swals({show: false});

        if(initialized && this.realWarehouses.length) {
            this.filters.warehouse = this.realWarehouses[0];
            await this.$nextTick();
            await this.listCurrentView({});
        }

        this.warehouseSearchEnabled = true;
    },
    data() {
        return {
            activeView: this.page,
            loadingStock: false,
            loadingKardex: false,
            loadingAlerts: false,
            loadingGuides: false,
            warehouseSearchEnabled: false,
            requestVersion: 0,
            savingMovement: false,
            confirmingMovement: false,
            previewingOperation: false,
            downloadingOperation: false,
            operationFileName: "",
            operationPreviewVersion: 0,
            savingTransfer: false,
            exporting: false,
            scannedProductId: null,
            stockRecords: {total: 0, data: []},
            stockAlerts: {total: 0, data: []},
            kardexRecords: {total: 0, data: []},
            guideRecords: {total: 0, data: []},
            filters: {
                warehouse: null,
                productSearch: "",
                movementType: MOVEMENT_TYPES[0],
                guideType: GUIDE_TYPES[0],
                dateFrom: "",
                dateTo: ""
            },
            movementForm: {
                operation: null,
                warehouse: null,
                items: [{
                    key: `initial-operation-${Date.now()}`,
                    product: null,
                    amount: "",
                    unitCost: ""
                }],
                reason: ""
            },
            movementErrors: {},
            transferForm: {
                source: null,
                destination: null,
                items: [{
                    key: `initial-transfer-${Date.now()}`,
                    product: null,
                    quantity: ""
                }],
                reason: ""
            },
            transferErrors: {},
            options: {warehouses: [], products: []},
            config: {
                ...Constants.generalConfig,
                entity: {
                    ...Requests.config({entity: "stocks_management"}),
                    page: {
                        title: VIEWS.find(view => view.code === this.page)?.title || "Inventario",
                        active: true,
                        menu: {id: VIEW_MENU_MAP[this.page]}
                    }
                }
            }
        };
    },
    methods: {
        activeMenuId() {
            return VIEW_MENU_MAP[this.activeView] || VIEW_MENU_MAP.stock;
        },
        async initParams() {
            const result = await Requests.get({
                route: this.config.entity.routes.initParams,
                data: {page: this.page},
                showAlert: true
            });
            this.options.warehouses = result.data?.config?.warehouses?.records || [];
            this.options.products = result.data?.config?.products?.records || [];
            return Requests.valid({result});
        },
        async listStock({url = null} = {}) {
            if(!this.filters.warehouse?.code) return;

            const requestVersion = ++this.requestVersion;
            const warehouseId = this.filters.warehouse.code;
            const isConsolidated = warehouseId === "all";
            const productSearch = this.filters.productSearch;

            this.loadingStock = true;

            const result = await Requests.get({
                route: url || Requests.config({
                    entity: "stocks_management",
                    type: isConsolidated ? "summary" : "list"
                }),
                data: {
                    warehouse_id: warehouseId,
                    product_search: productSearch
                }
            });

            if(requestVersion !== this.requestVersion) return;

            this.stockRecords = isConsolidated
                ? (result?.data?.data || {total: 0, data: []})
                : (result?.data || {total: 0, data: []});
            this.loadingStock = false;

            if(isConsolidated) {
                this.stockAlerts = {total: 0, data: []};
            }else {
                this.listStockAlerts({warehouseId, requestVersion});
            }
        },
        async listStockAlerts({warehouseId, requestVersion}) {
            this.loadingAlerts = true;

            const result = await Requests.get({
                route: Requests.config({entity: "stocks_management", type: "alerts"}),
                data: {
                    warehouse_id: warehouseId,
                    status: "open"
                }
            });

            if(requestVersion !== this.requestVersion) return;

            this.stockAlerts = result?.data || {total: 0, data: []};
            this.loadingAlerts = false;
        },
        async listKardex({url = null} = {}) {
            if(!this.filters.warehouse?.code) return;

            const requestVersion = ++this.requestVersion;
            const filters = this.currentReportFilters();

            this.loadingKardex = true;

            const result = await Requests.get({
                route: url || Requests.config({entity: "stocks_management", type: "movements"}),
                data: filters
            });

            if(requestVersion !== this.requestVersion) return;

            this.kardexRecords = result?.data || {total: 0, data: []};
            this.loadingKardex = false;
        },
        async listGuides({url = null} = {}) {
            if(!this.filters.warehouse?.code) return;

            const requestVersion = ++this.requestVersion;
            const filters = {
                warehouse_id: this.isConsolidatedStock ? "" : this.filters.warehouse.code,
                guide_type: this.filters.guideType?.code || "",
                date_from: this.filters.dateFrom,
                date_to: this.filters.dateTo
            };

            this.loadingGuides = true;

            const result = await Requests.get({
                route: url || Requests.config({entity: "stocks_management", type: "guides"}),
                data: filters
            });

            if(requestVersion !== this.requestVersion) return;

            this.guideRecords = result?.data || {total: 0, data: []};
            this.loadingGuides = false;
        },
        searchCurrentView() {
            if(!this.filters.warehouse?.code || this.isCurrentViewLoading) return Promise.resolve();

            return this.listCurrentView({});
        },
        async handleProductSearch() {
            if(this.isCurrentViewLoading) return;

            const exactProduct = this.findExactProduct(this.filters.productSearch);
            this.scannedProductId = exactProduct ? Number(exactProduct.code) : null;

            await this.searchCurrentView();
        },
        clearProductSearch() {
            this.scannedProductId = null;
        },
        findExactProduct(value) {
            const search = String(value || "").trim().toLocaleLowerCase();
            if(!search) return null;
            return this.products.find(product =>
                [product.barcode, product.internalCode, product.name]
                    .filter(Boolean)
                    .some(candidate => String(candidate).trim().toLocaleLowerCase() === search)
            ) || null;
        },
        focusProductSearch() {
            this.$nextTick(() => {
                this.$refs.productSearch?.focus();
            });
        },
        invalidateListing() {
            this.requestVersion += 1;
            this.loadingStock = false;
            this.loadingKardex = false;
            this.loadingAlerts = false;
            this.loadingGuides = false;
            this.scannedProductId = null;
            this.stockRecords = {total: 0, data: []};
            this.stockAlerts = {total: 0, data: []};
            this.kardexRecords = {total: 0, data: []};
            this.guideRecords = {total: 0, data: []};
        },
        listCurrentView({url = null} = {}) {
            if(this.activeView === "stock") return this.listStock({url});
            if(["kardex", "transfers", "valued"].includes(this.activeView)) return this.listKardex({url});
            if(this.activeView === "guides") return this.listGuides({url});
            return Promise.resolve();
        },
        currentReportFilters() {
            const filters = {
                warehouse_id: this.filters.warehouse?.code,
                movement_type: this.filters.movementType?.code || "",
                product_search: this.filters.productSearch,
                date_from: this.filters.dateFrom,
                date_to: this.filters.dateTo
            };

            if(this.activeView === "transfers") {
                filters.origin_types = ["transfer_out", "transfer_in"];
                filters.movement_type = "";
            }

            if(this.activeView === "guides") {
                filters.guide_type = this.filters.guideType?.code || "";
            }

            return filters;
        },
        async exportCurrentView() {
            if(!this.filters.warehouse?.code || this.exporting || this.isCurrentViewLoading) return;

            const confirmed = await Alerts.confirmDownload({resource: `el reporte de ${this.currentView.title.toLocaleLowerCase("es")}`});

            if(!confirmed || this.isCurrentViewLoading) return;

            this.exporting = true;
            Alerts.swals({type: "loading", message: "Preparando reporte"});
            const result = await Requests.download({
                route: this.config.entity.routes.export,
                data: {...this.currentReportFilters(), view: this.activeView},
                fileName: `inventario_${this.activeView}.xlsx`
            });
            this.exporting = false;
            Alerts.swals({show: false});

            if(!result?.bool) {
                Alerts.toastrs({type: "error", subtitle: result?.data?.msg || "No se pudo descargar el reporte."});
            }
        },
        newOperationItem(product = null) {
            return {
                key: `${Date.now()}-${Math.random().toString(16).slice(2)}`,
                product,
                amount: "",
                unitCost: "",
                currentStock: null
            };
        },
        prepareMovement(record = null) {
            this.resetOperationImport();
            const product = record
                ? this.products.find(item => Number(item.code) === Number(record.id))
                : this.findExactProduct(this.filters.productSearch);
            this.movementForm = {
                operation: null,
                warehouse: null,
                items: [this.newOperationItem(product)],
                reason: ""
            };
            this.movementErrors = {};
            this.refreshInventoryModalTooltips("inventoryMovementModal");
        },
        addMovementItem() {

            if(!this.canConfigureMovement) return;

            if(this.movementForm.items.length < 100) {
                this.disposeInventoryModalTooltips("inventoryMovementModal");
                this.movementForm.items.push(this.newOperationItem());
                this.refreshInventoryModalTooltips("inventoryMovementModal");
            }
        },
        removeMovementItem(index) {

            if(!this.canConfigureMovement) return;

            if(this.movementForm.items.length > 1) {
                this.disposeInventoryModalTooltips("inventoryMovementModal");
                this.movementForm.items.splice(index, 1);
                this.refreshInventoryModalTooltips("inventoryMovementModal");
            }
        },
        disposeInventoryModalTooltips(modalId) {
            Alerts.tooltips({show: false, time: 0, selector: `#${modalId} [data-bs-toggle='tooltip']`});
        },
        refreshInventoryModalTooltips(modalId) {
            this.$nextTick(() => Alerts.tooltips({show: true, time: 0, selector: `#${modalId} [data-bs-toggle='tooltip']`}));
        },
        movementProductOptions(index) {
            const selected = this.movementForm.items
                .filter((item, itemIndex) => itemIndex !== index)
                .map(item => Number(item.product?.code))
                .filter(Boolean);
            return this.products.filter(product => !selected.includes(Number(product.code)));
        },
        movementItemError(index, field) {
            return this.firstError(this.movementErrors[`items.${index}.${field}`]);
        },
        resetOperationImport({clearItems = false} = {}) {
            this.operationPreviewVersion++;
            this.previewingOperation = false;
            this.operationFileName = "";

            if(clearItems) {
                this.movementForm.items = [this.newOperationItem()];
            }
        },
        async downloadOperationTemplate() {
            if(!this.movementForm.operation?.code || !this.movementForm.warehouse?.code || this.downloadingOperation || this.previewingOperation) return;

            this.downloadingOperation = true;
            Alerts.loading({message: "Generando y descargando plantilla"});

            let result;

            try {

                result = await Requests.download({
                    route: Requests.config({entity: "stocks_management", type: "operationTemplate"}),
                    data: {
                        warehouse_id: this.movementForm.warehouse.code,
                        operation_code: this.movementForm.operation.code
                    },
                    fileName: `plantilla_${this.movementForm.operation.code}.xlsx`
                });

            }catch(error) {

                result = {bool: false, data: {msg: "No se pudo generar la plantilla."}};

            }finally {

                Alerts.close();
                this.downloadingOperation = false;

            }

            if(!result?.bool) {
                Alerts.toastrs({type: "error", subtitle: result?.data?.msg || "No se pudo descargar la plantilla."});
            }
        },
        async previewOperation(file) {
            if(!file || !this.movementForm.operation?.code || !this.movementForm.warehouse?.code) return;

            const warehouseId = this.movementForm.warehouse.code;
            const operationCode = this.movementForm.operation.code;
            const requestVersion = ++this.operationPreviewVersion;
            const formData = new FormData();

            formData.append("warehouse_id", warehouseId);
            formData.append("operation_code", operationCode);
            formData.append("file", file);
            if(this.operationFileName) {
                this.operationFileName = "";
                this.movementForm.items = [this.newOperationItem()];
            }
            this.previewingOperation = true;
            Alerts.loading({message: "Procesando plantilla"});

            let result;

            try {

                result = await Requests.post({
                    route: Requests.config({entity: "stocks_management", type: "operationPreview"}),
                    formData
                });

            }catch(error) {

                result = {bool: false, data: {msg: "No se pudo procesar la plantilla. Inténtalo nuevamente."}};

            }finally {

                Alerts.close();

                if(requestVersion === this.operationPreviewVersion) {

                    this.previewingOperation = false;

                }

            }

            if(requestVersion !== this.operationPreviewVersion) return;

            if(!Requests.valid({result})) {
                const details = Object.values(result?.errors || result?.data?.errors || {})
                    .flat()
                    .filter(Boolean)
                    .join(" ");

                Alerts.showMessage({
                    type: result?.code === 422 ? "warning" : "error",
                    title: "No se pudo procesar el archivo",
                    text: details || result?.data?.msg || "Comprueba el formato de la plantilla e inténtalo nuevamente."
                });
                return;
            }

            const importedItems = result.data.data.items || [];

            if(!importedItems.length) {
                Alerts.showMessage({
                    type: "warning",
                    title: "Archivo sin cantidades",
                    text: `Completa ${this.operationAmountLabel} de al menos un producto y vuelve a subir la plantilla.`
                });
                return;
            }

            for(const imported of importedItems) {
                if(!this.products.some(product => Number(product.code) === Number(imported.item_id))) {
                    this.options.products.push({
                        id: imported.item_id,
                        internal_code: imported.internal_code,
                        barcode: null,
                        name: imported.name,
                        measurement_unit: {
                            singular_name: imported.unit_singular,
                            plural_name: imported.unit_plural
                        }
                    });
                }
            }

            this.disposeInventoryModalTooltips("inventoryMovementModal");
            this.movementForm.items = importedItems.map(imported => ({
                ...this.newOperationItem(this.products.find(product => Number(product.code) === Number(imported.item_id))),
                amount: imported.amount,
                unitCost: imported.unit_cost ?? "",
                currentStock: imported.current_stock
            }));
            this.operationFileName = file.name;
            this.movementErrors = {};
            this.refreshInventoryModalTooltips("inventoryMovementModal");
            Alerts.showMessage({
                type: "success",
                title: "Archivo procesado",
                text: `Se ${importedItems.length === 1 ? "cargó" : "cargaron"} ${importedItems.length} ${importedItems.length === 1 ? "producto" : "productos"} para ${this.movementForm.operation.label.toLocaleLowerCase("es")}. Las filas sin cantidad se omitieron. Revisa la tabla antes de guardar; el inventario aún no cambió.`
            });
        },
        async saveMovement() {
            if(this.savingMovement || this.confirmingMovement || this.previewingOperation || !this.movementForm.warehouse?.code) return;

            this.movementErrors = {};
            const operation = this.movementForm.operation;
            const amountField = this.movementAmountField;

            if(!operation?.code) {
                this.movementErrors.operation = ["Selecciona un tipo de operación."];
            }

            if(!this.movementForm.reason?.trim()) {
                this.movementErrors.reason = ["Campo obligatorio."];
            }

            this.movementForm.items.forEach((item, index) => {
                if(!item.product?.code) this.movementErrors[`items.${index}.item_id`] = ["Campo obligatorio."];
                if(item.amount === "" || item.amount === null || item.amount === undefined) {
                    this.movementErrors[`items.${index}.${amountField}`] = ["Campo obligatorio."];
                }
            });

            if(Object.keys(this.movementErrors).length) {
                Alerts.showMessage({
                    type: "warning",
                    title: "Revisa la operación",
                    text: "Selecciona un tipo de operación y completa el motivo, los productos y sus cantidades antes de continuar."
                });
                return;
            }

            this.confirmingMovement = true;
            const productCount = this.movementForm.items.length;
            const confirmed = await Alerts.confirmAction({
                title: "¿Registrar operación?",
                text: `Registrarás ${operation.label.toLocaleLowerCase("es")} en ${this.movementForm.warehouse.displayLabel || this.movementForm.warehouse.label}. Se actualizará el stock de ${productCount} ${productCount === 1 ? "producto" : "productos"}. ¿Deseas continuar?`,
                confirmButtonText: "Sí, registrar",
                icon: "warning"
            });
            this.confirmingMovement = false;

            if(!confirmed) return;

            this.savingMovement = true;
            Alerts.swals({type: "loading", message: "Registrando operación"});
            const result = await Requests.post({
                route: Requests.config({entity: "stocks_management", type: "operations"}),
                data: {
                    warehouse_id: this.movementForm.warehouse.code,
                    movement_type: operation?.movementType,
                    origin_type: operation?.originType,
                    items: this.movementForm.items.map(item => ({
                        item_id: item.product?.code,
                        quantity: operation?.movementType === "correction" ? null : item.amount,
                        resulting_balance: operation?.movementType === "correction" ? item.amount : null,
                        unit_cost: operation?.movementType === "entry" ? item.unitCost : null
                    })),
                    reason: this.movementForm.reason
                }
            });
            this.savingMovement = false;
            Alerts.swals({show: false});

            if(Requests.valid({result})) {
                this.$refs.closeMovementModal?.click();
                Alerts.generateAlert({type: "success", msgContent: result.data.msg});
                await this.listStock({});
                return;
            }

            this.movementErrors = result?.errors || result?.data?.errors || {};
            Alerts.generateAlert({
                type: "error",
                messages: Object.values(this.movementErrors).flat().length
                    ? Object.values(this.movementErrors).flat()
                    : [result?.data?.msg || "No se pudo registrar la operación."]
            });
        },
        newTransferItem() {
            return {
                key: `${Date.now()}-${Math.random().toString(16).slice(2)}`,
                product: null,
                quantity: ""
            };
        },
        prepareTransfer() {

            this.transferErrors = {};
            this.transferForm = {
                source: null,
                destination: null,
                items: [this.newTransferItem()],
                reason: ""
            };
            this.refreshInventoryModalTooltips("inventoryTransferModal");
        },
        addTransferItem() {

            if(!this.canConfigureTransfer) return;

            if(this.transferForm.items.length < 100) {
                this.disposeInventoryModalTooltips("inventoryTransferModal");
                this.transferForm.items.push(this.newTransferItem());
                this.refreshInventoryModalTooltips("inventoryTransferModal");
            }
        },
        removeTransferItem(index) {

            if(!this.canConfigureTransfer) return;

            if(this.transferForm.items.length > 1) {
                this.disposeInventoryModalTooltips("inventoryTransferModal");
                this.transferForm.items.splice(index, 1);
                this.refreshInventoryModalTooltips("inventoryTransferModal");
            }
        },
        transferProductOptions(index) {
            const selected = this.transferForm.items
                .filter((item, itemIndex) => itemIndex !== index)
                .map(item => Number(item.product?.code))
                .filter(Boolean);
            return this.products.filter(product => !selected.includes(Number(product.code)));
        },
        transferItemError(index, field) {
            return this.firstError(this.transferErrors[`items.${index}.${field}`]);
        },
        async saveTransfer() {
            if(this.savingTransfer || !this.transferForm.source?.code || !this.transferForm.destination?.code) return;

            this.transferErrors = {};
            this.savingTransfer = true;
            Alerts.swals({type: "loading", message: "Registrando traslado"});
            const result = await Requests.post({
                route: Requests.config({entity: "stocks_management", type: "transfers"}),
                data: {
                    source_warehouse_id: this.transferForm.source.code,
                    destination_warehouse_id: this.transferForm.destination?.code,
                    items: this.transferForm.items.map(item => ({
                        item_id: item.product?.code,
                        quantity: item.quantity
                    })),
                    reason: this.transferForm.reason
                }
            });
            this.savingTransfer = false;
            Alerts.swals({show: false});

            if(Requests.valid({result})) {
                this.$refs.closeTransferModal?.click();
                Alerts.generateAlert({type: "success", msgContent: result.data.msg});
                this.prepareTransfer();
                await this.listKardex({});
                return;
            }

            this.transferErrors = result?.errors ?? result?.data?.errors ?? {};
            Alerts.generateAlert({
                type: "error",
                messages: Object.values(this.transferErrors).flat().length
                    ? Object.values(this.transferErrors).flat()
                    : [result?.data?.msg || "No se pudo registrar el traslado."]
            });
        },
        stockStatus(record) {
            const stock = Number(record.stock_quantity || 0);
            const minimum = Number(record.minimum_stock || 0);
            if(stock <= 0) return {label: "Sin existencias", icon: "fa-solid fa-circle-xmark", badgeClass: "br-badge--danger"};
            if(stock <= minimum) return {label: "Stock bajo", icon: "fa-solid fa-triangle-exclamation", badgeClass: "br-badge--warning"};
            return {label: "Stock saludable", icon: "fa-solid fa-circle-check", badgeClass: "br-badge--success"};
        },
        stockWarehouseBreakdown(record) {
            if(this.isConsolidatedStock) return record.warehouse_breakdown || [];

            const warehouse = this.filters.warehouse;
            if(!warehouse?.code) return [];

            return [{
                warehouse_id: warehouse.code,
                warehouse_name: warehouse.warehouseName,
                branch_name: warehouse.branchName,
                quantity: record.stock_quantity || 0,
                requires_attention: this.stockStatus(record).badgeClass !== "br-badge--success"
            }];
        },
        movementTypeLabel(type) {
            return MOVEMENT_TYPES.find(record => record.code === type)?.label || type;
        },
        originLabel(origin) {
            return {
                product_opening: "Creación de producto",
                commercial_catalog_seeder: "Stock inicial de demostración",
                manual: "Operación manual",
                sale: "Venta",
                sale_cancellation: "Devolución automática por anulación",
                purchase: "Compra",
                purchase_cancellation: "Anulación de compra",
                transfer_out: "Traslado enviado",
                transfer_in: "Traslado recibido",
                customer_return: "Devolución de cliente",
                supplier_return: "Devolución a proveedor",
                physical_count: "Toma física"
            }[origin] || origin;
        },
        formatDate(value) {
            if(!value) return "";
            return new Intl.DateTimeFormat("es-PE", {dateStyle: "short", timeStyle: "short"}).format(new Date(value));
        },
        formatSimpleDate(value) {
            if(!value) return "";
            const normalized = typeof value === "string" && value.length <= 10 ? `${value}T00:00:00` : value;
            return new Intl.DateTimeFormat("es-PE").format(new Date(normalized));
        },
        quantityWithUnit(value, item) {
            return Utils.quantityWithUnit(value, item?.measurement_unit || item);
        },
        signedQuantity(value, item) {
            const number = Number(value || 0);

            return `${number > 0 ? "+" : ""}${this.quantityWithUnit(number, item)}`;
        },
        currencyNumber(value, decimals = null) {
            return `S/ ${Utils.separatorNumber(value || 0, decimals)}`;
        },
        signedCurrency(value) {
            const number = Number(value || 0);
            return `${number > 0 ? "+" : number < 0 ? "-" : ""}${this.currencyNumber(Math.abs(number))}`;
        },
        firstError(error) {
            return Array.isArray(error) ? error[0] : error;
        },
        alertProductName(alert) {
            return alert?.warehouse_item?.item?.name || "Producto sin nombre";
        },
        separatorNumber(value) {
            return Utils.separatorNumber(value || 0);
        }
    },
    computed: {
        breadcrumbTitles() {
            return [{title: "Catálogo e inventario"}, this.config.entity.page];
        },
        currentView() {
            return VIEWS.find(view => view.code === this.activeView) || VIEWS[0];
        },
        isReportView() {
            return ["kardex", "valued", "guides"].includes(this.activeView);
        },
        contextActionColumns() {
            if(["stock", "transfers"].includes(this.activeView)) return "col-12 col-lg-6 col-xl-6";
            if(this.isReportView) return "col-12 col-md-6 col-lg-3 col-xl-3";

            return "col-12 col-md-6 col-lg-9 col-xl-9";
        },
        warehouses() {
            const warehouses = (this.options.warehouses || []).map(record => ({
                code: record.id,
                branchName: record.branch?.name || "",
                warehouseName: record.name,
                displayLabel: record.branch?.name ? `${record.branch.name} - ${record.name}` : record.name,
                label: record.branch?.name ? `${record.branch.name} / ${record.name}` : record.name
            }));

            return ["stock", "transfers"].includes(this.activeView)
                ? [{code: "all", label: "Todos los almacenes"}, ...warehouses]
                : warehouses;
        },
        destinationWarehouses() {
            if(!this.transferForm.source?.code) return [];

            return this.realWarehouses.filter(warehouse => Number(warehouse.code) !== Number(this.transferForm.source?.code));
        },
        realWarehouses() {
            return this.warehouses.filter(warehouse => warehouse.code !== "all");
        },
        products() {
            return (this.options.products || []).map(product => ({
                code: product.id,
                name: product.name,
                internalCode: product.internal_code,
                barcode: product.barcode,
                unitSingular: product.measurement_unit?.singular_name || "UNIDAD",
                unitPlural: product.measurement_unit?.plural_name || "UNIDADES",
                label: `${product.name} · ${product.internal_code}${product.barcode ? ` · ${product.barcode}` : ""}`
            }));
        },
        movementTypes() {
            return MOVEMENT_TYPES;
        },
        guideTypes() {
            return GUIDE_TYPES;
        },
        inventoryOperations() {
            return INVENTORY_OPERATION_OPTIONS;
        },
        inventoryOperationEffect() {
            return INVENTORY_OPERATION_GROUPS.find(group => group.code === this.movementForm.operation?.movementType)?.effect || "";
        },
        operationAmountLabel() {
            return this.movementForm.operation?.amountLabel || "Cantidad";
        },
        operationTemplateUrl() {
            const warehouseId = this.movementForm.warehouse?.code;
            const operationCode = this.movementForm.operation?.code;

            return warehouseId && operationCode
                ? `${Requests.config({entity: "stocks_management", type: "operationTemplate"})}?warehouse_id=${encodeURIComponent(warehouseId)}&operation_code=${encodeURIComponent(operationCode)}`
                : null;
        },
        movementAmountField() {
            return this.movementForm.operation?.movementType === "correction"
                ? "resulting_balance"
                : "quantity";
        },
        currentRecords() {
            if(this.activeView === "stock") return this.stockRecords;
            if(this.activeView === "guides") return this.guideRecords;

            return this.kardexRecords;
        },
        openStockAlerts() {
            return this.stockAlerts?.data || [];
        },
        isCurrentViewLoading() {
            if(this.activeView === "stock") return this.loadingStock || this.loadingAlerts;
            if(this.activeView === "guides") return this.loadingGuides;

            return this.loadingKardex;
        },
        canConfigureMovement() {

            return Boolean(this.movementForm.warehouse?.code);

        },
        canConfigureTransfer() {

            return Boolean(this.transferForm.source?.code && this.transferForm.destination?.code);

        },
        isConsolidatedStock() {
            return this.filters.warehouse?.code === "all";
        }
    },
    watch: {
        "movementForm.operation"(operation, previous) {
            if(operation?.code) delete this.movementErrors.operation;

            if(operation?.code !== previous?.code) {
                this.resetOperationImport({clearItems: true});
            }

            this.refreshInventoryModalTooltips("inventoryMovementModal");
        },
        "movementForm.warehouse"(warehouse, previous) {
            if(warehouse?.code !== previous?.code) {
                this.resetOperationImport({clearItems: true});
            }
        },
        "filters.warehouse"(warehouse, previous) {
            if(warehouse?.code === previous?.code) return;

            this.invalidateListing();

            if(this.warehouseSearchEnabled && warehouse?.code) {
                this.listCurrentView({});
            }
        },
        "transferForm.source"() {
            if(!this.destinationWarehouses.some(warehouse => warehouse.code === this.transferForm.destination?.code)) {
                this.transferForm.destination = null;
            }
        },
        "filters.productSearch"() {
            this.invalidateListing();
        },
        "filters.movementType"() {
            this.invalidateListing();
        },
        "filters.guideType"() {
            this.invalidateListing();
        },
        "filters.dateFrom"() {
            this.invalidateListing();
        },
        "filters.dateTo"() {
            this.invalidateListing();
        }
    }
};
</script>
