<template>
    <Breadcrumb :list="breadcrumbTitles"/>

    <section class="br-inventory">
        <div class="br-inventory__context">
            <div class="br-inventory__warehouse">
                <label for="inventoryWarehouse" class="form-label">Almacén de trabajo</label>
                <v-select
                    id="inventoryWarehouse"
                    v-model="filters.warehouse"
                    :options="warehouses"
                    :class="config.forms.classes.select2"
                    :clearable="false"
                    :searchable="true"/>
            </div>
            <p class="br-operational-scope mb-0">
                <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                <span>Alcance activo: {{ filters.warehouse?.label || 'Seleccione un almacén' }}</span>
            </p>
        </div>

        <nav class="nav nav-pills nav-fill br-entity-tabs br-inventory__views" role="tablist" aria-label="Secciones de inventario">
            <button
                v-for="view in views"
                :key="view.code"
                type="button"
                :class="['nav-link', 'br-entity-tab', 'br-inventory__view', {'active is-active': activeView === view.code}]"
                role="tab"
                :aria-selected="activeView === view.code"
                @click="changeView(view.code)">
                    <span class="br-entity-tab__step">
                        <i :class="view.icon" aria-hidden="true"></i>
                    </span>
                    <span class="br-entity-tab__content br-inventory__view-copy">
                        <strong>{{ view.title }}</strong>
                        <small>{{ view.description }}</small>
                    </span>
            </button>
        </nav>

        <div class="br-inventory__workspace">
            <section class="br-inventory__panel">
                <section class="br-filter-bar br-inventory__toolbar">
                    <div class="row align-items-end g-2">
                    <InputText
                            v-if="activeView === 'stock'"
                            ref="productSearch"
                            v-model.trim="filters.productSearch"
                            hasDiv
                            title="Búsqueda"
                            :titleClass="[config.forms.classes.title]"
                            placeholder="Buscar o escanear producto"
                            :inputClass="['form-control']"
                            xl="7"
                            lg="7"
                            @enterKeyPressed="handleProductSearch">
                        <template #inputGroupPrepend>
                            <span
                                class="input-group-text br-inventory-search__prefix"
                                data-bs-toggle="tooltip"
                                data-bs-placement="top"
                                title="Admite nombre, código interno o código de barras">
                                <i class="fa-solid fa-barcode" aria-hidden="true"></i>
                            </span>
                        </template>
                        <template #inputGroupAppend>
                            <button
                                v-if="filters.productSearch"
                                type="button"
                                class="br-input-action"
                                title="Limpiar"
                                aria-label="Limpiar búsqueda"
                                @click="clearProductSearch">
                                <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                            </button>
                        </template>
                    </InputText>

                    <InputSlot
                        hasDiv
                        :isInputGroup="false"
                        :divInputClass="['br-filter-bar__actions', 'br-inventory__toolbar-actions']"
                        :xl="activeView === 'stock' ? '5' : '12'"
                        :lg="activeView === 'stock' ? '5' : '12'">
                        <template #input>
                        <button
                            v-if="activeView === 'stock'"
                            type="button"
                            class="br-btn br-btn-sm br-btn-search"
                            :disabled="isCurrentViewLoading"
                            @click="handleProductSearch">
                            <span>Buscar</span>
                        </button>
                        <button
                            v-if="activeView === 'stock'"
                            type="button"
                            class="br-btn br-btn-sm br-btn-primary br-inventory__register-action"
                            :disabled="isConsolidatedStock"
                            data-bs-toggle="modal"
                            data-bs-target="#inventoryMovementModal"
                            title="Registrar una operación para uno o varios productos"
                            aria-label="Registrar operación de inventario"
                            @click="prepareMovement()">
                            <i class="fa-solid fa-arrow-right-arrow-left" aria-hidden="true"></i>
                            <span>Registrar operación</span>
                        </button>
                        <button
                            type="button"
                            class="br-btn br-btn-sm br-btn-action-export br-btn-action-export--desktop-icon br-inventory__export-action"
                            :disabled="exporting"
                            data-bs-toggle="tooltip"
                            data-bs-placement="top"
                            title="Descargar reporte Excel"
                            aria-label="Descargar reporte Excel"
                            @click="exportCurrentView">
                            <i class="fa-solid fa-file-excel" aria-hidden="true"></i>
                            <span class="br-btn-action-export__label">Descargar</span>
                        </button>
                        </template>
                    </InputSlot>
                    </div>
                </section>

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

                <div v-if="activeView === 'stock'" class="table-responsive br-inventory__table-wrap">
                    <table class="table br-entity-table br-inventory__table mb-0">
                        <colgroup>
                            <col class="br-inventory__col-product">
                            <col class="br-inventory__col-current">
                            <col class="br-inventory__col-minimum">
                            <col class="br-inventory__col-status">
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th class="text-end">Stock actual</th>
                                <th class="text-end">Stock mínimo</th>
                                <th v-if="isConsolidatedStock">Almacenes</th>
                                <th>Situación</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="loadingStock">
                                <td :colspan="isConsolidatedStock ? 5 : 4" class="text-center py-4"><Loader/></td>
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
                                    <td class="text-end"><strong>{{ separatorNumber(record.stock_quantity) }}</strong></td>
                                    <td class="text-end">{{ separatorNumber(record.minimum_stock) }}</td>
                                    <td v-if="isConsolidatedStock">
                                        <div class="br-inventory-warehouses">
                                            <span
                                                v-for="warehouse in (record.warehouse_breakdown || []).slice(0, 3)"
                                                :key="`${record.id}-${warehouse.warehouse_id}`"
                                                :class="['br-inventory-warehouse-pill', {'is-alert': warehouse.requires_attention}]">
                                                {{ warehouse.branch_name }} / {{ warehouse.warehouse_name }}:
                                                <strong>{{ separatorNumber(warehouse.quantity) }}</strong>
                                            </span>
                                            <span v-if="(record.warehouse_breakdown || []).length > 3" class="br-inventory-warehouse-pill is-muted">
                                                +{{ record.warehouse_breakdown.length - 3 }} más
                                            </span>
                                        </div>
                                    </td>
                                    <td>
                                        <span :class="['br-inventory-status', stockStatus(record).className]">
                                            <i :class="stockStatus(record).icon" aria-hidden="true"></i>
                                            {{ stockStatus(record).label }}
                                        </span>
                                    </td>
                                </tr>
                            </template>
                            <tr v-else>
                                <td :colspan="isConsolidatedStock ? 5 : 4"><WithoutData type="image"/></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-else-if="['kardex', 'valued'].includes(activeView)" class="br-inventory__kardex">
                    <p v-if="activeView === 'valued'" class="br-inventory-help">
                        <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                        El costo unitario se registra en cada entrada. Si se omite, el sistema conserva el costo promedio ponderado del almacén.
                    </p>
                    <div class="br-inventory__kardex-filters br-inventory__secondary-filters">
                        <div>
                            <label for="movementType" class="form-label">Movimiento</label>
                            <v-select
                                id="movementType"
                                v-model="filters.movementType"
                                :options="movementTypes"
                                :class="config.forms.classes.select2"
                                :clearable="false"
                                :searchable="false"/>
                        </div>
                        <div>
                            <label for="kardexDateFrom" class="form-label">Desde</label>
                            <input id="kardexDateFrom" v-model="filters.dateFrom" type="date" class="form-control">
                        </div>
                        <div>
                            <label for="kardexDateTo" class="form-label">Hasta</label>
                            <input id="kardexDateTo" v-model="filters.dateTo" type="date" class="form-control">
                        </div>
                        <button
                            type="button"
                            class="br-btn br-btn-search"
                            :disabled="loadingKardex"
                            @click="listKardex({})">
                            <span>Filtrar</span>
                        </button>
                    </div>

                    <div class="table-responsive br-inventory__table-wrap">
                        <table class="table br-entity-table br-inventory__kardex-table mb-0">
                            <thead>
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
                                    <td colspan="6" class="text-center py-4"><Loader/></td>
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
                                            {{ signedNumber(movement.quantity_change) }}
                                        </td>
                                        <td class="text-end">
                                            <strong>{{ separatorNumber(movement.quantity_after) }}</strong>
                                            <span class="br-inventory__meta">
                                                Antes: {{ separatorNumber(movement.quantity_before) }}
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
                                        <td class="text-end fw-semibold">{{ signedNumber(movement.quantity_change) }}</td>
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
                                    <td colspan="6"><WithoutData type="image"/></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div v-else-if="activeView === 'transfers'" class="br-inventory-transfer">
                    <div class="br-inventory-transfer__intro">
                        <div>
                            <span>Almacén de origen</span>
                            <strong>{{ filters.warehouse?.label }}</strong>
                        </div>
                        <p>
                            Escanea o agrega varios productos y envíalos juntos al almacén de destino.
                        </p>
                    </div>

                    <div class="br-inventory-transfer__form">
                        <div class="br-inventory-transfer__destination">
                            <label for="transferSource" class="form-label">Almacén de origen</label>
                            <v-select
                                id="transferSource"
                                v-model="filters.warehouse"
                                :options="warehouses"
                                :clearable="false"
                                :searchable="true"
                                append-to-body/>
                            <small class="br-inventory__field-help">
                                Se toma del almacén de trabajo y será la salida del traslado.
                            </small>
                        </div>

                        <div class="br-inventory-transfer__destination">
                            <label for="transferDestination" class="form-label">Almacén de destino</label>
                            <v-select
                                id="transferDestination"
                                v-model="transferForm.destination"
                                :options="destinationWarehouses"
                                :clearable="false"
                                :searchable="true"
                                append-to-body/>
                            <small v-if="transferErrors.destination_warehouse_id" class="text-danger">
                                {{ firstError(transferErrors.destination_warehouse_id) }}
                            </small>
                        </div>

                        <div class="br-inventory-transfer__items">
                            <div class="br-inventory-transfer__items-header">
                                <div>
                                    <strong>Productos del traslado</strong>
                                    <small>Registra hasta 100 productos en una sola operación.</small>
                                </div>
                                <button
                                    type="button"
                                    class="br-btn br-btn-sm br-btn-outline-secondary"
                                    :disabled="savingTransfer || transferForm.items.length >= 100"
                                    @click="addTransferItem">
                                    <i class="fa-solid fa-plus" aria-hidden="true"></i>
                                    <span>Agregar producto</span>
                                </button>
                            </div>

                            <div
                                v-for="(item, index) in transferForm.items"
                                :key="item.key"
                                class="br-inventory-transfer__item-row">
                                <div>
                                    <label :for="`transferProduct-${item.key}`" class="form-label">
                                        Producto {{ index + 1 }}
                                    </label>
                                    <v-select
                                        :id="`transferProduct-${item.key}`"
                                        v-model="item.product"
                                        :options="transferProductOptions(index)"
                                        :clearable="false"
                                        :searchable="true"
                                        append-to-body/>
                                    <small v-if="transferItemError(index, 'item_id')" class="text-danger">
                                        {{ transferItemError(index, "item_id") }}
                                    </small>
                                </div>
                                <div>
                                    <label :for="`transferQuantity-${item.key}`" class="form-label">Cantidad</label>
                                    <InputNumber
                                        :id="`transferQuantity-${item.key}`"
                                        v-model="item.quantity"
                                        :hasNegative="false"/>
                                    <small v-if="transferItemError(index, 'quantity')" class="text-danger">
                                        {{ transferItemError(index, "quantity") }}
                                    </small>
                                </div>
                                <button
                                    type="button"
                                    class="br-icon-action br-inventory-transfer__remove"
                                    :disabled="savingTransfer || transferForm.items.length === 1"
                                    :aria-label="`Quitar producto ${index + 1}`"
                                    title="Quitar producto"
                                    @click="removeTransferItem(index)">
                                    <i class="fa-solid fa-trash-can" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>

                        <div class="br-inventory-transfer__reason">
                            <label for="transferReason" class="form-label">Motivo del traslado</label>
                            <textarea
                                id="transferReason"
                                v-model.trim="transferForm.reason"
                                class="form-control"
                                rows="2"
                                maxlength="255"
                                placeholder="Ejemplo: reposición para el almacén de la sede norte"></textarea>
                            <small v-if="transferErrors.reason" class="text-danger">
                                {{ firstError(transferErrors.reason) }}
                            </small>
                        </div>

                        <div class="br-inventory-transfer__submit">
                            <button
                                type="button"
                                class="br-btn br-btn-primary"
                                :disabled="savingTransfer"
                                @click="saveTransfer">
                                <i class="fa-solid fa-arrow-right-arrow-left" aria-hidden="true"></i>
                                <span>Registrar traslado</span>
                            </button>
                        </div>
                    </div>

                    <div class="table-responsive br-inventory__table-wrap mt-3">
                        <table class="table br-entity-table br-inventory__kardex-table mb-0">
                            <thead>
                                <tr>
                                    <th>Fecha y responsable</th>
                                    <th>Producto</th>
                                    <th>Movimiento</th>
                                    <th class="text-end">Cantidad</th>
                                    <th>Referencia</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="loadingKardex">
                                    <td colspan="5" class="text-center py-4"><Loader/></td>
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
                                            <span :class="['br-inventory-movement', `is-${movement.movement_type}`]">
                                                {{ originLabel(movement.origin_type) }}
                                            </span>
                                        </td>
                                        <td class="text-end fw-semibold">{{ signedNumber(movement.quantity_change) }}</td>
                                        <td>
                                            <strong>{{ movement.metadata?.reference || movement.reference || "-" }}</strong>
                                            <span class="br-inventory__meta">{{ movement.reason }}</span>
                                        </td>
                                    </tr>
                                </template>
                                <tr v-else>
                                    <td colspan="5"><WithoutData type="image"/></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div v-else-if="activeView === 'guides'" class="br-inventory__kardex">
                    <div class="br-inventory__kardex-filters br-inventory__secondary-filters">
                        <div>
                            <label for="guideType" class="form-label">Tipo de guía</label>
                            <v-select
                                id="guideType"
                                v-model="filters.guideType"
                                :options="guideTypes"
                                :class="config.forms.classes.select2"
                                :clearable="false"
                                :searchable="false"/>
                        </div>
                        <div>
                            <label for="guideDateFrom" class="form-label">Desde</label>
                            <input id="guideDateFrom" v-model="filters.dateFrom" type="date" class="form-control">
                        </div>
                        <div>
                            <label for="guideDateTo" class="form-label">Hasta</label>
                            <input id="guideDateTo" v-model="filters.dateTo" type="date" class="form-control">
                        </div>
                        <button
                            type="button"
                            class="br-btn br-btn-search"
                            :disabled="loadingGuides"
                            @click="listGuides({})">
                            <span>Filtrar</span>
                        </button>
                    </div>

                    <div class="table-responsive br-inventory__table-wrap">
                        <table class="table br-entity-table br-inventory__kardex-table mb-0">
                            <thead>
                                <tr>
                                    <th>Número y fecha</th>
                                    <th>Almacén</th>
                                    <th>Tipo</th>
                                    <th>Detalle</th>
                                    <th>Referencia</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="loadingGuides">
                                    <td colspan="6" class="text-center py-4"><Loader/></td>
                                </tr>
                                <template v-else-if="guideRecords.total > 0">
                                    <tr v-for="guide in guideRecords.data" :key="`guide-${guide.id}`">
                                        <td>
                                            <strong>{{ guide.number }}</strong>
                                            <span class="br-inventory__meta">{{ formatSimpleDate(guide.issue_date) }}</span>
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
                                                {{ (guide.items || []).slice(0, 2).map(item => item.item?.name).join(", ") }}
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
                                    <td colspan="6"><WithoutData type="image"/></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </section>

            <div
                v-if="['stock', 'kardex', 'transfers', 'valued', 'guides'].includes(activeView) && currentRecords.links"
                class="d-flex justify-content-center mt-3">
                <Paginator :links="currentRecords.links" @clickPage="listCurrentView"/>
            </div>
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
                    <div>
                        <p class="br-entity-modal__eyebrow mb-1">Inventario</p>
                        <h2 id="inventoryMovementModalTitle" class="modal-title br-entity-modal__title">
                            Registrar operación
                        </h2>
                        <p class="br-inventory-operation__subtitle">
                            Aplica la misma operación a uno o varios productos del almacén seleccionado.
                        </p>
                    </div>
                    <button type="button" class="br-modal-close" data-bs-dismiss="modal" aria-label="Cerrar">
                        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                    </button>
                </div>

                <div class="modal-body br-entity-modal__body br-modal-standard__body">
                    <div class="br-inventory-operation__config">
                        <div>
                            <label class="form-label" for="inventoryOperation">Tipo de operación</label>
                            <v-select
                                id="inventoryOperation"
                                v-model="movementForm.operation"
                                :options="inventoryOperations"
                                :class="config.forms.classes.select2"
                                :clearable="false"
                                :searchable="false"/>
                            <small class="br-inventory__field-help">{{ movementForm.operation?.description }}</small>
                        </div>
                        <div>
                            <label class="form-label" for="movementReason">Motivo</label>
                            <textarea
                                id="movementReason"
                                v-model.trim="movementForm.reason"
                                class="form-control"
                                rows="2"
                                maxlength="255"
                                placeholder="Describe por qué se realiza esta operación"></textarea>
                            <small v-if="movementErrors.reason" class="text-danger">
                                {{ firstError(movementErrors.reason) }}
                            </small>
                        </div>
                    </div>

                    <div class="br-inventory-operation__items">
                        <div class="br-inventory-operation__items-header">
                            <div>
                                <strong>Productos incluidos</strong>
                                <small>También puedes escanear un código en el buscador principal antes de abrir esta ventana.</small>
                            </div>
                            <button
                                type="button"
                                class="br-btn br-btn-sm br-btn-outline-secondary"
                                :disabled="savingMovement || movementForm.items.length >= 100"
                                @click="addMovementItem">
                                <i class="fa-solid fa-plus" aria-hidden="true"></i>
                                <span>Agregar producto</span>
                            </button>
                        </div>

                        <div
                            v-for="(item, index) in movementForm.items"
                            :key="item.key"
                            :class="[
                                'br-inventory-operation__item',
                                {'has-unit-cost': movementForm.operation?.movementType === 'entry'}
                            ]">
                            <div>
                                <label :for="`operationProduct-${item.key}`" class="form-label">
                                    Producto {{ index + 1 }}
                                </label>
                                <v-select
                                    :id="`operationProduct-${item.key}`"
                                    v-model="item.product"
                                    :options="movementProductOptions(index)"
                                    :clearable="false"
                                    :searchable="true"
                                    append-to-body/>
                                <small v-if="movementItemError(index, 'item_id')" class="text-danger">
                                    {{ movementItemError(index, "item_id") }}
                                </small>
                            </div>
                            <div>
                                <label :for="`operationAmount-${item.key}`" class="form-label">
                                    {{ movementForm.operation?.movementType === "correction" ? "Saldo contado" : "Cantidad" }}
                                </label>
                                <InputNumber
                                    :id="`operationAmount-${item.key}`"
                                    v-model="item.amount"
                                    :hasNegative="false"/>
                                <small
                                    v-if="movementItemError(index, movementAmountField)"
                                    class="text-danger">
                                    {{ movementItemError(index, movementAmountField) }}
                                </small>
                            </div>
                            <div v-if="movementForm.operation?.movementType === 'entry'">
                                <label :for="`operationCost-${item.key}`" class="form-label">
                                    Costo unitario
                                </label>
                                <InputNumber
                                    :id="`operationCost-${item.key}`"
                                    v-model="item.unitCost"
                                    :hasNegative="false">
                                    <template #inputGroupPrepend>
                                        <span class="input-group-text br-currency-prefix">S/</span>
                                    </template>
                                </InputNumber>
                                <small class="br-inventory__field-help">
                                    Opcional. Si se omite, conserva el costo promedio actual.
                                </small>
                                <small v-if="movementItemError(index, 'unit_cost')" class="text-danger">
                                    {{ movementItemError(index, "unit_cost") }}
                                </small>
                            </div>
                            <button
                                type="button"
                                class="br-icon-action br-inventory-transfer__remove"
                                :disabled="savingMovement || movementForm.items.length === 1"
                                :aria-label="`Quitar producto ${index + 1}`"
                                title="Quitar producto"
                                @click="removeMovementItem(index)">
                                <i class="fa-solid fa-trash-can" aria-hidden="true"></i>
                            </button>
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
                    <button
                        type="button"
                        class="br-btn br-btn-action-update"
                        :disabled="savingMovement"
                        @click="saveMovement">
                        Registrar operación
                    </button>
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

