<template>
    <Breadcrumb :list="breadcrumbTitles"/>

    <InitialLoadTransition>
        <InventorySkeleton v-if="initialLoading" :page="page"/>

        <section v-else class="br-inventory">
            <div class="row align-items-end g-3 br-inventory__context">
                <div v-for="[field, label] in transferWarehouseFilters" :key="field" class="col-12 col-sm-6 col-lg-3 br-inventory__warehouse">
                    <label :for="`transferFilter-${field}`" class="form-label">{{ label }}</label>
                    <WarehouseSelect
                        :id="`transferFilter-${field}-select`"
                        :input-id="`transferFilter-${field}`"
                        v-model="filters[field]"
                        :options="warehouses"
                        :disabled="isCurrentViewLoading"
                        placeholder="Todos los almacenes"
                        clearable
                        :select-class="config.forms.classes.select2"/>
                </div>

                <SearchInput
                    v-model.trim="filters.transferReference"
                    hasDiv
                    title="Referencia"
                    :titleClass="[config.forms.classes.title]"
                    placeholder="Buscar referencia"
                    maxlength="40"
                    :inputClass="['form-control']"
                    :disabled="isCurrentViewLoading"
                    xl="2"
                    lg="2"
                    md="6"
                    sm="6"/>

                <div class="col-12 col-sm-6 col-lg-2">
                    <label for="inventoryDateFrom" class="form-label">Desde</label>
                    <input id="inventoryDateFrom" v-model="filters.dateFrom" type="date" class="form-control"
                        :disabled="isCurrentViewLoading">
                </div>

                <div class="col-12 col-sm-6 col-lg-2">
                    <label for="inventoryDateTo" class="form-label">Hasta</label>
                    <input id="inventoryDateTo" v-model="filters.dateTo" type="date" class="form-control"
                        :disabled="isCurrentViewLoading">
                </div>

                <SearchInput
                    ref="productSearch"
                    v-model.trim="filters.productSearch"
                    hasDiv
                    title="Producto"
                    :titleClass="[config.forms.classes.title]"
                    :placeholder="productSearchPlaceholder"
                    :inputClass="['form-control']"
                    :disabled="isCurrentViewLoading"
                    xl="3"
                    lg="3"
                    md="6"
                    sm="6"/>

                <div class="col-12 col-sm-6 col-lg-2">
                    <label for="transferStatusFilter" class="form-label">Estado del traslado</label>
                    <v-select id="transferStatusFilter" v-model="filters.transferStatus" :options="transferStatusFilters"
                        :class="config.forms.classes.select2" :disabled="isCurrentViewLoading"
                        :clearable="false" :searchable="false"/>
                </div>

                <div class="col-auto flex-grow-1 d-flex flex-wrap align-items-end gap-2">
                    <div class="br-inventory__context-actions d-flex flex-nowrap align-items-stretch gap-2">
                        <SearchButton
                            class="br-btn-sm"
                            :disabled="isCurrentViewLoading"
                            @click="searchCurrentView"/>
                        <ClearFiltersButton
                            :disabled="isCurrentViewLoading"
                            @clear="clearFilters"/>
                        <button
                            type="button"
                            class="br-btn br-btn-sm br-btn-actions"
                            :disabled="isCurrentViewLoading"
                            data-bs-toggle="dropdown"
                            aria-expanded="false">
                            <span>Acciones</span>
                            <i class="fa-solid fa-chevron-down br-btn-actions__chevron" aria-hidden="true"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end br-btn-actions-menu">
                            <li>
                                <button type="button" class="dropdown-item" :disabled="isCurrentViewLoading || exporting" @click="exportCurrentView">
                                    <i class="fa-solid fa-file-excel" aria-hidden="true"></i>
                                    <span>Descargar Excel</span>
                                </button>
                            </li>
                        </ul>
                    </div>
                    <div v-if="canOperateTransfers" class="br-inventory__context-actions d-flex flex-nowrap align-items-stretch gap-2 ms-auto">
                        <span class="vr align-self-center" aria-hidden="true"></span>
                        <button
                            type="button"
                            class="br-btn br-btn-sm br-btn-action-open-create waves-effect"
                            :disabled="isCurrentViewLoading"
                            data-bs-toggle="modal"
                            data-bs-target="#inventoryTransferModal"
                            @click="prepareTransfer">
                            <i class="fa-solid fa-plus" aria-hidden="true"></i>
                            <span>Registrar traslado</span>
                        </button>
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

                                                <div v-if="canConfigureTransfer" class="br-inventory-transfer__form-status">
                                                    <label for="transferFormStatus" class="form-label">Estado del traslado</label>
                                                    <v-select id="transferFormStatus" v-model="transferForm.status" :options="transferRegistrationStatuses"
                                                        :reduce="status => status.code" :class="config.forms.classes.select2"
                                                        :clearable="false" :searchable="false" :disabled="savingTransfer"/>
                                                    <small v-if="transferErrors.status" class="text-danger">{{ firstError(transferErrors.status) }}</small>
                                                    <small v-else-if="transferForm.status !== 'accepted'" class="br-inventory__meta">El stock se actualizará al aceptar el traslado.</small>
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
                                                    <div class="d-inline-flex align-items-center gap-2 ms-auto">
                                                        <button
                                                            ref="addTransferProduct"
                                                            type="button"
                                                            class="br-btn br-btn-xs br-btn-add-row waves-effect"
                                                            :disabled="savingTransfer || transferForm.items.length >= 100"
                                                            @click="addTransferItem">
                                                            Agregar producto
                                                        </button>
                                                        <button
                                                            type="button"
                                                            class="br-btn br-btn-xs br-btn-danger waves-effect"
                                                            :disabled="savingTransfer || !transferForm.items.length"
                                                            @click="clearTransferItems">
                                                            Eliminar productos
                                                        </button>
                                                    </div>
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

                        <TransferDetailModal
                            :transfer="selectedTransfer"
                            :loading="loadingTransferDetail"
                            :error="transferDetailError"
                            :status="transferStatusInfo(selectedTransfer?.status)"
                            :next-status="nextTransferStatus"
                            :can-cancel="canCancelTransfer"
                            :saving-status="savingTransferStatus"
                            :status-error="transferStatusError"
                            :print-url="selectedTransfer ? transferPrintUrl(selectedTransfer) : null"
                            :format-date="formatDate"
                            @retry="loadTransferDetail(transferDetailId)"
                            @advance="changeTransferStatus(nextTransferStatus)"
                            @cancel="changeTransferStatus('canceled')"
                            @view-cancellation-reason="viewTransferCancellationReason"/>

                        <section class="br-entity-list" aria-label="Traslados registrados" :aria-busy="loadingTransfers">
                            <div class="table-responsive">
                                <table class="table br-entity-table br-inventory-table-headings br-inventory-transfer__table mb-0">
                                    <colgroup>
                                        <col class="br-inventory-transfer__col-reference">
                                        <col class="br-inventory-transfer__col-date">
                                        <col class="br-inventory-transfer__col-source">
                                        <col class="br-inventory-transfer__col-direction">
                                        <col class="br-inventory-transfer__col-destination">
                                        <col class="br-inventory-transfer__col-count">
                                        <col class="br-inventory-transfer__col-status">
                                        <col class="br-entity-table__col-actions">
                                    </colgroup>
                                    <thead class="br-table-header-surface">
                                        <tr>
                                            <th scope="col">Referencia</th>
                                            <th scope="col">Fecha y responsable</th>
                                            <th scope="col">Almacén de origen</th>
                                            <th aria-hidden="true"></th>
                                            <th scope="col">Almacén de destino</th>
                                            <th scope="col">Productos</th>
                                            <th scope="col">Estado</th>
                                            <th scope="col"><span class="visually-hidden">Acciones</span></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr class="br-table-state" v-if="loadingTransfers">
                                            <td colspan="8" class="py-4"><Loader/></td>
                                        </tr>
                                        <template v-else-if="transferRecords.data.length">
                                            <tr v-for="transfer in transferRecords.data" :key="transfer.id">
                                                <td class="text-center br-inventory-transfer__reference">{{ transfer.reference }}</td>
                                                <td>
                                                    <span class="text-nowrap">{{ formatDate(transfer.created_at) }}</span>
                                                    <span class="br-inventory__meta">{{ transfer.user?.name || "Proceso del sistema" }}</span>
                                                </td>
                                                <td>
                                                    <span>{{ transfer.source_warehouse?.branch?.name || transfer.source_warehouse?.name || "Almacén no visible" }}</span>
                                                    <span v-if="transfer.source_warehouse?.branch?.name" class="br-inventory__meta">{{ transfer.source_warehouse.name }}</span>
                                                </td>
                                                <td class="px-0" aria-hidden="true">
                                                    <span class="br-inventory-transfer__direction mx-auto">
                                                        <i class="fa-solid fa-right-long fa-lg lh-1"></i>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span>{{ transfer.destination_warehouse?.branch?.name || transfer.destination_warehouse?.name || "Almacén no visible" }}</span>
                                                    <span v-if="transfer.destination_warehouse?.branch?.name" class="br-inventory__meta">{{ transfer.destination_warehouse.name }}</span>
                                                </td>
                                                <td class="text-center">{{ transfer.items_count }}</td>
                                                <td class="text-center">
                                                    <span :class="['br-badge', `br-badge--${transferStatusInfo(transfer.status).tone}`]">{{ transferStatusInfo(transfer.status).label }}</span>
                                                </td>
                                                <td class="text-center">
                                                    <TableActionsDropdown :label="`Acciones de ${transfer.reference}`">
                                                        <li>
                                                            <button type="button" class="dropdown-item waves-effect" data-bs-toggle="modal" data-bs-target="#inventoryTransferDetailModal" @click="loadTransferDetail(transfer.id)">
                                                                <i class="fa-solid fa-eye" aria-hidden="true"></i>
                                                                <span>Ver detalle</span>
                                                            </button>
                                                        </li>
                                                    </TableActionsDropdown>
                                                </td>
                                            </tr>
                                        </template>
                                        <tr class="br-table-state" v-else>
                                            <td colspan="8" class="py-4">
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
import * as Requests from "@System/Helpers/Requests.js";
import * as Alerts from "@System/Helpers/Alerts.js";
import {createOptionSearch} from "@/Shared/Options.js";
import TransferDetailModal from "./TransferDetailModal.vue";
import inventoryPage from "./inventoryState.js";

