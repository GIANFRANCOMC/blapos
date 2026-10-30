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
                <div  class="br-inventory-transfer">
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
            </section>

            <nav
                v-if="!isCurrentViewLoading && currentRecords.total > 0 && currentRecords.links"
                class="d-flex justify-content-center mt-3"
                :aria-label="`Paginación de ${currentView.title.toLocaleLowerCase('es')}`">
                <Paginator :links="currentRecords.links" @clickPage="listCurrentView"/>
            </nav>
        </div>
    </section>
</template>

<script>
import inventoryPage from "./inventoryState.js";

export default inventoryPage;
</script>
