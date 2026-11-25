<template>
    <FgPageHeader :title="pageTitle" :description="pageDescription">
        <template v-if="activeMode === 'list'" #actions>
            <button type="button" class="fg-button" :disabled="initialLoading" @click="changeMode('new')">
                <i class="fa-solid fa-plus" aria-hidden="true"></i>
                <span>Nueva compra</span>
            </button>
            <button type="button" class="fg-button fg-button--secondary" :disabled="initialLoading || exporting" @click="exportPurchases">
                <i class="fa-solid fa-file-excel" aria-hidden="true"></i>
                <span>Descargar compras</span>
            </button>
        </template>
    </FgPageHeader>

    <InitialLoadTransition>
        <PageSkeleton v-if="initialLoading" :table="activeMode !== 'new'"/>
        <div v-else class="fg-page-content">
            <main class="fg-entity fg-purchases">
                <section v-if="activeMode === 'new'" class="fg-purchases__workspace">
                    <div class="fg-sale-create-grid">
                        <div>
                            <div class="fg-sales-panel">
                                <div class="fg-sales-panel__body">
                                    <h2 class="fg-section-title">Documento de compra</h2>
                                    <div class="row g-3 mb-4">
                                        <FgField class="col-lg-6 col-xl-3" label="Tipo de comprobante" required>
                                            <FgSelect v-model="purchaseForm.documentType" :options="documentTypes" :clearable="false" :searchable="false" placeholder="Seleccione"/>
                                        </FgField>
                                        <InputText v-model="purchaseForm.documentSeries" hasDiv title="Serie" :titleClass="[config.forms.classes.title]" maxlength="20" showCharCounter xl="3" lg="6"/>
                                        <InputText v-model="purchaseForm.documentNumber" hasDiv title="Nro. de comprobante" :titleClass="[config.forms.classes.title]" maxlength="50" showCharCounter xl="3" lg="6"/>
                                        <InputDate v-model="purchaseForm.issueDate" hasDiv title="Fecha de emisión" :titleClass="[config.forms.classes.title]" isRequired xl="3" lg="6"/>
                                        <FgField class="col-lg-4" label="Sucursal" required>
                                            <FgSelect v-model="purchaseForm.branch" :options="branches" :clearable="false" :searchable="false" placeholder="Seleccione"/>
                                        </FgField>
                                        <FgField class="col-lg-4" label="Almacén" required>
                                            <FgSelect v-model="purchaseForm.warehouse" :options="warehousesForBranch(purchaseForm.branch?.code)" :clearable="false" placeholder="Seleccione"/>
                                        </FgField>
                                        <FgField class="col-lg-8" label="Proveedor" required>
                                            <template #action>
                                                <AddSupplier triggerLabel="Agregar" @postAction="addSupplierPostAction"/>
                                            </template>
                                            <FgSelect v-model="purchaseForm.supplier" :options="suppliers" :clearable="false" placeholder="Seleccione"/>
                                        </FgField>
                                        <FgField class="col-lg-4" label="Recepción" required>
                                            <FgSelect v-model="purchaseForm.deliveryMode" :options="deliveryModes" :clearable="false" :searchable="false" placeholder="Seleccione"/>
                                        </FgField>
                                        <InputDate v-if="purchaseForm.deliveryMode?.code === 'pending'" v-model="purchaseForm.expectedDate" hasDiv title="Fecha esperada" :titleClass="[config.forms.classes.title]" xl="3" lg="6"/>
                                    </div>

                                    <h2 class="fg-section-title">Productos de la compra</h2>
                                    <div class="row g-3">
                                        <ResponsiveTable class="fg-sales-table-scroll">
                                            <table role="table" class="fg-table fg-record-table fg-record-table--editor">
                                                <thead role="rowgroup" class="text-center">
                                                    <tr role="row">
                                                        <th scope="col" role="columnheader" class="d-none d-md-table-cell" style="width: 8%;">#</th>
                                                        <th scope="col" role="columnheader" class="min-w-150px" style="width: 32%;">PRODUCTO</th>
                                                        <th scope="col" role="columnheader" class="min-w-150px" style="width: 20%;">CANTIDAD</th>
                                                        <th scope="col" role="columnheader" class="min-w-150px" style="width: 20%;">COSTO UNITARIO</th>
                                                        <th scope="col" role="columnheader" class="min-w-150px text-end pe-3" style="width: 15%;">TOTAL</th>
                                                        <th scope="col" role="columnheader" style="width: 5%;">ACCIONES</th>
                                                    </tr>
                                                </thead>
                                                <tbody role="rowgroup" class="table-border-bottom-0 bg-white">
                                                    <template v-if="purchaseForm.items?.length">
                                                        <tr role="row" v-for="(detail, index) in purchaseForm.items" :key="detail.key">
                                                            <td role="cell" class="text-center d-none d-md-table-cell">
                                                                <span>{{ index + 1 }}</span>
                                                            </td>
                                                            <td role="cell" class="text-start fg-record-table__identity">
                                                                <FgSelect v-model="detail.product" :options="purchaseProductOptions(index)" :aria-label="`Producto ${index + 1}`" :clearable="false" placeholder="Selecciona un producto"/>
                                                            </td>
                                                            <td role="cell" class="text-center">
                                                                <span class="fg-record-table__label" aria-hidden="true">Cantidad</span>
                                                                <div class="fg-record-table__value"><InputNumber v-model="detail.quantity" :aria-label="'Cantidad del producto ' + (index + 1)" :hasNegative="false"/></div>
                                                            </td>
                                                            <td role="cell" class="text-center">
                                                                <span class="fg-record-table__label" aria-hidden="true">Costo unitario</span>
                                                                <div class="fg-record-table__value">
                                                                    <InputNumber v-model="detail.unitCost" :aria-label="'Costo del producto ' + (index + 1)" :hasNegative="false">
                                                                        <template #inputGroupPrepend>
                                                                            <span class="input-group-text fg-currency-prefix">{{ purchaseForm.currency?.sign || "S/" }}</span>
                                                                        </template>
                                                                    </InputNumber>
                                                                </div>
                                                            </td>
                                                            <td role="cell" class="text-end pe-3">
                                                                <span class="fg-record-table__label" aria-hidden="true">Total</span>
                                                                <span class="fg-amount-inline fg-record-table__value">
                                                                    <span class="fg-amount-inline__sign">{{ purchaseForm.currency?.sign || "" }}</span>
                                                                    <span class="fg-amount-inline__amount">{{ separatorNumber(lineTotal(detail)) }}</span>
                                                                </span>
                                                            </td>
                                                            <td role="cell" class="text-center fg-record-table__actions">
                                                                <button type="button" class="fg-button fg-button--icon" :disabled="purchaseForm.items.length === 1" :aria-label="'Quitar producto ' + (index + 1)" @click="removePurchaseItem(index)">
                                                                    <i class="fa fa-times" aria-hidden="true"></i>
                                                                </button>
                                                            </td>
                                                        </tr>
                                                        <tr role="row" class="fg-table-footer-stripe d-none d-md-table-row">
                                                            <td role="cell" colspan="4" class="text-end fg-table-footer-stripe__label">
                                                                <span class="text-uppercase">Importe total:</span>
                                                            </td>
                                                            <td role="cell" class="text-end pe-3">
                                                                <span class="fg-amount-inline fg-amount-inline--emphasis">
                                                                    <span class="fg-amount-inline__sign">{{ purchaseForm.currency?.sign || "" }}</span>
                                                                    <span class="fg-amount-inline__amount">{{ separatorNumber(purchaseSubtotal) }}</span>
                                                                </span>
                                                            </td>
                                                            <td role="cell"></td>
                                                        </tr>
                                                    </template>
                                                    <tr role="row" class="fg-table__state" v-else>
                                                        <td role="cell" class="pt-1 pb-0 border-0" colspan="6">
                                                            <div class="fg-table-detail-empty">
                                                                <div class="fg-table-detail-empty__top">
                                                                    <div class="fg-table-detail-empty__body">
                                                                        <img class="fg-table-detail-empty__img" :src="purchaseDetailEmptyImageUrl" alt="" width="100" height="84" loading="lazy" decoding="async"/>
                                                                        <p class="fg-table-detail-empty__text">
                                                                            <span>No hay productos en el detalle. Agréguelos con la acción </span>
                                                                            <button type="button" class="fg-button fg-button--secondary" @click="addPurchaseItem">Agregar producto</button>
                                                                            <span>.</span>
                                                                        </p>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </ResponsiveTable>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div>
                            <aside class="fg-purchases__settlement">
                                <div class="fg-document-settlement fg-sale-settlement mb-2 mb-md-3">
                                    <div>
                                        <div class="fg-document-settlement__header">
                                            <label class="form-label">Observaciones</label>
                                            <button
                                                type="button"
                                                class="fg-button fg-button--compact fg-button--secondary fg-document-payment-config waves-effect"
                                                @click="openObservationsModal">
                                                <span>Cambiar</span>
                                            </button>
                                        </div>
                                        <div class="fg-document-observation-summary">
                                            <span
                                                v-if="observationHasContent"
                                                class="fg-document-observation-summary__value"
                                                :title="observationPreviewTooltip"
                                                v-text="observationDisplayPreview">
                                            </span>
                                            <p v-else class="fg-document-settlement__empty mb-0">
                                                Sin observaciones para esta compra.
                                            </p>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="form-label">Impuestos extras</label>
                                        <div class="fg-document-settlement__taxes" v-if="optionalPurchaseTaxes.length">
                                            <template v-for="tax in optionalPurchaseTaxes" :key="`optional-purchase-tax-page-${tax.code}`">
                                                <label class="fg-entity-switch fg-document-settlement__tax-option">
                                                    <input v-model="purchaseForm.selectedTaxes" class="form-check-input" type="checkbox" :value="tax.code" @change="syncSelectedTaxQuantity(tax.data)">
                                                    <span>
                                                        <strong>{{ tax.data.name }}</strong>
                                                        <small>{{ taxLabel(tax.data) }}</small>
                                                    </span>
                                                </label>
                                                <InputNumber v-if="isFixedTax(tax.data) && (purchaseForm.selectedTaxes || []).includes(tax.code)" v-model="purchaseForm.selectedTaxQuantities[tax.code]" title="" :inputClass="['form-control', 'fg-tax-quantity']" :decimals="0" :minValue="taxQuantityMinimum(tax.data)" :maxValue="taxQuantityMaximum(tax.data)" :hasNegative="false" @change="normalizeSelectedTaxQuantity(tax.code)">
                                                    <template #inputGroupPrepend>
                                                        <span class="input-group-text fg-tax-quantity__label">Veces</span>
                                                    </template>
                                                </InputNumber>
                                            </template>
                                        </div>
                                        <p v-else class="fg-document-settlement__empty mb-0">Sin impuestos extras para esta compra.</p>
                                    </div>
                                    <div>
                                        <label class="form-label">Métodos de pago</label>
                                        <div class="fg-document-payments">
                                            <div v-for="(payment, index) in purchaseForm.payments" :key="payment.key" class="fg-document-payment-row">
                                                <FgSelect
                                                    v-model="payment.method"
                                                    :options="purchasePaymentMethods"
                                                    :aria-label="`Método de pago ${index + 1}`"
                                                    :clearable="false"
                                                    @update:modelValue="syncPurchasePaymentVariant(payment)">
                                                    <template #selected-option="{ label, data }">
                                                        <span class="fg-payment-select-option">
                                                            <img v-if="paymentAssetUrl(data)" :src="paymentAssetUrl(data)" alt="" class="fg-payment-select-option__image">
                                                            <span>{{ label }}</span>
                                                        </span>
                                                    </template>
                                                    <template #option="{ label, data }">
                                                        <span class="fg-payment-select-option">
                                                            <img v-if="paymentAssetUrl(data)" :src="paymentAssetUrl(data)" alt="" class="fg-payment-select-option__image">
                                                            <span>{{ label }}</span>
                                                        </span>
                                                    </template>
                                                </FgSelect>
                                                <FgSelect
                                                    v-if="purchasePaymentVariantOptions(payment).length"
                                                    v-model="payment.variant"
                                                    :options="purchasePaymentVariantOptions(payment)"
                                                    :aria-label="`Variante de pago ${index + 1}`"
                                                    :clearable="false"
                                                    placeholder="Variante">
                                                    <template #selected-option="{ label, data }">
                                                        <span class="fg-payment-select-option">
                                                            <img v-if="paymentAssetUrl(data)" :src="paymentAssetUrl(data)" alt="" class="fg-payment-select-option__image">
                                                            <span>{{ label }}</span>
                                                        </span>
                                                    </template>
                                                    <template #option="{ label, data }">
                                                        <span class="fg-payment-select-option">
                                                            <img v-if="paymentAssetUrl(data)" :src="paymentAssetUrl(data)" alt="" class="fg-payment-select-option__image">
                                                            <span>{{ label }}</span>
                                                        </span>
                                                    </template>
                                                </FgSelect>
                                                <InputNumber v-model="payment.amount" title="" :titleClass="[]" :inputClass="['form-control', 'fg-document-payment-amount']" :minValue="0" :placeholder="separatorNumber(purchaseTotal)">
                                                    <template #inputGroupPrepend>
                                                        <span class="input-group-text fg-currency-prefix">{{ purchaseForm.currency?.sign }}</span>
                                                    </template>
                                                </InputNumber>
                                                <input v-if="paymentRequiresReference(payment)" v-model.trim="payment.reference" type="text" class="form-control" maxlength="100" placeholder="Referencia">
                                                <button type="button" class="fg-icon-action" :disabled="purchaseForm.payments.length <= 1" title="Quitar método" @click="removePurchasePayment(index)">
                                                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                                                </button>
                                            </div>
                                            <button type="button" class="fg-document-payment-add" @click="addPurchasePayment">
                                                <i class="fa-solid fa-circle-plus" aria-hidden="true"></i>
                                                <span>Agregar método</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="fg-document-summary-card mb-2 mb-md-3">
                                    <h3 class="fg-document-summary-card__title">Resumen</h3>
                                    <div class="fg-document-settlement__summary">
                                        <span>Subtotal</span>
                                        <strong>{{ purchaseForm.currency?.sign }} {{ separatorNumber(purchaseSubtotal) }}</strong>
                                        <template v-if="purchaseTaxBreakdown.length">
                                            <template v-for="tax in purchaseTaxBreakdown" :key="`purchase-summary-tax-page-${tax.id}`">
                                                <span>{{ tax.name }}</span>
                                                <strong>{{ purchaseForm.currency?.sign }} {{ separatorNumber(tax.amount) }}</strong>
                                            </template>
                                        </template>
                                        <span>Total</span>
                                        <strong>{{ purchaseForm.currency?.sign }} {{ separatorNumber(purchaseTotal) }}</strong>
                                        <span>Pagado</span>
                                        <strong>{{ purchaseForm.currency?.sign }} {{ separatorNumber(purchasePaidTotal) }}</strong>
                                        <span>Diferencia</span>
                                        <strong :class="Number(purchasePaymentDifference) === 0 ? 'text-success' : 'text-danger'">
                                            {{ purchaseForm.currency?.sign }} {{ separatorNumber(purchasePaymentDifference) }}
                                        </strong>
                                    </div>
                                </div>
                                <div class="fg-sale-sidebar-actions">
                                    <div class="fg-purchases-sidebar-actions">
                                        <button type="button" class="fg-button fg-button--small waves-effect" @click="addPurchaseItem">
                                            <span>Agregar producto</span>
                                        </button>
                                        <button type="button" class="fg-button fg-button--small fg-button--secondary waves-effect" @click="preparePurchase">
                                            <span>Limpiar</span>
                                        </button>
                                    </div>
                                    <div class="fg-purchases-sidebar-actions">
                                        <button type="button" class="fg-button fg-sale-sidebar-actions__cta" :disabled="saving" @click="savePurchase">
                                            <span v-text="saving ? 'Registrando' : 'Registrar compra'"></span>
                                        </button>
                                    </div>
                                </div>
                            </aside>
                        </div>
                    </div>
                </section>

                <div id="purchaseObservationsModal" class="modal fade fg-modal fg-modal--compact" data-bs-backdrop="static" tabindex="-1" aria-labelledby="purchase-observations-title">
                    <div class="modal-dialog modal-dialog-centered" role="document">
                        <div class="modal-content">
                            <div class="modal-header fg-modal__header">
                                <h2 id="purchase-observations-title" class="modal-title fg-modal__title">Cambiar observación</h2>
                                <button type="button" class="fg-modal-close" data-bs-dismiss="modal" aria-label="Cerrar">
                                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                                </button>
                            </div>
                            <div class="modal-body fg-modal__body">
                                <InputTextArea
                                    v-model="observationDraft"
                                    hasDiv
                                    title="Observaciones"
                                    :titleClass="[config.forms.classes.title]"
                                    rows="5"
                                    maxlength="1000"
                                    showCharCounter
                                    placeholder="Escribe aquí la observación de la compra..."/>
                            </div>
                            <div class="modal-footer fg-modal__footer">
                                <button type="button" class="fg-button fg-button--secondary" data-bs-dismiss="modal">Cerrar</button>
                                <button type="button" class="fg-button" @click="saveObservationsModal">Cambiar observación</button>
                            </div>
                        </div>
                    </div>
                </div>

                <FgResponsiveFilters
                    v-if="activeMode !== 'new'"
                    id="purchases-list-filters"
                    :label="activeMode === 'receipts' ? 'Filtros de ingresos pendientes' : 'Filtros de compras'"
                    :loading="loading"
                    @search="listPurchases({applyFilters: true})"
                    @clear="clearFilters">
                    <div class="fg-field-cell">
                        <SearchInput v-model.trim="filters.word" title="Búsqueda" placeholder="Proveedor, referencia o producto"/>
                    </div>
                    <FgField :label="activeMode === 'receipts' ? 'Estado del ingreso' : 'Estado'" input-id="purchase-filter-status">
                        <FgSelect v-model="filters.status" :options="statuses" :clearable="false" :searchable="false"/>
                    </FgField>
                </FgResponsiveFilters>

                <section v-if="['list', 'receipts'].includes(activeMode)" class="fg-entity-list">
                    <ResponsiveTable class="fg-sales-table-scroll">
                        <table role="table" class="fg-table fg-record-table mb-0">
                            <thead role="rowgroup" class="fg-table-header-surface">
                                <tr role="row">
                                    <th scope="col" role="columnheader">Documento</th>
                                    <th scope="col" role="columnheader">Proveedor</th>
                                    <th scope="col" role="columnheader">Almacén</th>
                                    <th scope="col" role="columnheader" class="text-end">Total</th>
                                    <th scope="col" role="columnheader">Recepción</th>
                                    <th scope="col" role="columnheader">Estado</th>
                                    <th scope="col" role="columnheader" class="text-center"><span class="visually-hidden">Acciones</span></th>
                                </tr>
                            </thead>
                            <tbody role="rowgroup">
                                <tr role="row" class="fg-table__state" v-if="loading">
                                    <td role="cell" colspan="7"><FgListLoading :label="activeMode === 'receipts' ? 'Buscando ingresos pendientes…' : 'Buscando compras…'"/></td>
                                </tr>
                                <tr role="row" v-else-if="listError" class="fg-table__state">
                                    <td role="cell" colspan="7">
                                        <FgEmptyState title="No se pudo completar la consulta" description="Vuelve a intentar con los filtros aplicados.">
                                            <button type="button" class="fg-button fg-button--secondary" @click="listPurchases()">Reintentar</button>
                                        </FgEmptyState>
                                    </td>
                                </tr>
                                <template v-else-if="records.data?.length">
                                    <tr role="row" v-for="purchase in records.data" :key="purchase.id">
                                        <td role="cell" class="fg-record-table__identity">
                                            <strong>{{ purchase.formatted_document_type }}</strong>
                                            <span class="fg-purchases__meta">
                                                {{ purchaseDocumentReference(purchase) }}
                                                · {{ formatDate(purchase.issue_date) }}
                                            </span>
                                        </td>
                                        <td role="cell">
                                            <span class="fg-record-table__label" aria-hidden="true">Proveedor</span>
                                            <div class="fg-record-table__value"><strong>{{ purchase.supplier?.name }}</strong><span class="fg-purchases__meta">{{ purchase.supplier?.document_number }}</span></div>
                                        </td>
                                        <td role="cell">
                                            <span class="fg-record-table__label" aria-hidden="true">Almacén</span>
                                            <div class="fg-record-table__value">{{ purchase.warehouse?.name }}</div>
                                        </td>
                                        <td role="cell" class="text-end">
                                            <span class="fg-record-table__label" aria-hidden="true">Total</span>
                                            <div class="fg-record-table__value"><strong class="fg-money__value">{{ purchase.currency?.sign }} {{ separatorNumber(purchase.total) }}</strong></div>
                                        </td>
                                        <td role="cell">
                                            <span class="fg-record-table__label" aria-hidden="true">Recepción</span>
                                            <div class="fg-record-table__value">
                                                <span class="fg-purchases__progress-label">{{ purchase.receipt_progress }}%</span>
                                                <div class="fg-purchases__progress" aria-hidden="true">
                                                    <span :style="{width: `${purchase.receipt_progress}%`}"></span>
                                                </div>
                                            </div>
                                        </td>
                                        <td role="cell" class="fg-record-table__state">
                                            <span :class="['fg-purchase-status', `is-${purchase.status}`]">
                                                {{ purchase.formatted_status }}
                                            </span>
                                        </td>
                                        <td role="cell" class="text-center fg-record-table__actions">
                                            <div class="fg-purchases__actions">
                                                <button
                                                    v-if="['confirmed', 'partial'].includes(purchase.status)"
                                                    type="button"
                                                    class="fg-icon-action fg-icon-action-edit"
                                                    title="Registrar recepción"
                                                    :aria-label="'Registrar recepción de ' + purchaseDocumentReference(purchase)"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#purchaseReceiptModal"
                                                    @click="prepareReceipt(purchase.id)">
                                                    <i class="fa-solid fa-box-open" aria-hidden="true"></i>
                                                </button>
                                                <button
                                                    v-if="purchase.status === 'confirmed'"
                                                    type="button"
                                                    class="fg-icon-action fg-purchases__cancel"
                                                    title="Anular compra"
                                                    :aria-label="'Anular compra ' + purchaseDocumentReference(purchase)"
                                                    @click="cancelPurchase(purchase)">
                                                    <i class="fa-solid fa-ban" aria-hidden="true"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                                <tr role="row" class="fg-table__state" v-else>
                                    <td role="cell" colspan="7"><FgEmptyState :title="emptyTitle" :description="emptyDescription"/></td>
                                </tr>
                            </tbody>
                        </table>
                    </ResponsiveTable>
                </section>

                <Paginator v-if="activeMode !== 'new' && !listError" :links="records.links" :pagination="records" :loading="loading" @clickPage="listPurchases"/>
            </main>

        </div>
    </InitialLoadTransition>
    <div id="purchaseReceiptModal" class="modal fade fg-modal" aria-labelledby="purchase-receipt-title" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header fg-modal__header">
                    <h2 id="purchase-receipt-title" class="modal-title fg-modal__title">Recibir mercadería</h2>
                    <button type="button" class="fg-modal-close" data-bs-dismiss="modal" aria-label="Cerrar">
                        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                    </button>
                </div>
                <div class="modal-body fg-modal__body">
                    <FgListLoading v-if="loadingReceipt" label="Cargando los productos pendientes…"/>
                    <FgEmptyState v-else-if="receiptError" title="No se pudo consultar la compra" description="Vuelve a intentar antes de registrar su recepción.">
                        <button type="button" class="fg-button fg-button--secondary" @click="prepareReceipt(receiptPurchaseId)">Reintentar</button>
                    </FgEmptyState>
                    <FgEmptyState v-else-if="!receiptForm.items.length" title="Esta compra no tiene productos pendientes" description="Actualiza el listado para revisar el estado de la recepción."/>
                    <template v-else>
                        <p class="fg-purchases__modal-help mb-3">Solo las cantidades recibidas ingresarán al inventario.</p>
                        <div class="fg-purchases__receipt-summary">
                            <strong>{{ receiptForm.purchase?.supplier?.name }}</strong>
                            <span>{{ receiptForm.purchase?.warehouse?.name }}</span>
                        </div>
                        <div class="fg-purchases__receipt-items">
                            <div v-for="detail in receiptForm.items" :key="detail.purchaseItemId" class="fg-purchases__receipt-row">
                                <div>
                                    <ProductIdentity :product="detail.product"/>
                                    <span class="fg-purchases__receipt-pending">Pendiente: {{ separatorNumber(detail.remaining) }}</span>
                                </div>
                                <InputNumber
                                    v-model="detail.quantity"
                                    title="Cantidad recibida"
                                    :aria-label="'Cantidad recibida de ' + detail.product.name"
                                    :hasNegative="false"
                                    :hasTextBottom="receiptQuantityInvalid(detail)"
                                    :textBottomInfo="['Ingresa una cantidad entre 0 y ' + separatorNumber(detail.remaining) + '.']"/>
                            </div>
                        </div>
                        <p v-if="receivedQuantity > 0 && !hasInvalidReceiptQuantity" class="fg-sales-message" role="status">
                            Quedará pendiente: {{ separatorNumber(remainingReceiptQuantity) }}.
                        </p>
                        <div class="row g-3 mt-1">
                            <div class="col-md-5">
                                <label for="purchase-receipt-date" class="form-label">Fecha y hora de recepción</label>
                                <input id="purchase-receipt-date" v-model="receiptForm.receivedAt" type="datetime-local" class="form-control">
                            </div>
                            <div class="col-md-7">
                                <label for="purchase-receipt-observation" class="form-label">Observación</label>
                                <textarea id="purchase-receipt-observation" v-model.trim="receiptForm.observation" class="form-control" rows="2"></textarea>
                            </div>
                        </div>
                    </template>
                </div>
                <div class="modal-footer fg-modal__footer">
                    <button ref="closeReceiptModal" type="button" class="fg-button fg-button--secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="button" class="fg-button" :disabled="!canSaveReceipt" @click="saveReceipt">Confirmar recepción</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import FgPageHeader from "@System/Components/Figma/FgPageHeader.vue";