export default {
    ...inventoryPage,
    components: {...inventoryPage.components, TransferDetailModal, WarehouseSelectionField},
    mixins: [createOptionSearch("items")],
    data() {
        return {
            ...inventoryPage.data.call(this),
            selectedTransfer: null,
            transferDetailId: null,
            transferDetailError: "",
            transferStatusError: "",
            savingTransferStatus: false,
            loadingTransferDetail: false,
            transferDetailRequest: 0
        };
    },
    computed: {
        ...inventoryPage.computed,
        optionOrigin: () => "inventory-transfers",
        transferWarehouseFilters: () => [["sourceWarehouse", "Almacén de origen"], ["destinationWarehouse", "Almacén de destino"]],
        transferRegistrationStatuses() {
            return this.options.transferStatuses.filter(status => status.code !== "canceled");
        },
        canManageSelectedTransfer() {
            const transfer = this.selectedTransfer;
            return Boolean(this.canOperateTransfers && transfer?.source_warehouse && transfer.destination_warehouse);
        },
        canCancelTransfer() {
            return this.canManageSelectedTransfer && this.transferStatusInfo(this.selectedTransfer.status).can_cancel === true;
        },
        nextTransferStatus() {
            return this.canManageSelectedTransfer ? this.transferStatusInfo(this.selectedTransfer.status).next || null : null;
        },
        transferSelectedProductIds() {
            return new Set(this.transferForm.items.map(item => item.product?.code));
        }
    },
    methods: {
        ...inventoryPage.methods,
        transferUrl(id) {
            return `${Requests.config({entity: "stocks_management", type: "transfers"})}/${encodeURIComponent(id)}`;
        },
        transferPrintUrl(transfer) {
            return `${this.transferUrl(transfer.id)}/print`;
        },
        transferStatusInfo(code) {
            return this.options.transferStatuses.find(status => status.code === code) || {label: "—", tone: "neutral"};
        },
        viewTransferCancellationReason() {
            return Alerts.showMessage({
                title: "Motivo de cancelación",
                text: this.selectedTransfer?.cancellation_reason,
                target: "#inventoryTransferDetailModal"
            });
        },
        async changeTransferStatus(status) {
            const transfer = this.selectedTransfer;
            if(!status || !this.canManageSelectedTransfer || this.savingTransferStatus) return;
            if(status === "canceled" && !this.canCancelTransfer) return;

            this.savingTransferStatus = true;
            this.transferStatusError = "";
            try {
                if(status === "accepted" && !await Alerts.confirmAction({
                    title: "¿Aceptar este traslado?",
                    text: "Se descontará el stock del almacén de origen y se registrará la entrada en el destino.",
                    confirmButtonText: "Aceptar traslado"
                })) return;

                const data = {status};
                if(status === "canceled") {
                    const reason = await Alerts.confirmActionWithReason({
                        title: "¿Cancelar este traslado?",
                        text: "El traslado quedará registrado como cancelado.",
                        icon: "warning",
                        confirmButtonText: "Confirmar cancelación",
                        target: "#inventoryTransferDetailModal"
                    });
                    if(reason === null) return;

                    data.expected_status = transfer.status;
                    data.cancellation_reason = reason;
                }
                const result = await Requests.patch({route: `${this.transferUrl(transfer.id)}/status`, data});
                if(result?.bool) {
                    if(this.transferDetailId === transfer.id) this.selectedTransfer = result.data.data;
                    await this.listCurrentView();
                }else if(this.transferDetailId === transfer.id) {
                    this.transferStatusError = result?.data?.msg || "No se pudo actualizar el estado del traslado.";
                }
            } finally {
                this.savingTransferStatus = false;
            }
        },
        async loadTransferDetail(id) {
            const request = ++this.transferDetailRequest;
            this.transferDetailId = id;
            this.selectedTransfer = null;
            this.transferDetailError = "";
            this.transferStatusError = "";
            this.loadingTransferDetail = true;

            try {
                const result = await Requests.get({route: this.transferUrl(id)});
                if(request !== this.transferDetailRequest) return;

                if(result?.bool) this.selectedTransfer = result.data.data;
                else this.transferDetailError = "No se pudo cargar el detalle del traslado.";
            } finally {
                if(request === this.transferDetailRequest) this.loadingTransferDetail = false;
            }
        },
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
