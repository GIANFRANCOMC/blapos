<template>
    <InventoryListing ref="inventoryListing" :page="page" v-model:filters="filters" :options="listingOptions"
        :initial-loading="initialLoading" :loading="isCurrentViewLoading" :records="currentRecords"
        :exporting="exporting" :applied-filters="appliedFilters" :applied-warehouse="Boolean(appliedFilters.warehouse?.code)" :error="listError"
        @search="searchCurrentView" @clear="clearFilters" @export="exportCurrentView" @page="listCurrentView" @retry="retryInventoryList">
        <template #actions>
            <FgButton v-if="canOperateTransfers" icon="fa-solid fa-plus" :disabled="initialLoading || isCurrentViewLoading" data-bs-toggle="modal" data-bs-target="#inventoryTransferModal" @click="prepareTransfer">Registrar traslado</FgButton>
        </template>
        <tr role="row" v-for="transfer in transferRecords.data" :key="transfer.id">
            <td role="cell" class="fg-record-table__identity fg-align-center fg-inventory-reference">{{ transfer.reference }}</td>
            <td role="cell" class="fg-align-end">
                <span class="fg-record-table__label" aria-hidden="true">Almacén de origen</span>
                <div class="fg-record-table__value">
                    <span>{{ transfer.source_warehouse?.branch?.name || transfer.source_warehouse?.name || "Almacén no visible" }}</span>
                    <span v-if="transfer.source_warehouse?.branch?.name" class="fg-table-note">{{ transfer.source_warehouse.name }}</span>
                </div>
            </td>
            <td role="cell" class="px-0">
                <span class="fg-record-table__label" aria-hidden="true">Productos incluidos</span>
                <div class="fg-record-table__value">
                    <span class="fg-transfer-direction mx-auto">
                        <i class="fa-solid fa-right-long fa-lg lh-1" aria-hidden="true"></i>
                        <span class="fg-transfer-count" tabindex="0" data-bs-toggle="tooltip"
                            :title="`${transfer.items_count} ${Number(transfer.items_count) === 1 ? 'producto' : 'productos'}`">
                            <span class="visually-hidden">Productos incluidos: </span>{{ transfer.items_count }}
                        </span>
                    </span>
                </div>
            </td>
            <td role="cell">
                <span class="fg-record-table__label" aria-hidden="true">Almacén de destino</span>
                <div class="fg-record-table__value">
                    <span>{{ transfer.destination_warehouse?.branch?.name || transfer.destination_warehouse?.name || "Almacén no visible" }}</span>
                    <span v-if="transfer.destination_warehouse?.branch?.name" class="fg-table-note">{{ transfer.destination_warehouse.name }}</span>
                </div>
            </td>
            <td role="cell" class="text-start">
                <span class="fg-record-table__label" aria-hidden="true">Detalle</span>
                <div class="fg-record-table__value">
                    <span class="d-flex align-items-center gap-1">
                        <i class="fa-regular fa-calendar fg-help" tabindex="0" role="img" aria-label="Fecha de registro"
                            title="Fecha de registro" data-bs-toggle="tooltip"></i>
                        <span>{{ formatTransferDate(transfer.created_at) }}</span>
                    </span>
                    <span class="fg-table-note d-flex align-items-center gap-1">
                        <i class="fa-regular fa-user" tabindex="0" role="img" aria-label="Registrado por"
                            title="Registrado por" data-bs-toggle="tooltip"></i>
                        <span>{{ transfer.user?.name || "Proceso del sistema" }}</span>
                    </span>
                </div>
            </td>
            <td role="cell" class="fg-record-table__state fg-align-center">
                <span :class="['fg-badge', `fg-badge--${transferStatusInfo(transfer.status).tone}`]">{{ transferStatusInfo(transfer.status).label }}</span>
            </td>
            <td role="cell" class="fg-record-table__actions fg-align-center">
                <TableActionsDropdown :label="`Acciones de ${transfer.reference}`">
                    <li>
                        <button type="button" class="fg-menu__item" data-bs-toggle="modal" data-bs-target="#inventoryTransferDetailModal" @click="loadTransferDetail(transfer.id)">
                            <span>Ver detalle</span>
                        </button>
                    </li>
                </TableActionsDropdown>
            </td>
        </tr>
    </InventoryListing>

    <FormModal modal-id="inventoryTransferModal" title="Registrar traslado" modal-class="fg-inventory-dialog" modal-size="modal-lg"
        :keyboard="false" :show-submit="false" :close-disabled="savingTransfer" @hidden="resetOptions('items')">
        <template #body>
            <div class="fg-stack">
                <div class="fg-section-heading"><h3 class="fg-section-title">Datos del traslado</h3><span class="fg-help"><span class="fg-required">*</span> Obligatorios</span></div>
                <div class="fg-transfer-route">
                    <WarehouseSelectionField id="transferSource" v-model="transferForm.source" :summarize-selection="false"
                        label="Almacén de origen" :options="realWarehouses" placeholder="Selecciona el almacén que envía"
                        :disabled="savingTransfer" :error="firstError(transferErrors.source_warehouse_id)" required/>
                    <span class="fg-transfer-direction" aria-hidden="true"><i class="fa-solid fa-right-long"></i></span>
                    <WarehouseSelectionField id="transferDestination" v-model="transferForm.destination" :summarize-selection="false"
                        label="Almacén de destino" :options="destinationWarehouses" placeholder="Selecciona el almacén que recibe"
                        :disabled="savingTransfer || !transferForm.source?.code" :error="firstError(transferErrors.destination_warehouse_id)" required/>
                    <FgField label="Estado del traslado" input-id="transferFormStatus" :error="transferErrors.status"
                        :help="transferForm.status === 'in_transit' ? 'Se descontará el stock del origen.' : 'El stock saldrá al confirmar el envío.'">
                        <FgSelect v-model="transferForm.status" :options="transferRegistrationStatuses" :reduce="status => status.code"
                            :get-option-key="status => status.code" :clearable="false" :searchable="false" :disabled="savingTransfer"/>
                    </FgField>
                </div>

                <template v-if="canConfigureTransfer">
                    <InputText id="transferReason" ref="transferReason" v-model.trim="transferForm.reason" title="Motivo del traslado"
                        :disabled="savingTransfer" is-required placeholder="Describe por qué se realiza el traslado"
                        maxlength="255" show-char-counter has-text-bottom :text-bottom-info="transferErrors.reason || []">
                        <template #default>
                            <QuickCreateTrigger mode="icon" title="Motivos frecuentes" icon="fa-solid fa-arrow-up-right-from-square"
                                custom-class="fg-field__action" aria-haspopup="dialog" aria-controls="transferReasonsDialog" :disabled="savingTransfer"
                                @click="openTransferReasons"/>
                        </template>
                    </InputText>
                    <section class="fg-stack" aria-labelledby="inventoryTransferItemsTitle">
                        <div class="fg-inventory-section-actions">
                            <h3 id="inventoryTransferItemsTitle" class="fg-section-title">Productos incluidos <span class="fg-required" aria-hidden="true">*</span></h3>
                            <div class="fg-inventory-dialog-actions">
                                <button ref="addTransferProduct" type="button" class="fg-link" :disabled="savingTransfer || transferForm.items.length >= 100"
                                    @click="addTransferItem"><i class="fa-solid fa-plus" aria-hidden="true"></i> Agregar producto</button>
                                <button type="button" class="fg-link fg-error" :disabled="savingTransfer || !transferForm.items.length"
                                    @click="clearTransferItems">Eliminar productos</button>
                            </div>
                        </div>
                        <p v-if="transferAvailabilityError" class="fg-notice fg-notice--danger" role="alert">
                            No se pudo consultar el stock.
                            <button type="button" class="fg-link" @click="loadTransferAvailability(transferAvailabilityQuery)">Reintentar</button>
                        </p>
                        <div ref="transferItemsScroll" class="fg-table-scroll fg-table-scroll--records" role="region" aria-labelledby="inventoryTransferItemsTitle" tabindex="0">
                            <table class="fg-table fg-inventory-items">
                                <thead><tr>
                                    <th scope="col">Producto</th>
                                    <th scope="col" class="fg-align-end">Stock disponible</th>
                                    <th scope="col">Cantidad a trasladar</th>
                                    <th scope="col"><span class="fg-sr-only">Quitar</span></th>
                                </tr></thead>
                                <tbody>
                                    <tr v-for="(item, index) in transferForm.items" :key="item.key">
                                        <td>
                                            <ProductSelect v-model="item.product" :disabled="savingTransfer" :options="transferProductOptions(item.product)"
                                                :loading="remoteOptions.items.loading" :placeholder="productSearchPlaceholder" :aria-label="`Producto ${index + 1}`"
                                                remote summarize-selection @open="openTransferProductOptions" @search="searchOptions('items', $event)">
                                                <template #no-options>
                                                    {{ remoteOptions.items.loading ? 'Buscando productos…' : remoteOptions.items.error ? 'No se pudieron consultar los productos.' : 'Sin productos para esta búsqueda.' }}
                                                </template>
                                                <template #list-footer>
                                                    <li v-if="remoteOptions.items.hasMore || remoteOptions.items.error" class="p-2">
                                                        <button type="button" class="fg-link" :disabled="remoteOptions.items.loading" @mousedown.prevent
                                                            @click.stop="loadOptions('items', remoteOptions.items.page + 1)">
                                                            {{ remoteOptions.items.error ? 'Reintentar consulta' : 'Cargar más productos' }}
                                                        </button>
                                                    </li>
                                                </template>
                                            </ProductSelect>
                                            <small v-if="transferItemError(index, 'item_id')" class="fg-error">{{ transferItemError(index, "item_id") }}</small>
                                        </td>
                                        <td class="fg-align-end">
                                            <QuantityValue v-if="item.product && transferAvailability[item.product.code] != null" class="fg-inventory-quantity"
                                                :value="transferAvailability[item.product.code]" :unit="item.product"/>
                                            <small v-else-if="item.product && loadingTransferAvailability" class="fg-help">Consultando…</small>
                                            <span v-else class="fg-help" aria-label="Stock disponible no informado">—</span>
                                        </td>
                                        <td>
                                            <InputNumber v-model="item.quantity" :has-negative="false" :disabled="savingTransfer" has-text-bottom
                                                :text-bottom-info="transferQuantityErrors(item, index)" @input="clearTransferQuantityError(index)"
                                                :aria-label="item.product?.unitAbbreviation ? `Cantidad a trasladar (${item.product.unitAbbreviation})` : 'Cantidad a trasladar'">
                                                <template #inputGroupAppend>
                                                    <span v-if="item.product?.unitAbbreviation" class="fg-field__prefix fg-quantity-unit" aria-hidden="true">{{ item.product.unitAbbreviation }}</span>
                                                </template>
                                            </InputNumber>
                                        </td>
                                        <td class="fg-align-center">
                                            <FgButton tone="icon" class="fg-icon-danger" icon="fa-solid fa-trash" :disabled="savingTransfer"
                                                :aria-label="`Quitar producto ${index + 1}`" data-bs-toggle="tooltip" title="Quitar producto" @click="removeTransferItem(index)"/>
                                        </td>
                                    </tr>
                                    <tr v-if="!transferForm.items.length" class="fg-table__state">
                                        <td colspan="4"><p class="fg-inventory-empty-row">Agrega un producto al traslado.</p></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </section>
                </template>
            </div>
        </template>
        <template #footer>
            <FgButton v-if="canConfigureTransfer" :loading="savingTransfer"
                :disabled="!transferForm.items.length || hasTransferQuantityOverStock" @click="saveTransfer">Guardar traslado</FgButton>
        </template>
    </FormModal>

    <FormModal v-if="canConfigureTransfer" modal-id="transferReasonsDialog" title="Motivos frecuentes" modal-class="fg-inventory-dialog"
        modal-size="modal-lg" :show-submit="false">
        <InventoryTable :columns="transferReasonColumns" min-width="420px" scroll-records>
            <tr v-for="option in transferReasonOptions" :key="option.label">
                <td><span>{{ option.label }}</span><span class="fg-table-note">{{ option.text }}</span></td>
                <td class="fg-align-center">
                    <a href="#transferReason" class="fg-link" :aria-label="`Seleccionar ${option.label}`" @click.prevent="applyTransferReason(option.text)">
                        <i class="fa-solid fa-hand-pointer" aria-hidden="true"></i> Seleccionar
                    </a>
                </td>
            </tr>
        </InventoryTable>
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
        :pdf-url="selectedTransfer ? transferPdfUrl(selectedTransfer) : null"
        :format-date="formatTransferDate"
        @retry="loadTransferDetail(transferDetailId)"
        @advance="changeTransferStatus(nextTransferStatus)"
        @cancel="changeTransferStatus('canceled')"
        @operate="saveTransferOperation"
        @view-cancellation-reason="viewTransferCancellationReason"/>


