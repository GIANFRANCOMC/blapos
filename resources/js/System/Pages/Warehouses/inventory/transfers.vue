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
                    :placeholder="productSearchPlaceholder"
                    :inputClass="['form-control']"
                    :disabled="isCurrentViewLoading || !filters.warehouse?.code"
                    xl="3"
                    lg="3"
                    md="6"/>

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
                        <SearchButton
                            class="br-btn-sm"
                            :disabled="!filters.warehouse?.code || isCurrentViewLoading"
                            @click="searchCurrentView"/>
                        <ClearFiltersButton
                            :disabled="isCurrentViewLoading || !filters.warehouse?.code"
                            @clear="clearFilters"/>
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
                                <button type="button" class="dropdown-item" :disabled="isCurrentViewLoading || exporting || !appliedFilters.warehouse?.code" @click="exportCurrentView">
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
                                aria-hidden="true"
                                v-on="{ 'hidden.bs.modal': () => resetOptions('items') }">
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

                                        <div class="modal-body br-entity-modal__body br-inventory-transfer__body">
                                            <div class="br-inventory-operation__config br-inventory-transfer__config">
                                                <WarehouseSelectionField
                                                    id="transferSource"
                                                    v-model="transferForm.source"
                                                    class="br-inventory-transfer__warehouse"
                                                    label="Almacén de origen"
                                                    :options="realWarehouses"
                                                    :select-class="config.forms.classes.select2"
                                                    placeholder="Selecciona el almacén que envía"
                                                    :disabled="savingTransfer"
                                                    :error="firstError(transferErrors.source_warehouse_id)"
                                                    required
                                                    append-to-body/>

                                                <span class="br-inventory-transfer__direction" aria-hidden="true">
                                                    <i class="fa-solid fa-right-long fa-lg lh-1"></i>
                                                </span>

                                                <WarehouseSelectionField
                                                    id="transferDestination"
                                                    v-model="transferForm.destination"
                                                    class="br-inventory-transfer__warehouse"
                                                    label="Almacén de destino"
                                                    :options="destinationWarehouses"
                                                    :select-class="config.forms.classes.select2"
                                                    :disabled="savingTransfer || !transferForm.source?.code"
                                                    placeholder="Selecciona el almacén que recibe"
                                                    :error="firstError(transferErrors.destination_warehouse_id)"
                                                    required
                                                    append-to-body/>

                                                <div v-if="canConfigureTransfer" class="br-inventory-transfer__reason">
                                                    <QuickCreateTrigger
                                                        mode="icon"
                                                        title="Motivos frecuentes"
                                                        icon="fa-solid fa-arrow-up-right-from-square"
                                                        custom-class="br-quick-create-trigger--field-label waves-effect"
                                                        aria-haspopup="dialog"
                                                        aria-controls="transferReasonsDialog"
                                                        :disabled="savingTransfer"
                                                        @click="openTransferReasons"/>
                                                    <label for="transferReason" class="form-label colon-at-end">Motivo del traslado</label>
                                                    <span class="text-danger ms-1 fw-bold" aria-hidden="true">*</span>
                                                    <InputText
                                                        id="transferReason"
                                                        ref="transferReason"
                                                        v-model.trim="transferForm.reason"
                                                        :disabled="savingTransfer"
                                                        aria-required="true"
                                                        placeholder="Describe por qué se realiza el traslado"
                                                        maxlength="255"
                                                        showCharCounter
                                                        hasTextBottom
                                                        :textBottomInfo="transferErrors.reason || []"/>
                                                </div>
                                            </div>

                                            <div v-if="canConfigureTransfer" class="br-inventory-operation__items br-inventory-transfer__items">
                                                <div class="br-inventory-operation__items-header">
                                                    <div class="br-inventory-operation__items-title">
                                                        <span id="inventoryTransferItemsTitle" class="form-label colon-at-end">Productos incluidos</span>
                                                        <span class="text-danger fw-bold" aria-hidden="true">*</span>
                                                    </div>
                                                    <small v-if="transferAvailabilityError" class="text-danger">
                                                        No se pudo consultar el stock.
                                                        <button type="button" class="btn btn-link p-0" @click="loadTransferAvailability(transferAvailabilityQuery)">Reintentar</button>
                                                    </small>
                                                    <button
                                                        type="button"
                                                        class="br-btn br-btn-add-row ms-auto waves-effect"
                                                        :disabled="savingTransfer || transferForm.items.length >= 100"
                                                        @click="addTransferItem">
                                                        Agregar producto
                                                    </button>
                                                </div>

                                                <div
                                                    class="table-responsive br-inventory-operation__table-wrap br-inventory-transfer__table-scroll"
                                                    ref="transferItemsScroll"
                                                    role="region"
                                                    aria-labelledby="inventoryTransferItemsTitle"
                                                    tabindex="0">
                                                    <table class="table br-entity-table br-inventory-table-headings br-inventory-operation__table br-inventory-transfer__items-table mb-0">
                                                        <thead class="br-table-header-surface">
                                                            <tr>
                                                                <th scope="col">Producto</th>
                                                                <th scope="col">Stock disponible</th>
                                                                <th scope="col">Cantidad a trasladar</th>
                                                                <th scope="col"><span class="visually-hidden">Quitar</span></th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <tr v-for="(item, index) in transferForm.items" :key="item.key">
                                                                <td>
                                                                    <ProductSelect
                                                                        v-model="item.product"
                                                                        :disabled="savingTransfer"
                                                                        :options="transferProductOptions(item.product)"
                                                                        :select-class="config.forms.classes.select2"
                                                                        :loading="remoteOptions.items.loading"
                                                                        :placeholder="productSearchPlaceholder"
                                                                        remote
                                                                        summarize-selection
                                                                        @open="openTransferProductOptions"
                                                                        @search="searchOptions('items', $event)">
                                                                        <template #no-options>
                                                                            {{ remoteOptions.items.loading ? 'Buscando productos…' : remoteOptions.items.error ? 'No se pudieron consultar los productos.' : 'Sin productos para esta búsqueda.' }}
                                                                        </template>
                                                                        <template #list-footer>
                                                                            <li v-if="remoteOptions.items.hasMore || remoteOptions.items.error" class="px-2 py-1">
                                                                                <button
                                                                                    type="button"
                                                                                    class="btn btn-link btn-sm w-100 text-start waves-effect"
                                                                                    :disabled="remoteOptions.items.loading"
                                                                                    @mousedown.prevent
                                                                                    @click.stop="loadOptions('items', remoteOptions.items.page + 1)">
                                                                                    {{ remoteOptions.items.error ? 'Reintentar consulta' : 'Cargar más productos' }}
                                                                                </button>
                                                                            </li>
                                                                        </template>
                                                                    </ProductSelect>
                                                                    <small v-if="transferItemError(index, 'item_id')" class="text-danger">
                                                                        {{ transferItemError(index, "item_id") }}
                                                                    </small>
                                                                </td>
                                                                <td class="text-center">
                                                                    <QuantityValue
                                                                        v-if="item.product && transferAvailability[item.product.code] != null"
                                                                        class="text-nowrap"
                                                                        :value="transferAvailability[item.product.code]"
                                                                        :unit="item.product"/>
                                                                    <small v-else-if="item.product && loadingTransferAvailability" class="text-muted">Consultando…</small>
                                                                    <span v-else class="text-muted" aria-label="Stock disponible no informado">—</span>
                                                                </td>
                                                                <td>
                                                                    <InputNumber
                                                                        v-model="item.quantity"
                                                                        :hasNegative="false"
                                                                        :disabled="savingTransfer"
                                                                        hasTextBottom
                                                                        :text-bottom-info="transferQuantityErrors(item, index)"
                                                                        @input="clearTransferQuantityError(index)"
                                                                        :aria-label="item.product?.unitAbbreviation ? `Cantidad a trasladar (${item.product.unitAbbreviation})` : 'Cantidad a trasladar'">
                                                                        <template #inputGroupAppend>
                                                                            <span
                                                                                v-if="item.product?.unitAbbreviation"
                                                                                class="input-group-text br-input-unit-suffix"
                                                                                aria-hidden="true">{{ item.product.unitAbbreviation }}</span>
                                                                        </template>
                                                                    </InputNumber>
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
                                                                            :disabled="savingTransfer"
                                                                            :aria-label="`Quitar producto ${index + 1}`"
                                                                            @click="removeTransferItem(index)">
                                                                            <i class="fa-solid fa-trash" aria-hidden="true"></i>
                                                                        </button>
                                                                    </span>
                                                                </td>
                                                            </tr>
                                                            <tr v-if="!transferForm.items.length" class="br-table-state">
                                                                <td colspan="4" class="text-center text-muted">Agrega un producto al traslado.</td>
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
                                                Cerrar
                                            </button>
                                            <div v-if="canConfigureTransfer" class="br-entity-modal__footer-actions">
                                                <button
                                                    type="button"
                                                    class="br-btn br-btn-action-create"
                                                    :disabled="savingTransfer || !transferForm.items.length || hasTransferQuantityOverStock"
                                                    @click="saveTransfer">
                                                    Guardar traslado
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </Teleport>

                        <FormModal
                            v-if="canConfigureTransfer"
                            modal-id="transferReasonsDialog"
                            title="Motivos frecuentes"
                            modal-size="modal-lg"
                            modal-class="br-modal-standard"
                            dialog-class="modal-dialog-scrollable"
                            :show-submit="false">
                            <div class="table-responsive">
                                <table class="table br-entity-table br-inventory-table-headings br-inventory-transfer__reasons-table mb-0">
                                    <colgroup>
                                        <col>
                                        <col class="br-inventory-transfer__reason-action">
                                    </colgroup>
                                    <thead class="br-table-header-surface">
                                        <tr>
                                            <th scope="col">Motivo</th>
                                            <th scope="col">Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="option in transferReasonOptions" :key="option.label">
                                            <td>
                                                {{ option.label }}
                                                <span class="br-inventory__meta">{{ option.text }}</span>
                                            </td>
                                            <td class="text-center">
                                                <a
                                                    href="#transferReason"
                                                    class="br-link"
                                                    :aria-label="`Seleccionar ${option.label}`"
                                                    @click.prevent="applyTransferReason(option.text)">
                                                    <i class="fa-solid fa-hand-pointer" aria-hidden="true"></i>
                                                    <span class="ms-1">Seleccionar</span>
                                                </a>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </FormModal>

                        <section class="br-entity-list" aria-label="Traslados registrados">
                            <div class="table-responsive">
                                <table class="table br-entity-table br-inventory-table-headings br-inventory-transfer__table mb-0">
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
                                            <th>Cantidad</th>
                                            <th>Referencia</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr class="br-table-state" v-if="loadingKardex">
                                            <td colspan="7" class="py-4"><Loader/></td>
                                        </tr>
                                        <template v-else-if="kardexRecords.total > 0">
                                            <tr v-for="movement in kardexRecords.data" :key="`transfer-${movement.id}`">
                                                <td>
                                                    <strong>{{ formatDate(movement.created_at) }}</strong>
                                                    <span class="br-inventory__meta">{{ movement.user?.name || "Proceso del sistema" }}</span>
                                                </td>
                                                <td>
                                                    <ProductIdentity variant="expandable" :product="movement.item"/>
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
                                                <td class="text-end br-inventory__difference"><QuantityValue :value="movement.quantity_change" :unit="movement.item?.measurement_unit || movement.item" signed/></td>
                                                <td>
                                                    <strong>{{ movement.metadata?.reference || movement.reference || "-" }}</strong>
                                                    <span class="br-inventory__meta">{{ movement.reason }}</span>
                                                </td>
                                            </tr>
                                        </template>
                                        <tr class="br-table-state" v-else>
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

                <Paginator :aria-label="`Paginación de ${currentView.title.toLocaleLowerCase('es')}`" :links="currentRecords.links" :pagination="currentRecords" :loading="isCurrentViewLoading" @clickPage="listCurrentView"/>
            </div>
        </section>
    </InitialLoadTransition>
</template>

<script>
import WarehouseSelectionField from "@System/Components/Generics/WarehouseSelectionField.vue";
import {createOptionSearch} from "@/Shared/Options.js";
import inventoryPage from "./inventoryState.js";

export default {
    ...inventoryPage,
    components: {...inventoryPage.components, WarehouseSelectionField},
    mixins: [createOptionSearch("items")],
    computed: {
        ...inventoryPage.computed,
        optionOrigin: () => "inventory-transfers",
        transferSelectedProductIds() {
            return new Set(this.transferForm.items.map(item => item.product?.code));
        }
    },
    methods: {
        ...inventoryPage.methods,
        openTransferProductOptions() {
            if(this.remoteOptions.items.term) return this.searchOptions("items", "");
            return this.openOptions("items");
        },
        transferProductOptions(selectedProduct) {
            if(!this.remoteOptions.items.loaded) return [];

            const records = this.options.items?.records ?? [];
            return records.filter(product => product.code === selectedProduct?.code || !this.transferSelectedProductIds.has(product.code));
        }
    }
};
</script>