const VIEWS = [
    {
        code: "stock",
        title: "Control de stock",
        description: "Existencias y alertas",
        longDescription: "Revisa cuánto hay disponible y detecta productos que requieren reposición.",
        icon: "fa-solid fa-boxes-stacked"
    },
    {
        code: "kardex",
        title: "Kardex",
        description: "Historial y trazabilidad",
        longDescription: "Consulta entradas, salidas y correcciones con su responsable y saldo resultante.",
        icon: "fa-solid fa-clock-rotate-left"
    },
    {
        code: "transfers",
        title: "Traslados",
        description: "Movimientos entre almacenes",
        longDescription: "Mueve varios productos entre almacenes en una sola operación trazable.",
        icon: "fa-solid fa-truck-ramp-box"
    },
    {
        code: "valued",
        title: "Kardex valorizado",
        description: "Costos y valoración",
        longDescription: "Consulta cantidades y valores según el método contable configurado.",
        icon: "fa-solid fa-calculator"
    },
    {
        code: "guides",
        title: "Guías",
        description: "Entradas y salidas",
        longDescription: "Consulta guías numeradas con estado, almacén y productos asociados.",
        icon: "fa-solid fa-clipboard-list"
    }
];

const ROUTE_VIEW_MAP = {
    stock: "stock",
    kardex: "kardex",
    transfers: "transfers",
    valued: "valued",
    guides: "guides"
};

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
    {code: "manual_entry", label: "Entrada manual", movementType: "entry", originType: "manual", description: "Suma unidades por una operación interna justificada."},
    {code: "manual_exit", label: "Salida manual", movementType: "exit", originType: "manual", description: "Descuenta unidades por una operación interna justificada."},
    {code: "physical_count", label: "Toma física", movementType: "correction", originType: "physical_count", description: "Registra el saldo contado y conserva la diferencia como corrección."},
    {code: "replenishment", label: "Reposición", movementType: "entry", originType: "replenishment", description: "Suma unidades recibidas para reponer existencias."},
    {code: "customer_return", label: "Devolución de cliente", movementType: "entry", originType: "customer_return", description: "Reingresa productos recibidos físicamente de un cliente."},
    {code: "supplier_return", label: "Devolución a proveedor", movementType: "exit", originType: "supplier_return", description: "Descuenta productos enviados de vuelta al proveedor."}
];