</template>

<script>
import WarehouseSelectionField from "@System/Components/Generics/WarehouseSelectionField.vue";
import * as Requests from "@System/Helpers/Requests.js";
import * as Alerts from "@System/Helpers/Alerts.js";
import {createOptionSearch} from "@/Shared/Options.js";
import TransferDetailModal from "./TransferDetailModal.vue";
import InventoryTable from "./InventoryTable.vue";
import inventoryPage from "./inventoryState.js";

export default {
    ...inventoryPage,
    components: {...inventoryPage.components, InventoryTable, TransferDetailModal, WarehouseSelectionField},
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
        transferReasonColumns: () => [{label: "Motivo"}, {label: "Acción", width: "130px", align: "center"}],
        transferRegistrationStatuses() {
            return this.options.transferStatuses.filter(status => status.can_register === true);
        },
        canManageSelectedTransfer() {
            const transfer = this.selectedTransfer;
            return Boolean(transfer && (transfer.can_dispatch || transfer.can_cancel || transfer.can_receive || transfer.can_resolve));
        },
        canCancelTransfer() {
            return this.selectedTransfer?.can_cancel === true;
        },
        nextTransferStatus() {
            return this.selectedTransfer?.can_dispatch ? this.transferStatusInfo(this.selectedTransfer.status).next || null : null;
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
        transferPdfUrl(transfer) {
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
                if(status === "in_transit" && !await Alerts.confirmAction({
                    title: "¿Confirmar el envío?",
                    text: "Se descontarán los productos del origen y quedarán en tránsito hasta confirmar su recepción.",
                    confirmButtonText: "Confirmar envío"
                })) return;

                const data = {status, expected_version: transfer.version};
                if(status === "canceled") {
                    const reason = await Alerts.confirmActionWithReason({
                        title: "¿Cancelar este traslado?",
                        text: "El traslado quedará registrado como cancelado.",
                        icon: "warning",
                        confirmButtonText: "Confirmar cancelación",
                        target: "#inventoryTransferDetailModal"
                    });
                    if(reason === null) return;

                    data.cancellation_reason = reason;
                }
                const result = await Requests.patch({route: `${this.transferUrl(transfer.id)}/status`, data});
                if(result?.bool) {
                    if(this.transferDetailId === transfer.id) this.selectedTransfer = result.data.data;
                    await this.listCurrentView();
                    Alerts.showMessage({type: "success", title: status === "canceled" ? "Traslado cancelado correctamente." : "Envío confirmado correctamente."});
                }else if(this.transferDetailId === transfer.id) {
                    this.transferStatusError = result?.data?.msg || "No se pudo actualizar el estado del traslado.";
                    Alerts.showMessage({type: "error", title: "No se pudo actualizar el traslado", text: this.transferStatusError});
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
        async saveTransferOperation({kind, data}) {
            const transfer = this.selectedTransfer;
            if(this.savingTransferStatus || !transfer || !(kind === "receipt" ? transfer.can_receive : transfer.can_resolve)) return;
            this.savingTransferStatus = true;
            this.transferStatusError = "";
            Alerts.loading({message: kind === "receipt" ? "Registrando recepción" : "Resolviendo diferencia"});
            let result;
            try {
                result = await Requests.post({route: `${this.transferUrl(transfer.id)}/${kind === "receipt" ? "receipts" : "resolutions"}`, data});
            } finally {
                this.savingTransferStatus = false;
                Alerts.close();
            }
            if(Requests.valid({result})) {
                if(this.transferDetailId === transfer.id) this.selectedTransfer = result.data.data;
                await this.listCurrentView();
                Alerts.showMessage({type: "success", title: result.data.msg});
            }else {
                this.transferStatusError = result?.data?.msg || result?.data?.message || "No se pudo registrar la operación. Revisa los datos e intenta nuevamente.";
                Alerts.showMessage({type: "error", title: "No se pudo guardar", text: this.transferStatusError});
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