import FgResponsiveFilters from "@System/Components/Figma/FgResponsiveFilters.vue";
import FgListLoading from "@System/Components/Figma/FgListLoading.vue";
import FgEmptyState from "@System/Components/Figma/FgEmptyState.vue";
import ResponsiveTable from "@/Shared/ResponsiveTable.vue";
import {navbarItem} from "@System/Layout/Menu.js";
import InitialPageMixin from "@System/Helpers/InitialPageMixin.js";
import * as Alerts from "@System/Helpers/Alerts.js";
import * as Constants from "@System/Helpers/Constants.js";
import * as Requests from "@System/Helpers/Requests.js";
import * as Utils from "@System/Helpers/Utils.js";
import ProductIdentity from "@System/Components/Generics/ProductIdentity.vue";
import SearchInput from "@System/Components/Generics/SearchInput.vue";
import FgField from "@System/Components/Figma/FgField.vue";
import FgSelect from "@System/Components/Figma/FgSelect.vue";

export default {
    mixins: [InitialPageMixin],
    components: {FgPageHeader, FgResponsiveFilters, FgListLoading, FgEmptyState, ResponsiveTable, ProductIdentity, SearchInput, FgField, FgSelect},
    async mounted() {
        navbarItem("menu-parent-purchases", {addClass: "open"});
        navbarItem({
            new: "menu-purchases-new",
            receipts: "menu-purchases-receipts",
            list: "menu-purchases-list"
        }[this.activeMode], {});

        await this.loadInitialParams(async () => {
            await this.initParams();
            if(this.activeMode === "new") this.preparePurchase();
        });
        if(this.activeMode !== "new") await this.listPurchases();
    },
    data() {
        return {
            activeMode: this.initialModeFromPath(),
            loading: false,
            listError: false,
            saving: false,
            loadingReceipt: false,
            receiptError: false,
            receiptRequest: 0,
            receiptPurchaseId: null,
            savingReceipt: false,
            exporting: false,
            records: {total: 0, data: []},
            appliedFilters: null,
            filters: {
                word: "",
                status: {code: "", label: "Todos los estados"}
            },
            options: {
                branches: [],
                suppliers: [],
                warehouses: [],
                currencies: [],
                products: [],
                taxes: [],
                paymentMethods: [],
                purchaseDocumentTypes: [],
                purchaseDeliveryModes: []
            },
            deliveryModes: [
                {
                    code: "immediate",
                    label: "Recepción inmediata"
                },
                {
                    code: "pending",
                    label: "Recepción pendiente"
                }
            ],
            purchaseForm: {},
            observationDraft: "",
            observationPreviewExpanded: false,
            receiptForm: {purchase: null, items: [], receivedAt: "", observation: ""},
            config: {
                ...Constants.generalConfig,
                entity: {
                    ...Requests.config({entity: "purchases"}),
                    page: {title: "Compras", active: true, menu: {id: "menu-purchases-list"}}
                }
            }
        };
    },
    methods: {
        initialModeFromPath() {
            const segment = window.location.pathname.split("?")[0].split("/").filter(Boolean).pop();

            if(["new", "create"].includes(segment)) return "new";
            if(segment === "pending-receipts") return "receipts";

            return "list";
        },
        changeMode(mode) {
            this.activeMode = mode;
            this.updateUrlMode(mode);
            if(mode === "new") this.preparePurchase();
        },
        taxLabel(tax = {}) {

            const name = tax?.name || "IGV";
            const rate = Number(tax?.rate || 0);
            const calculationType = tax?.calculation_type || "percentage";
            const operationType = tax?.operation_type || "addition";
            const sign = operationType === "subtraction" ? "-" : "+";

            if(calculationType === "fixed") {

                return `${name} ${sign} ${this.separatorNumber(rate)}`;

            }

            return `${name} ${sign}${this.separatorNumber(rate)}%`;

        },
        calculateTaxAmount(tax = {}, baseAmount = 0) {
            const quantity = this.isFixedTax(tax) ? this.selectedTaxQuantity(tax.id) : 1;

            return Utils.taxAmount(baseAmount, tax, quantity);
        },
        taxIsRequired(tax = {}) {

            return [true, 1, "1", "true"].includes(tax?.is_required);

        },
        isFixedTax(tax = {}) {

            return (tax?.calculation_type || "percentage") === "fixed";

        },
        selectedTaxQuantity(taxId) {

            return Math.max(1, parseInt(Number(this.purchaseForm.selectedTaxQuantities?.[taxId] || 1), 10));

        },
        normalizeSelectedTaxQuantity(taxId) {

            this.purchaseForm.selectedTaxQuantities[taxId] = Math.max(1, parseInt(Number(this.purchaseForm.selectedTaxQuantities[taxId] || 1), 10));

        },
        syncSelectedTaxQuantity(tax = {}) {

            if(!this.isFixedTax(tax)) return;

            this.purchaseForm.selectedTaxQuantities[tax.id] = (this.purchaseForm.selectedTaxes || []).includes(tax.id) ? 1 : 0;

        },
        async initParams() {
            const result = await Requests.get({
                route: this.config.entity.routes.initParams,
                data: {page: "main"},
                showAlert: true
            });
            const config = result.data?.config || {};
            this.options.branches = config.branches?.records || [];
            this.options.suppliers = config.suppliers?.records || [];
            this.options.warehouses = config.warehouses?.records || [];
            this.options.currencies = config.currencies?.records || [];
            this.options.products = config.products?.records || [];
            this.options.taxes = config.taxes || {records: []};
            this.options.paymentMethods = config.paymentMethods || {records: []};
            this.options.purchaseDocumentTypes = config.purchaseDocumentTypes?.records || [];
            this.options.purchaseDeliveryModes = config.purchaseDeliveryModes?.records || [];
            if(this.options.purchaseDeliveryModes.length) {
                this.deliveryModes = this.options.purchaseDeliveryModes;
            }
        },
        clearFilters() {
            this.filters = {word: "", status: this.statuses[0]};
            return Alerts.showFiltersCleared();
        },
        async listPurchases({url = null, applyFilters = false} = {}) {
            if(this.loading) return;
            this.listError = false;
            if(applyFilters || !this.appliedFilters) this.appliedFilters = {...this.filters};
            this.loading = true;

            try {
                this.records = Requests.emptyPagination();
                const result = await Requests.get({
                    route: url || this.config.entity.routes.list,
                    data: {
                        word: this.appliedFilters.word,
                        status: this.activeMode === "receipts"
                            ? (this.appliedFilters.status?.code || "pending_receipt")
                            : (this.appliedFilters.status?.code || "")
                    }
                });
                this.listError = !result.bool;
                if(!this.listError) this.records = result.data || Requests.emptyPagination();
            } finally {
                this.loading = false;
            }
        },
        newPurchaseItem() {
            return {key: `${Date.now()}-${Math.random()}`, product: null, quantity: "", unitCost: ""};
        },
        newPurchasePayment({amount = ""} = {}) {

            const payment = {
                key: `${Date.now()}-${Math.random()}`,
                method: this.purchasePaymentMethods.find(method => method.data?.is_default) || this.purchasePaymentMethods[0] || null,
                variant: null,
                amount,
                reference: "",
                note: ""
            };

            this.syncPurchasePaymentVariant(payment);

            return payment;

        },
        paymentAssetUrl(record) {

            const path = record?.image_path || record?.data?.image_path;

            if(!path) return "";

            if(/^https?:\/\//i.test(path) || path.startsWith("/")) return path;

            return `/${path}`;

        },
        purchasePaymentVariantOptions(payment) {

            const method = payment?.method?.data || {};
            const variants = method.supports_variants ? (method.variants || []) : [];

            return variants
                .filter(variant => variant.status !== "inactive")
                .map(variant => ({
                    code: variant.id,
                    label: variant.name,
                    data: variant
                }));

        },
        syncPurchasePaymentVariant(payment) {

            if(!payment) return;

            const options = this.purchasePaymentVariantOptions(payment);

            if(options.length === 0) {
                payment.variant = null;
                return;
            }

            const current = options.find(option => Number(option.code) === Number(payment.variant?.code));
            payment.variant = current || options.find(option => option.data?.is_default) || options[0];

        },
        paymentRequiresReference(payment) {

            return Boolean(payment?.variant?.data?.requires_reference ?? payment?.method?.data?.requires_reference);

        },
        addPurchasePayment() {
            const pending = this.purchasePaymentDifference > 0 ? this.purchasePaymentDifference : "";
            this.purchaseForm.payments.push(this.newPurchasePayment({amount: pending}));
        },
        removePurchasePayment(index) {
            if(this.purchaseForm.payments.length <= 1) return;
            this.purchaseForm.payments.splice(index, 1);
        },
        openObservationsModal() {
            this.observationDraft = this.purchaseForm.observation == null ? "" : String(this.purchaseForm.observation);
            Alerts.modals({type: "show", id: "purchaseObservationsModal"});
        },
        toggleObservationPreviewExpand() {
            this.observationPreviewExpanded = !this.observationPreviewExpanded;
        },
        saveObservationsModal() {
            this.purchaseForm.observation = this.observationDraft == null ? "" : String(this.observationDraft);
            Alerts.modals({type: "hide", id: "purchaseObservationsModal"});
        },
        preparePurchase() {
            const defaultBranch = this.branches[0] || null;
            const defaultWarehouse = this.warehousesForBranch(defaultBranch?.code)[0] || this.warehouses[0] || null;

            this.purchaseForm = {
                supplier: this.suppliers[0] || null,
                branch: defaultBranch,
                warehouse: defaultWarehouse,
                currency: this.currencies[0] || null,
                documentType: this.documentTypes[0],
                documentSeries: "",
                documentNumber: "",
                issueDate: new Date().toISOString().slice(0, 10),
                expectedDate: "",
                deliveryMode: this.deliveryModes[0],
                selectedTaxes: [],
                selectedTaxQuantities: {},
                payments: [this.newPurchasePayment({amount: 0})],
                observation: "",
                items: [this.newPurchaseItem()]
            };
        },
        addPurchaseItem() {
            if(this.purchaseForm.items.length < 100) this.purchaseForm.items.push(this.newPurchaseItem());
        },
        removePurchaseItem(index) {
            if(this.purchaseForm.items.length > 1) this.purchaseForm.items.splice(index, 1);
        },
        purchaseProductOptions(index) {
            const selected = this.purchaseForm.items
                .filter((_, itemIndex) => itemIndex !== index)
                .map(item => Number(item.product?.code))
                .filter(Boolean);
            return this.products.filter(product => !selected.includes(Number(product.code)));
        },
        warehousesForBranch(branchId) {
            if(!branchId) return this.warehouses;

            return this.warehouses.filter(warehouse => Number(warehouse.branchId) === Number(branchId));
        },
        lineTotal(detail) {
            return Utils.lineTotal(detail.quantity, detail.unitCost);
        },
        taxQuantityMinimum(tax = {}) {
            return Math.max(1, Number(tax?.min_apply_quantity ?? 1));
        },
        taxQuantityMaximum(tax = {}) {
            return tax?.max_apply_quantity == null
                ? undefined
                : Math.max(this.taxQuantityMinimum(tax), Number(tax.max_apply_quantity));
        },
        addSupplierPostAction({response}) {
            if(!Requests.valid({result: response})) return;

            const supplier = response?.data?.data;
            if(!supplier?.id) return;

            const exists = this.options.suppliers.some(record => Number(record.id) === Number(supplier.id));
            if(!exists) {
                this.options.suppliers.push(supplier);
                this.options.suppliers.sort((a, b) => `${a.name}`.localeCompare(`${b.name}`));
            }

            this.purchaseForm.supplier = this.suppliers.find(record => Number(record.code) === Number(supplier.id)) || this.purchaseForm.supplier;
        },
        async savePurchase() {
            if(this.saving) return;
            this.saving = true;
            Alerts.swals({type: "loading", message: "Registrando compra"});
            const result = await Requests.post({
                route: this.config.entity.routes.store,
                data: {
                    supplier_id: this.purchaseForm.supplier?.code,
                    warehouse_id: this.purchaseForm.warehouse?.code,
                    currency_id: this.purchaseForm.currency?.code,
                    document_type: this.purchaseForm.documentType?.code,
                    document_series: this.purchaseForm.documentSeries || null,
                    document_number: this.purchaseForm.documentNumber,
                    issue_date: this.purchaseForm.issueDate,
                    expected_date: this.purchaseForm.expectedDate || null,
                    delivery_mode: this.purchaseForm.deliveryMode?.code || "immediate",
                    tax: this.purchaseTaxTotal,
                    taxes: this.purchaseTaxBreakdown.map(tax => ({
                        tax_id: tax.id,
                        quantity: tax.quantity,
                        amount: tax.amount,
                        base_amount: this.purchaseSubtotal,
                        is_required: tax.isRequired
                    })),
                    payments: this.purchasePaymentPayload,
                    observation: this.purchaseForm.observation,
                    items: this.purchaseForm.items.map(item => ({
                        item_id: item.product?.code,
                        quantity: item.quantity,
                        unit_cost: item.unitCost
                    }))
                }
            });
            this.saving = false;
            Alerts.swals({show: false});
            if(Requests.valid({result})) {
                this.activeMode = "list";
                this.updateUrlMode("list");
                Alerts.generateAlert({type: "success", msgContent: result.data.msg});
                await this.listPurchases({});
                return;
            }
            Alerts.generateAlert({type: "error", messages: this.responseErrors(result)});
        },
        async prepareReceipt(id) {
            const request = ++this.receiptRequest;
            this.receiptPurchaseId = id;
            this.receiptForm = {purchase: null, items: [], receivedAt: "", observation: ""};
            this.receiptError = false;
            this.loadingReceipt = true;
            const result = await Requests.get({route: `${this.config.entity.routes.consult}/${id}`});
            if(request !== this.receiptRequest) return;
            this.loadingReceipt = false;
            this.receiptError = !result.bool;
            if(this.receiptError) return;

            const purchase = result.data;
            this.receiptForm = {
                purchase,
                receivedAt: new Date().toISOString().slice(0, 16),
                observation: "",
                items: (purchase.items || [])
                    .filter(item => Number(item.remaining_quantity) > 0)
                    .map(item => ({
                        purchaseItemId: item.id,
                        product: {...item.item, name: item.name || item.item?.name},
                        remaining: Number(item.remaining_quantity),
                        quantity: Number(item.remaining_quantity)
                    }))
            };
        },
        receiptQuantityInvalid(item) {
            return Utils.compareNumbers(item.quantity, 0) < 0 || Utils.compareNumbers(item.quantity, item.remaining) > 0;
        },
        async saveReceipt() {
            if(!this.canSaveReceipt) return;
            this.savingReceipt = true;
            Alerts.swals({type: "loading", message: "Registrando recepción"});
            const result = await Requests.post({
                route: `${this.config.entity.routes.consult}/${this.receiptForm.purchase.id}/receive`,
                data: {
                    received_at: this.receiptForm.receivedAt,
                    observation: this.receiptForm.observation,
                    items: this.receiptForm.items
                        .filter(item => Number(item.quantity) > 0)
                        .map(item => ({
                            purchase_item_id: item.purchaseItemId,
                            quantity: item.quantity
                        }))
                }
            });
            this.savingReceipt = false;
            Alerts.swals({show: false});
            if(Requests.valid({result})) {
                this.$refs.closeReceiptModal?.click();
                Alerts.generateAlert({type: "success", msgContent: result.data.msg});
                await this.listPurchases({});
                return;
            }
            Alerts.generateAlert({type: "error", messages: this.responseErrors(result)});
        },
        async cancelPurchase(purchase) {
            const accepted = await Alerts.generateAlert({
                type: "question",
                msgContent: `¿Deseas anular ${purchase.formatted_document_type.toLowerCase()}?`
            });
            if(!accepted?.isConfirmed) return;
            Alerts.swals({type: "loading", message: "Anulando compra"});
            const result = await Requests.patch({
                route: this.config.entity.routes.consult,
                id: `${purchase.id}/cancel`,
                data: {}
            });
            Alerts.swals({show: false});
            if(Requests.valid({result})) {
                Alerts.generateAlert({type: "success", msgContent: result.data.msg});
                await this.listPurchases({});
                return;
            }
            Alerts.generateAlert({type: "error", msgContent: result.data?.msg});
        },
        async exportPurchases() {
            if(this.exporting) return;
            this.exporting = true;
            Alerts.swals({type: "loading", message: "Preparando reporte"});
            const result = await Requests.download({
                route: this.config.entity.routes.export,
                data: {word: this.appliedFilters.word, status: this.appliedFilters.status?.code || ""},
                fileName: "compras.xlsx"
            });
            Alerts.swals({show: false});
            this.exporting = false;

            if(!result.bool) {
                Alerts.generateAlert({
                    type: "error",
                    msgContent: result.data?.msg || "No se pudo descargar el reporte."
                });
            }
        },
        responseErrors(result) {
            const errors = Object.values(result?.errors || result?.data?.errors || {}).flat();
            return errors.length ? errors : [result?.data?.msg || "No se pudo completar la operación."];
        },
        formatDate(value) {
            if(!value) return "";
            return new Intl.DateTimeFormat("es-PE").format(new Date(`${value}T00:00:00`));
        },
        purchaseDocumentReference(purchase) {
            const number = purchase?.document_number || "";
            const series = purchase?.document_series || "";

            if(series && number) return `${series}-${number}`;
            if(number) return number;

            return purchase?.reference || `Registro #${purchase?.id}`;
        },
        separatorNumber(value) {
            return Utils.separatorNumber(value || 0);
        },
        updateUrlMode(mode) {
            const path = mode === "new" ? "/purchases/create" : "/purchases";
            window.history.replaceState({}, "", path);
        }
    },
    computed: {
        pageTitle() {
            return {new: "Nueva compra", list: "Compras registradas", receipts: "Ingresos pendientes"}[this.activeMode];
        },
        pageDescription() {
            return {
                new: "Registra el comprobante y revisa los productos, importes y recepción antes de guardar.",
                list: "Consulta los comprobantes de compra y su estado de recepción.",
                receipts: "Revisa lo que falta por recibir y registra la mercadería que ingresó."
            }[this.activeMode];
        },
        hasAppliedFilters() {
            return Boolean(this.appliedFilters?.word?.trim() || this.appliedFilters?.status?.code);
        },
        emptyTitle() {
            if(this.hasAppliedFilters) return "No encontramos compras con estos filtros";
            return this.activeMode === "receipts" ? "No hay ingresos pendientes" : "Aún no hay compras registradas";
        },
        emptyDescription() {
            if(this.hasAppliedFilters) return "Revisa los filtros e intenta otra búsqueda.";
            return this.activeMode === "receipts" ? "Las compras por recibir aparecerán aquí." : "Usa Nueva compra para registrar el primer comprobante.";
        },
        receivedQuantity() {
            return Utils.sum(this.receiptForm.items.map(item => item.quantity || 0));
        },
        remainingReceiptQuantity() {
            return Utils.subtract(Utils.sum(this.receiptForm.items.map(item => item.remaining)), this.receivedQuantity);
        },
        hasInvalidReceiptQuantity() {
            return this.receiptForm.items.some(this.receiptQuantityInvalid);
        },
        canSaveReceipt() {
            return !this.loadingReceipt && !this.savingReceipt && !this.receiptError && Boolean(this.receiptForm.purchase)
                && !this.hasInvalidReceiptQuantity && Utils.compareNumbers(this.receivedQuantity, 0) > 0;
        },
        statuses() {
            if(this.activeMode === "receipts") {
                return [
                    {code: "", label: "Todos los ingresos pendientes"},
                    {code: "confirmed", label: "Pendiente de recepción"},
                    {code: "partial", label: "Recepción parcial"}
                ];
            }

            return [
                {code: "", label: "Todos los estados"},
                {code: "confirmed", label: "Pendiente de recepción"},
                {code: "partial", label: "Recepción parcial"},
                {code: "received", label: "Recibida"},
                {code: "canceled", label: "Anulada"}
            ];
        },
        documentTypes() {
            return this.options.purchaseDocumentTypes.length ? this.options.purchaseDocumentTypes : [
                {code: "order", label: "BOLETA"},
                {code: "invoice", label: "FACTURA"}
            ];
        },
        deliveryModeDescription() {
            return this.purchaseForm.deliveryMode?.code === "immediate"
                ? "La mercadería ingresa al inventario al registrar la compra."
                : "La compra queda pendiente hasta registrar la recepción.";
        },
        branches() {
            return this.options.branches.map(record => ({
                code: record.id,
                label: record.name,
                data: record
            }));
        },
        suppliers() {
            return this.options.suppliers.map(record => ({code: record.id, label: record.name}));
        },
        warehouses() {
            return this.options.warehouses.map(record => ({
                code: record.id,
                branchId: record.branch?.id || record.branch_id || null,
                label: record.name,
                data: record
            }));
        },
        currencies() {
            return this.options.currencies.map(record => ({
                code: record.id,
                sign: record.sign,
                label: `${record.code} - ${record.sign}`
            }));
        },
        products() {
            return this.options.products.map(record => ({
                code: record.id,
                label: [record.name, record.internal_code, record.barcode].filter(Boolean).join(" ? "),
                data: record
            }));
        },
        purchaseDetailEmptyImageUrl() {
            return "/System/assets/img/utils/without_data/empty_sale_detail.svg";
        },
        observationFullText() {
            const raw = this.purchaseForm.observation;

            if(raw == null) return "";

            return String(raw).trim();
        },
        observationHasContent() {
            return this.observationFullText.length > 0;
        },
        observationPreviewCharLimit() {
            return 400;
        },
        observationIsTruncatable() {
            return this.observationFullText.length > this.observationPreviewCharLimit;
        },
        observationDisplayPreview() {
            const full = this.observationFullText;

            if(!full) return "";
            if(this.observationPreviewExpanded || !this.observationIsTruncatable) return full;

            return `${full.slice(0, this.observationPreviewCharLimit)}...`;
        },
        observationPreviewTooltip() {
            if(!this.observationHasContent || this.observationPreviewExpanded || !this.observationIsTruncatable) return "";

            return this.observationFullText;
        },
        observationsFieldAriaLabel() {
            return `Observaciones. ${this.observationHasContent ? "Cambiar observación" : "Agregar observación"}`;
        },
        purchaseSubtotal() {
            return Utils.sum((this.purchaseForm.items || []).map(detail => this.lineTotal(detail)));
        },
        purchaseTaxTotal() {
            return Utils.sum((this.purchaseTaxBreakdown || []).map(tax => tax.amount));
        },
        purchaseTaxBreakdown() {
            const subtotal = Number(this.purchaseSubtotal || 0);

            return (this.appliedPurchaseTaxes || []).map(tax => {
                const data = tax.data || {};

                return {
                    id: data.id || tax.code,
                    name: data.name || "IGV",
                    isRequired: this.taxIsRequired(data),
                    quantity: this.isFixedTax(data) ? this.selectedTaxQuantity(data.id || tax.code) : 1,
                    amount: this.calculateTaxAmount(data, subtotal)
                };
            });
        },
        purchaseTotal() {
            return Utils.sum([this.purchaseSubtotal, this.purchaseTaxTotal]);
        },
        purchaseTaxes() {
            return (this.options.taxes?.records || []).map(tax => ({
                code: tax.id,
                label: this.taxLabel(tax),
                data: tax
            }));
        },
        requiredPurchaseTaxes() {
            return this.purchaseTaxes.filter(tax => this.taxIsRequired(tax.data));
        },
        optionalPurchaseTaxes() {
            return this.purchaseTaxes.filter(tax => !this.taxIsRequired(tax.data));
        },
        appliedPurchaseTaxes() {
            const selected = this.purchaseForm.selectedTaxes || [];

            return this.purchaseTaxes.filter(tax => this.taxIsRequired(tax.data) || selected.includes(tax.code));
        },
        purchasePaymentMethods() {
            return (this.options.paymentMethods?.records || []).map(method => ({
                code: method.id,
                label: method.name,
                data: method
            }));
        },
        purchasePaymentPayload() {
            const selected = this.purchaseForm.payments || [];

            if(selected.length === 0) return [];

            return selected
                .filter(payment => payment.method?.code)
                .map(payment => ({
                    payment_method_id: payment.method.code,
                    payment_method_variant_id: payment.variant?.code || null,
                    name: payment.variant?.label || payment.method?.label || null,
                    amount: Number(payment.amount || 0),
                    reference: payment.reference || null,
                    note: payment.note || null
                }));
        },
        purchasePaidTotal() {
            return Utils.sum((this.purchaseForm.payments || []).map(payment => payment.amount));
        },
        purchasePaymentDifference() {
            return Utils.subtract(this.purchaseTotal, this.purchasePaidTotal);
        }
    },
    watch: {
        "purchaseForm.branch": function(branch) {
            const warehouses = this.warehousesForBranch(branch?.code);
            const currentWarehouseIsValid = warehouses.some(warehouse => Number(warehouse.code) === Number(this.purchaseForm.warehouse?.code));

            if(!currentWarehouseIsValid) {
                this.purchaseForm.warehouse = warehouses[0] || null;
            }
        },
        "purchaseForm.observation": function() {
            this.observationPreviewExpanded = false;
        },
        purchaseTotal(value) {
            const payments = this.purchaseForm.payments || [];

            if(payments.length === 1) {
                payments[0].amount = Number(value || 0);
            }
        }
    }
};
</script>