export default {
    async mounted() {
        this.activeView = this.initialViewFromPath();
        Utils.navbarItem("menu-parent-inventory", {addClass: "open"});
        Utils.navbarItem(this.activeMenuId(), {});
        Alerts.swals({type: "initParams"});
        const initialized = await this.initParams();
        Alerts.swals({show: false});

        if(initialized && this.warehouses.length) {
            this.filters.warehouse = this.activeView === "stock"
                ? this.warehouses[0]
                : (this.realWarehouses[0] || this.warehouses[0]);
        }
    },
    data() {
        return {
            views: VIEWS,
            activeView: "stock",
            loadingStock: false,
            loadingKardex: false,
            loadingAlerts: false,
            loadingGuides: false,
            savingMovement: false,
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
                operation: INVENTORY_OPERATIONS[0],
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
                        title: "Inventario",
                        active: true,
                        menu: {id: "menu-inventory-stock"}
                    }
                }
            }
        };
    },
    methods: {
        initialViewFromPath() {
            const segment = window.location.pathname.split("?")[0].split("/").filter(Boolean).pop();

            return ROUTE_VIEW_MAP[segment] || "stock";
        },
        activeMenuId() {
            return VIEW_MENU_MAP[this.activeView] || VIEW_MENU_MAP.stock;
        },
        async initParams() {
            const result = await Requests.get({
                route: this.config.entity.routes.initParams,
                data: {page: "main"},
                showAlert: true
            });
            this.options.warehouses = result.data?.config?.warehouses?.records || [];
            this.options.products = result.data?.config?.products?.records || [];
            return Requests.valid({result});
        },
        async listStock({url = null} = {}) {
            if(!this.filters.warehouse?.code) return;
            this.loadingStock = true;
            const result = await Requests.get({
                route: url || Requests.config({
                    entity: "stocks_management",
                    type: this.isConsolidatedStock ? "summary" : "list"
                }),
                data: {
                    warehouse_id: this.filters.warehouse.code,
                    product_search: this.filters.productSearch
                }
            });
            this.stockRecords = this.isConsolidatedStock
                ? {total: result?.data?.data?.length || 0, data: result?.data?.data || [], links: null}
                : (result?.data || {total: 0, data: []});
            this.loadingStock = false;
            if(this.isConsolidatedStock) {
                this.stockAlerts = {total: 0, data: []};
            }else {
                this.listStockAlerts({});
            }
        },
        async listStockAlerts({url = null} = {}) {
            if(!this.filters.warehouse?.code) return;
            this.loadingAlerts = true;
            const result = await Requests.get({
                route: url || Requests.config({entity: "stocks_management", type: "alerts"}),
                data: {
                    warehouse_id: this.filters.warehouse.code,
                    status: "open"
                }
            });
            this.stockAlerts = result?.data || {total: 0, data: []};
            this.loadingAlerts = false;
        },
        async listKardex({url = null} = {}) {
            if(!this.filters.warehouse?.code) return;
            this.loadingKardex = true;
            const result = await Requests.get({
                route: url || Requests.config({entity: "stocks_management", type: "movements"}),
                data: this.currentReportFilters()
            });
            this.kardexRecords = result?.data || {total: 0, data: []};
            this.loadingKardex = false;
        },
        async listGuides({url = null} = {}) {
            if(!this.filters.warehouse?.code) return;
            this.loadingGuides = true;
            const result = await Requests.get({
                route: url || Requests.config({entity: "stocks_management", type: "guides"}),
                data: {
                    warehouse_id: this.isConsolidatedStock ? "" : this.filters.warehouse?.code,
                    guide_type: this.filters.guideType?.code || "",
                    date_from: this.filters.dateFrom,
                    date_to: this.filters.dateTo
                }
            });
            this.guideRecords = result?.data || {total: 0, data: []};
            this.loadingGuides = false;
        },
        changeView(view) {
            this.activeView = view;
            Utils.navbarItem(this.activeMenuId(), {});
            this.scannedProductId = null;

            if(["kardex", "valued", "transfers"].includes(view) && !this.kardexRecords.data.length) {
                this.listKardex({});
            }

            if(view === "guides" && !this.guideRecords.data.length) {
                this.listGuides({});
            }

            if(view === "transfers" && !this.transferForm.destination) {
                this.transferForm.destination = this.destinationWarehouses[0] ?? null;
            }
        },
        async handleProductSearch() {
            const exactProduct = this.findExactProduct(this.filters.productSearch);
            this.scannedProductId = exactProduct ? Number(exactProduct.code) : null;

            if(this.activeView === "transfers" && exactProduct) {
                this.addScannedTransferProduct(exactProduct);
                return;
            }

            await this.listCurrentView({});
        },
        clearProductSearch() {
            this.filters.productSearch = "";
            this.scannedProductId = null;
            this.listCurrentView({});
            this.focusProductSearch();
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
        addScannedTransferProduct(product) {
            const existing = this.transferForm.items.find(item => Number(item.product?.code) === Number(product.code));

            if(existing) {
                Alerts.toastrs({type: "info", subtitle: "El producto ya está incluido en el traslado."});
                return;
            }

            const empty = this.transferForm.items.find(item => !item.product);
            if(empty) empty.product = product;
            else this.transferForm.items.push({...this.newTransferItem(), product});

            this.filters.productSearch = "";
            Alerts.toastrs({type: "success", subtitle: "Producto agregado al traslado."});
            this.focusProductSearch();
        },
        focusProductSearch() {
            this.$nextTick(() => {
                document.querySelector(".br-inventory__toolbar input")?.focus();
            });
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
            if(!this.filters.warehouse?.code || this.exporting) return;
            this.exporting = true;
            Alerts.swals({type: "loading", message: "Preparando reporte"});
            const result = await Requests.download({
                route: this.config.entity.routes.export,
                data: {...this.currentReportFilters(), view: this.activeView},
                fileName: `inventario_${this.activeView}.xlsx`
            });
            this.exporting = false;
            Alerts.swals({show: false});

            if(!Requests.valid({result})) {
                Alerts.toastrs({type: "error", subtitle: result?.data?.msg || "No se pudo descargar el reporte."});
            }
        },
        newOperationItem(product = null) {
            return {
                key: `${Date.now()}-${Math.random().toString(16).slice(2)}`,
                product,
                amount: "",
                unitCost: ""
            };
        },
        prepareMovement(record = null) {
            const product = record
                ? this.products.find(item => Number(item.code) === Number(record.id))
                : this.findExactProduct(this.filters.productSearch);
            this.movementForm = {
                operation: INVENTORY_OPERATIONS[0],
                items: [this.newOperationItem(product)],
                reason: ""
            };
            this.movementErrors = {};
        },
        addMovementItem() {
            if(this.movementForm.items.length < 100) {
                this.movementForm.items.push(this.newOperationItem());
            }
        },
        removeMovementItem(index) {
            if(this.movementForm.items.length > 1) this.movementForm.items.splice(index, 1);
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
        async saveMovement() {
            this.savingMovement = true;
            this.movementErrors = {};
            Alerts.swals({type: "loading", message: "Registrando operación"});
            const operation = this.movementForm.operation;
            const result = await Requests.post({
                route: Requests.config({entity: "stocks_management", type: "operations"}),
                data: {
                    warehouse_id: this.filters.warehouse?.code,
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
                await Promise.all([this.listStock({}), this.listKardex({})]);
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
        addTransferItem() {
            if(this.transferForm.items.length < 100) this.transferForm.items.push(this.newTransferItem());
        },
        removeTransferItem(index) {
            if(this.transferForm.items.length > 1) this.transferForm.items.splice(index, 1);
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
            this.transferErrors = {};
            this.savingTransfer = true;
            Alerts.swals({type: "loading", message: "Registrando traslado"});
            const result = await Requests.post({
                route: Requests.config({entity: "stocks_management", type: "transfers"}),
                data: {
                    source_warehouse_id: this.filters.warehouse?.code,
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
                Alerts.generateAlert({type: "success", msgContent: result.data.msg});
                this.transferForm = {
                    destination: this.destinationWarehouses[0] ?? null,
                    items: [this.newTransferItem()],
                    reason: ""
                };
                await Promise.all([this.listStock({}), this.listKardex({})]);
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
            if(stock <= 0) return {label: "Sin existencias", icon: "fa-solid fa-circle-xmark", className: "is-empty"};
            if(stock <= minimum) return {label: "Stock bajo", icon: "fa-solid fa-triangle-exclamation", className: "is-low"};
            return {label: "Stock saludable", icon: "fa-solid fa-circle-check", className: "is-healthy"};
        },
        movementTypeLabel(type) {
            return MOVEMENT_TYPES.find(record => record.code === type)?.label || type;
        },
        originLabel(origin) {
            return {
                product_opening: "Creación de producto",
                manual: "Operación manual",
                sale: "Venta",
                sale_cancellation: "Devolución automática por anulación",
                purchase: "Compra",
                purchase_cancellation: "Anulación de compra",
                transfer_out: "Traslado enviado",
                transfer_in: "Traslado recibido",
                replenishment: "Reposición",
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
        signedNumber(value) {
            const number = Number(value || 0);
            return `${number > 0 ? "+" : ""}${this.separatorNumber(number)}`;
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
            return [{title: "Catálogo comercial"}, this.config.entity.page];
        },
        currentView() {
            return this.views.find(view => view.code === this.activeView) || this.views[0];
        },
        warehouses() {
            return [
                {code: "all", label: "Todos los almacenes"}
            ].concat((this.options.warehouses || []).map(record => ({
                code: record.id,
                label: record.name
            })));
        },
        destinationWarehouses() {
            return this.realWarehouses.filter(warehouse => Number(warehouse.code) !== Number(this.filters.warehouse?.code));
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
            return INVENTORY_OPERATIONS;
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
            if(this.activeView === "stock") return this.loadingStock;
            if(this.activeView === "guides") return this.loadingGuides;

            return this.loadingKardex;
        },
        isConsolidatedStock() {
            return this.filters.warehouse?.code === "all";
        }
    },
    watch: {
        "filters.warehouse"(value) {
            if(!value?.code) return;
            this.stockRecords = {total: 0, data: []};
            this.kardexRecords = {total: 0, data: []};
            this.guideRecords = {total: 0, data: []};
            this.listCurrentView({});
            this.transferForm.destination = this.destinationWarehouses[0] ?? null;
        }
    }
};
</script>
