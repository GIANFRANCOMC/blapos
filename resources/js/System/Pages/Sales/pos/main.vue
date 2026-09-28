<template>
    <Breadcrumb :list="breadcrumbTitles"/>

    <section class="br-pos">
        <main class="br-pos__catalog">

            <Loader v-if="loading"/>
            <template v-else>
                <nav class="br-pos-categories" aria-label="Categorías">
                    <button
                        type="button"
                        class="br-pos-category"
                        :class="{'is-active': selectedCategoryId === null}"
                        @click="selectedCategoryId = null">
                        <span>
                            <strong>Todos</strong>
                        </span>
                        <small class="br-pos-category__count">{{ items.length }}</small>
                    </button>
                    <button
                        v-for="category in visibleCategories"
                        :key="category.id"
                        type="button"
                        class="br-pos-category"
                        :class="{'is-active': selectedCategoryId === category.id}"
                        @click="selectedCategoryId = category.id">
                        <span>
                            <strong>{{ category.name }}</strong>
                        </span>
                        <small class="br-pos-category__count">{{ countByCategory(category.id) }}</small>
                    </button>
                </nav>
                <div class="br-pos-search">
                    <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                    <input
                        v-model.trim="search"
                        type="search"
                        class="form-control"
                        placeholder="Buscar producto, código o marca"
                        aria-label="Buscar producto">
                </div>

                <section class="br-pos-products" aria-label="Productos">
                    <article
                        v-for="item in filteredItems"
                        :key="item.id"
                        class="br-pos-product"
                        :style="itemCardStyle(item)">
                        <div class="br-pos-product__body">
                            <span class="br-pos-product__image" :style="itemImageStyle(item)" aria-hidden="true">
                                <i :class="itemIcon(item)"></i>
                            </span>
                            <span class="br-pos-product__info">
                                <strong>{{ item.name }}</strong>
                                <small v-if="item.description" class="br-pos-product__description">
                                    {{ item.description || 'Producto disponible' }}
                                </small>
                                <span class="br-pos-product__meta">
                                    <span v-if="item.brand?.name" class="br-pos-product__meta-row is-brand">
                                        <b>Marca</b>
                                        <em>{{ item.brand.name }}</em>
                                    </span>
                                    <span v-if="item.internal_code" class="br-pos-product__meta-row is-internal">
                                        <b>Int.</b>
                                        <em>{{ item.internal_code }}</em>
                                    </span>
                                    <span v-if="item.barcode" class="br-pos-product__meta-row is-barcode">
                                        <b>Bar.</b>
                                        <em>{{ item.barcode }}</em>
                                    </span>
                                </span>
                            </span>
                        </div>
                        <footer class="br-pos-product__footer">
                            <strong>{{ currencySign(item) }} {{ separatorNumber(item.price) }}</strong>
                            <div class="br-pos-product__actions">
                                <button
                                    type="button"
                                    class="br-pos-detail"
                                    data-bs-toggle="tooltip"
                                    title="Ver detalle"
                                    @click="openItemDetail(item)"
                                    aria-label="Ver detalle del catálogo">
                                    <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                                </button>
                                <button
                                    v-if="quantityInCart(item.id)"
                                    type="button"
                                    class="br-pos-remove br-btn-danger"
                                    @click="decreaseItem(item.id)"
                                    aria-label="Quitar del detalle">
                                    <i class="fa-solid fa-minus" aria-hidden="true"></i>
                                </button>
                                <button type="button" class="br-pos-add" @click="addItem(item)" aria-label="Agregar al detalle">
                                    <i class="fa-solid fa-plus" aria-hidden="true"></i>
                                    <span v-if="quantityInCart(item.id)">{{ quantityInCart(item.id) }}</span>
                                </button>
                            </div>
                        </footer>
                    </article>
                </section>
                <WithoutData v-if="!filteredItems.length"/>
            </template>
        </main>

        <aside class="br-pos-ticket" aria-label="Resumen de venta">
            <section class="br-pos-ticket__config">
                <div v-if="!hasOpenCashSessions" class="br-pos-alert br-pos-alert--danger mb-0">
                    <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
                    <span>Abre una caja para registrar ventas POS.</span>
                </div>
                <div v-else-if="hasSingleCashSession" class="br-pos-cash-static">
                    <span>Caja activa</span>
                    <strong>{{ selectedCashSession?.register?.name || 'Caja' }}</strong>
                    <small>{{ selectedCashSession?.branch?.name || 'Sucursal no definida' }}</small>
                </div>
                <label v-else>
                    <span>Caja</span>
                    <v-select
                        v-model="selectedCashSession"
                        :options="cashSessionOptions"
                        class="bg-white"
                        :clearable="true"
                        :searchable="false"
                        placeholder="Seleccione caja abierta"
                        @option:selected="syncFromCashSession"/>
                </label>
                <label v-if="hasOpenCashSessions && showWarehouseInput">
                    <span>Almacén</span>
                    <v-select
                        v-model="selectedWarehouse"
                        :options="warehouseOptions"
                        class="bg-white"
                        :clearable="false"
                        :searchable="false"
                        placeholder="Seleccione almacén"/>
                </label>
                <div v-if="posConfigurationIssue" class="br-pos-alert br-pos-alert--danger mb-0">
                    <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
                    <span>{{ posConfigurationIssue }}</span>
                </div>
                <p v-if="hasOpenCashSessions" class="br-operational-scope mb-0">
                    <i class="fa-solid fa-cash-register" aria-hidden="true"></i>
                    <span>{{ activePosScopeLabel }}</span>
                </p>
            </section>

            <section class="br-pos-ticket__items">
                <article v-for="line in cart" :key="line.item.id" class="br-pos-ticket-item">
                    <div>
                        <strong>{{ line.item.name }}</strong>
                        <small><b>{{ line.quantity }}</b> x <b>{{ currencySign(line.item) }} {{ separatorNumber(line.price) }}</b></small>
                    </div>
                    <div class="br-pos-ticket-item__side">
                        <strong>{{ currencySign(line.item) }} {{ separatorNumber(lineTotal(line)) }}</strong>
                        <div class="br-pos-ticket-item__qty">
                            <button type="button" class="is-danger br-btn-danger" @click="decreaseItem(line.item.id)">-</button>
                            <span>{{ line.quantity }}</span>
                            <button type="button" @click="addItem(line.item)">+</button>
                        </div>
                    </div>
                </article>
            </section>

            <section v-if="hasOpenCashSessions" class="br-pos-ticket__checkout" aria-label="Cierre de venta">
                <section class="br-pos-total-box" aria-label="Total de venta">
                    <div class="br-pos-ticket__totals">
                        <div>
                            <span>Subtotal</span>
                            <strong>S/ {{ separatorNumber(subtotal) }}</strong>
                        </div>
                        <div v-for="tax in taxSummary" :key="tax.tax_id">
                            <span>{{ tax.label }}</span>
                            <strong>S/ {{ separatorNumber(tax.amount) }}</strong>
                        </div>
                        <div v-if="!taxSummary.length">
                            <span>IGV</span>
                            <strong>S/ 0.00</strong>
                        </div>
                    </div>

                    <header class="br-pos-ticket__header">
                        <div>
                            <p>Total</p>
                            <h2>S/ {{ separatorNumber(total) }}</h2>
                            <span>{{ totalQuantity }} ?tems agregados</span>
                        </div>
                        <button
                            type="button"
                            class="br-icon-action br-btn-danger"
                            data-bs-toggle="tooltip"
                            title="Limpiar venta"
                            :disabled="!cart.length"
                            @click="clearCart">
                            <i class="fa-solid fa-trash-can" aria-hidden="true"></i>
                        </button>
                    </header>
                </section>

                <button
                    type="button"
                    class="br-btn br-btn-success br-pos-ticket__pay"
                    :disabled="saving || !cart.length || Boolean(posConfigurationIssue)"
                    :title="posConfigurationIssue || 'Revisar venta'"
                    @click="openSaleConfirmation">
                    <i class="fa-solid fa-cash-register" aria-hidden="true"></i>
                    <span>Revisar venta</span>
                </button>
            </section>
        </aside>
    </section>

    <div
        class="modal fade br-entity-modal br-pos-detail-modal"
        id="brPosItemDetailModal"
        data-bs-backdrop="static"
        tabindex="-1"
        role="dialog"
        aria-modal="true"
        aria-labelledby="brPosItemDetailModalTitle">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header br-entity-modal__header">
                    <div>
                        <p class="br-entity-modal__eyebrow mb-1">Catálogo comercial</p>
                        <h2 id="brPosItemDetailModalTitle" class="modal-title br-entity-modal__title">
                            {{ selectedItemDetail?.name || 'Detalle' }}
                        </h2>
                    </div>
                    <button type="button" class="br-modal-close" data-bs-dismiss="modal" aria-label="Cerrar">
                        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                    </button>
                </div>

                <div class="modal-body br-entity-modal__body br-pos-detail-modal__body">
                    <div class="br-pos-detail-modal__hero" :style="itemCardStyle(selectedItemDetail || {})">
                        <span class="br-pos-product__image" :style="itemImageStyle(selectedItemDetail || {})" aria-hidden="true">
                            <i :class="itemIcon(selectedItemDetail || {})"></i>
                        </span>
                        <div>
                            <strong>{{ itemTypeLabel(selectedItemDetail) }}</strong>
                            <span>{{ currencySign(selectedItemDetail) }} {{ separatorNumber(selectedItemDetail?.price) }}</span>
                        </div>
                    </div>

                    <dl class="br-pos-detail-modal__list">
                        <div>
                            <dt>Marca</dt>
                            <dd>{{ selectedItemDetail?.brand?.name || 'Sin marca' }}</dd>
                        </div>
                        <div>
                            <dt>Descripción</dt>
                            <dd>{{ selectedItemDetail?.description || 'Sin descripción registrada' }}</dd>
                        </div>
                        <div>
                            <dt>Categorías</dt>
                            <dd>{{ formattedItemCategories(selectedItemDetail) }}</dd>
                        </div>
                        <div>
                            <dt>Código interno</dt>
                            <dd>{{ selectedItemDetail?.internal_code || 'No registrado' }}</dd>
                        </div>
                        <div>
                            <dt>Código de barras</dt>
                            <dd>{{ selectedItemDetail?.barcode || 'No registrado' }}</dd>
                        </div>
                        <div>
                            <dt>IGV exonerado</dt>
                            <dd>{{ boolLabel(itemIsIgvExempt(selectedItemDetail)) }}</dd>
                        </div>
                        <div>
                            <dt>Incluye IGV</dt>
                            <dd>{{ itemIsIgvExempt(selectedItemDetail) ? 'No aplica' : boolLabel(includesTax(selectedItemDetail)) }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="modal-footer br-entity-modal__footer">
                    <button type="button" class="br-btn br-btn-cancel" data-bs-dismiss="modal">Cerrar</button>
                    <button type="button" class="br-btn br-btn-primary" @click="addItem(selectedItemDetail)" data-bs-dismiss="modal">
                        <span>Agregar al detalle</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div
        class="modal fade br-entity-modal br-pos-sale-modal"
        id="brPosSaleConfirmationModal"
        data-bs-backdrop="static"
        tabindex="-1"
        role="dialog"
        aria-modal="true"
        aria-labelledby="brPosSaleConfirmationModalTitle">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header br-entity-modal__header">
                    <div>
                        <p class="br-entity-modal__eyebrow mb-1">Venta POS</p>
                        <h2 id="brPosSaleConfirmationModalTitle" class="modal-title br-entity-modal__title">
                            Confirmar venta
                        </h2>
                    </div>
                    <button type="button" class="br-modal-close" data-bs-dismiss="modal" aria-label="Cerrar">
                        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                    </button>
                </div>

                <div class="modal-body br-entity-modal__body br-pos-sale-modal__body">
                    <section class="br-pos-sale-document" aria-label="Comprobante de pago">
                        <label v-if="hasMultipleSeries">
                            <span class="br-pos-field-label">
                                <span>Comprobante</span>
                            </span>
                            <v-select
                                v-model="selectedSerie"
                                :options="serieOptions"
                                class="bg-white"
                                :clearable="false"
                                :searchable="false"
                                append-to-body
                                placeholder="Seleccione comprobante"/>
                        </label>
                        <div v-else class="br-pos-sale-static-field">
                            <span>Comprobante</span>
                            <strong>{{ selectedSerieLabel }}</strong>
                            <small v-if="!selectedSerie">No hay comprobantes disponibles para esta sucursal.</small>
                        </div>
                    </section>

                    <section class="br-pos-sale-customer" aria-label="Cliente de la venta">
                        <label>
                            <span class="br-pos-field-label">
                                <span>Cliente</span>
                                <AddCustomer
                                    triggerLabel="Agregar"
                                    :options="customerQuickCreateOptions"
                                    @postAction="addCustomerPostAction"/>
                            </span>
                            <v-select
                                v-model="selectedCustomer"
                                :options="customerOptions"
                                class="bg-white"
                                :clearable="false"
                                :searchable="true"
                                append-to-body
                                placeholder="Seleccione cliente"/>
                        </label>
                    </section>

                    <section class="br-pos-sale-summary" aria-label="Resumen de importes">
                        <div>
                            <span>Subtotal</span>
                            <strong>S/ {{ separatorNumber(subtotal) }}</strong>
                        </div>
                        <div v-for="tax in taxSummary" :key="`summary-${tax.tax_id}`">
                            <span>{{ tax.label }}</span>
                            <strong>S/ {{ separatorNumber(tax.amount) }}</strong>
                        </div>
                        <div v-if="!taxSummary.length">
                            <span>IGV</span>
                            <strong>S/ 0.00</strong>
                        </div>
                        <div class="is-total">
                            <span>Total</span>
                            <strong>S/ {{ separatorNumber(total) }}</strong>
                        </div>
                    </section>
                    <section v-if="optionalTaxes.length" class="br-document-settlement__taxes" aria-label="Impuestos extras">
                        <label class="form-label">Impuestos extras</label>
                        <template v-for="tax in optionalTaxes" :key="`pos-optional-tax-${tax.id}`">
                            <label class="br-entity-switch br-document-settlement__tax-option">
                                <input
                                    v-model="selectedTaxIds"
                                    class="form-check-input"
                                    type="checkbox"
                                    :value="tax.id"
                                    @change="syncSelectedTaxQuantity(tax)">
                                <span>
                                    <strong>{{ tax.name }}</strong>
                                    <small>{{ taxLabel(tax) }}</small>
                                </span>
                            </label>
                            <InputNumber
                                v-if="isFixedTax(tax) && selectedTaxIds.includes(tax.id)"
                                v-model="selectedTaxQuantities[tax.id]"
                                title=""
                                :inputClass="['form-control', 'br-tax-quantity']"
                                :decimals="0"
                                :minValue="taxQuantityMinimum(tax)"
                                :maxValue="taxQuantityMaximum(tax)"
                                :hasNegative="false"
                                @change="normalizeSelectedTaxQuantity(tax.id)">
                                <template v-slot:inputGroupPrepend>
                                    <span class="input-group-text br-tax-quantity__label">Veces</span>
                                </template>
                            </InputNumber>
                        </template>
                    </section>

                    <section class="br-pos-sale-payments" aria-label="Pagos de la venta">
                        <header>
                            <div>
                                <strong>Métodos de pago</strong>
                                <small>{{ showPaymentEditor ? 'Ajusta los importes antes de confirmar si el pago es mixto.' : 'Revisa cómo se registrará el pago de esta venta.' }}</small>
                            </div>
                            <button type="button" class="br-pos-payments__add" @click="showPaymentEditor = !showPaymentEditor">
                                <i class="fa-solid" :class="showPaymentEditor ? 'fa-eye-slash' : 'fa-pen-to-square'" aria-hidden="true"></i>
                                <span>{{ showPaymentEditor ? 'Ocultar edición' : 'Cambiar método de pago' }}</span>
                            </button>
                        </header>

                        <div v-if="!showPaymentEditor" class="br-pos-payments-summary">
                            <div v-for="payment in paymentSummary" :key="`confirm-summary-${payment.uid}`">
                                <span>{{ payment.label }}</span>
                                <strong>S/ {{ separatorNumber(payment.amount) }}</strong>
                            </div>
                        </div>

                        <div v-else class="br-pos-payments-editor">
                            <article v-for="(payment, index) in payments" :key="`confirm-${payment.uid}`" class="br-pos-payment">
                                <v-select
                                    v-model="payment.method"
                                    :options="paymentMethodOptions"
                                    class="bg-white"
                                    :clearable="false"
                                    :searchable="true"
                                    placeholder="Método"
                                    @update:modelValue="syncPaymentVariant(payment)">
                                    <template #selected-option="{ label, data }">
                                        <span class="br-payment-select-option">
                                            <img v-if="paymentAssetUrl(data)" :src="paymentAssetUrl(data)" alt="" class="br-payment-select-option__image">
                                            <span>{{ label }}</span>
                                        </span>
                                    </template>
                                    <template #option="{ label, data }">
                                        <span class="br-payment-select-option">
                                            <img v-if="paymentAssetUrl(data)" :src="paymentAssetUrl(data)" alt="" class="br-payment-select-option__image">
                                            <span>{{ label }}</span>
                                        </span>
                                    </template>
                                </v-select>
                                <v-select
                                    v-if="paymentVariantOptions(payment).length"
                                    v-model="payment.variant"
                                    :options="paymentVariantOptions(payment)"
                                    class="bg-white"
                                    :clearable="false"
                                    :searchable="true"
                                    append-to-body
                                    placeholder="Variante">
                                    <template #selected-option="{ label, data }">
                                        <span class="br-payment-select-option">
                                            <img v-if="paymentAssetUrl(data)" :src="paymentAssetUrl(data)" alt="" class="br-payment-select-option__image">
                                            <span>{{ label }}</span>
                                        </span>
                                    </template>
                                    <template #option="{ label, data }">
                                        <span class="br-payment-select-option">
                                            <img v-if="paymentAssetUrl(data)" :src="paymentAssetUrl(data)" alt="" class="br-payment-select-option__image">
                                            <span>{{ label }}</span>
                                        </span>
                                    </template>
                                </v-select>
                                <InputNumber
                                    v-model="payment.amount"
                                    title=""
                                    :titleClass="[]"
                                    :inputClass="['form-control', 'br-document-payment-amount']"
                                    :minValue="0">
                                    <template v-slot:inputGroupPrepend>
                                        <span class="input-group-text br-currency-prefix">S/</span>
                                    </template>
                                </InputNumber>
                                <button
                                    type="button"
                                    class="br-pos-payment__remove br-btn-danger"
                                    :disabled="payments.length === 1"
                                    @click="removePayment(index)"
                                    aria-label="Quitar método de pago">
                                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                                </button>
                            </article>

                            <button type="button" class="br-pos-payments__add br-pos-payments__add-row" @click="addPayment(false)">
                                <i class="fa-solid fa-circle-plus" aria-hidden="true"></i>
                                <span>Agregar método</span>
                            </button>
                        </div>

                        <p v-if="paymentDifference !== 0" class="br-pos-payments__difference">
                            Falta cuadrar S/ {{ separatorNumber(Math.abs(paymentDifference)) }}
                        </p>
                    </section>
                </div>

                <div class="modal-footer br-entity-modal__footer">
                    <button type="button" class="br-btn br-btn-cancel" data-bs-dismiss="modal">Volver</button>
                    <button
                        type="button"
                        class="br-btn br-btn-success"
                        :disabled="saving || !cart.length"
                        @click="confirmSale">
                        <span>{{ saving ? 'Confirmando venta' : 'Confirmar venta' }}</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import * as Alerts from "@System/Helpers/Alerts.js";
import * as Requests from "@System/Helpers/Requests.js";
import * as Utils from "@System/Helpers/Utils.js";
import AddCustomer from "@System/Components/Customers/AddCustomer.vue";
import Breadcrumb from "@System/Components/Breadcrumb.vue";
import InputNumber from "@System/Components/InputNumber.vue";
import Loader from "@System/Components/Loader.vue";
import WithoutData from "@System/Components/WithoutData.vue";

export default {
    components: {
        AddCustomer,
        Breadcrumb,
        InputNumber,
        Loader,
        WithoutData
    },
    data() {
        return {
            config: Requests.config({entity: "sales"}),
            loading: false,
            saving: false,
            search: "",
            showPaymentEditor: false,
            selectedCategoryId: null,
            branches: [],
            warehouses: [],
            customers: [],
            customerConfig: {},
            items: [],
            categories: [],
            currencies: [],
            taxes: [],
            paymentMethods: [],
            cashSessions: [],
            selectedBranch: null,
            selectedSerie: null,
            selectedWarehouse: null,
            selectedCustomer: null,
            selectedPaymentMethod: null,
            selectedCashSession: null,
            selectedItemDetail: null,
            selectedTaxIds: [],
            selectedTaxQuantities: {},
            payments: [],
            cart: [],
            serviceSessionId: null,
            serviceSession: null
        };
    },
    computed: {
        breadcrumbTitles() {
            return [{title: "Ventas"}, {title: "Venta POS", active: true}];
        },
        branchOptions() {
            return this.branches.map(branch => ({...branch, label: branch.name}));
        },
        warehouseOptions() {
            const branchId = this.selectedBranch?.id;
            return this.warehouses
                .filter(warehouse => warehouse?.status === "active" && (!branchId || warehouse.branch_id === branchId))
                .map(warehouse => ({...warehouse, label: warehouse.name}));
        },
        serieOptions() {
            return (this.selectedBranch?.series || [])
                .filter(serie => serie?.status === "active")
                .map(serie => ({
                    ...serie,
                    label: this.serieLabel(serie)
                }));
        },
        hasMultipleSeries() {
            return this.serieOptions.length > 1;
        },
        selectedSerieLabel() {
            return this.selectedSerie ? this.serieLabel(this.selectedSerie) : "Sin comprobante asignado";
        },
        customerOptions() {
            return this.customers.map(customer => ({...customer, label: customer.name}));
        },
        customerQuickCreateOptions() {
            return {
                identityDocumentTypes: this.customerConfig?.identityDocumentTypes || [],
                genders: this.customerConfig?.genders || [],
                statuses: this.customerConfig?.statuses || []
            };
        },
        paymentMethodOptions() {
            return this.paymentMethods.map(method => ({...method, code: method.id, label: method.name, data: method}));
        },
        cashSessionOptions() {
            return this.cashSessions
                .map(session => ({
                    ...session,
                    label: `${session.register?.name || 'Caja'} - ${session.branch?.name || 'Sucursal'}`
                }));
        },
        hasOpenCashSessions() {
            return this.cashSessionOptions.length > 0;
        },
        hasSingleCashSession() {
            return this.cashSessionOptions.length === 1;
        },
        showWarehouseInput() {
            return this.warehouseOptions.length > 1;
        },
        posConfigurationIssue() {
            if(!this.hasOpenCashSessions || !this.selectedBranch) return null;

            if(!this.serieOptions.length) {
                return "Esta sucursal no tiene una serie activa. Crea o activa una serie antes de vender.";
            }

            if(!this.warehouseOptions.length) {
                return "Esta sucursal no tiene un almacén activo. Crea o activa un almacén antes de vender.";
            }

            return null;
        },
        activePosScopeLabel() {
            const cash = this.selectedCashSession?.register?.name || "Caja no seleccionada";
            const branch = this.selectedBranch?.name || this.selectedCashSession?.branch?.name || "Sucursal no seleccionada";
            const warehouse = this.selectedWarehouse?.name || "Almacén no seleccionado";

            return `${cash} ? ${branch} ? ${warehouse}`;
        },
        visibleCategories() {
            return this.categories.filter(category => this.countByCategory(category.id) > 0);
        },
        filteredItems() {
            const text = this.search.toLowerCase();

            return this.items.filter(item => {
                const matchesCategory = this.selectedCategoryId === null || this.itemCategoryIds(item).includes(this.selectedCategoryId);
                const matchesSearch = !text || [
                    item.name,
                    item.internal_code,
                    item.barcode,
                    item.brand?.name,
                    item.description
                ].some(value => String(value || "").toLowerCase().includes(text));

                return matchesCategory && matchesSearch;
            });
        },
        totalQuantity() {
            return this.cart.reduce((total, line) => total + Number(line.quantity || 0), 0);
        },
        grossSubtotal() {
            return this.fixedNumber(this.cart.reduce((total, line) => total + this.lineTotal(line), 0));
        },
        subtotal() {
            return this.fixedNumber(Number(this.grossSubtotal || 0) - Number(this.includedTaxTotal || 0));
        },
        requiredTaxes() {
            return this.taxes.filter(tax => this.taxIsRequired(tax?.data || tax));
        },
        optionalTaxes() {
            return this.taxes.filter(tax => !this.taxIsRequired(tax?.data || tax));
        },
        appliedTaxes() {
            return this.taxes.filter(tax => {
                const data = tax?.data || tax;
                return this.taxIsRequired(data) || this.selectedTaxIds.includes(data.id);
            });
        },
        taxSummary() {
            return this.appliedTaxes.map(tax => {
                const taxData = tax?.data || tax || {};
                const line = this.calculateSaleTaxLine(taxData);

                return {
                    tax_id: taxData.id ?? tax.code ?? tax.id,
                    name: taxData.name ?? tax.label ?? "IGV",
                    label: this.taxLabel(taxData),
                    rate: Number(taxData.rate ?? taxData.percentage ?? 0),
                    calculation_type: taxData.calculation_type || "percentage",
                    operation_type: taxData.operation_type || "addition",
                    is_required: this.taxIsRequired(taxData),
                    quantity: this.isFixedTax(taxData) ? this.selectedTaxQuantity(taxData.id) : 1,
                    base_amount: Number(line.baseAmount || 0),
                    total_impact: Number(line.totalImpact || 0),
                    amount: Number(line.amount || 0)
                };
            });
        },
        totalTax() {
            return this.fixedNumber(this.taxSummary.reduce((total, tax) => total + tax.amount, 0));
        },
        taxImpactTotal() {
            return this.fixedNumber(this.taxSummary.reduce((total, tax) => total + Number(tax.total_impact || 0), 0));
        },
        includedTaxTotal() {
            return this.fixedNumber(Number(this.totalTax || 0) - Number(this.taxImpactTotal || 0));
        },
        total() {
            return this.fixedNumber(Number(this.grossSubtotal || 0) + Number(this.taxImpactTotal || 0));
        },
        paymentsTotal() {
            return this.payments.reduce((total, payment) => total + Number(payment.amount || 0), 0);
        },
        paymentSummary() {
            return this.payments
                .filter(payment => payment.method)
                .map(payment => ({
                    uid: payment.uid,
                    label: payment.variant?.label || payment.method?.label || payment.method?.name || "Método de pago",
                    amount: Number(payment.amount || 0)
                }));
        },
        paymentDifference() {
            return this.fixedNumber(Number(this.total || 0) - Number(this.paymentsTotal || 0));
        },
        canSubmit() {
            return this.cart.length > 0
                && this.hasOpenCashSessions
                && this.selectedBranch
                && this.selectedSerie
                && this.selectedWarehouse
                && this.selectedCustomer
                && this.selectedCashSession
                && this.payments.length > 0
                && this.payments.every(payment => payment.method && Number(payment.amount || 0) > 0)
                && this.paymentDifference === 0;
        }
    },
    watch: {
        total() {
            this.syncDefaultPaymentAmount();
        }
    },
    mounted() {
        Utils.navbarItem("menu-parent-sales", {addClass: "open"});
        Utils.navbarItem("menu-sales-pos", {addClass: "active"});
        this.initParams();
    },
    methods: {
        taxLabel(tax = {}) {
            const name = tax?.name || "IGV";
            const rate = Number(tax?.rate || 0);
            const calculationType = tax?.calculation_type || "percentage";
            const operationType = tax?.operation_type || "addition";
            const sign = operationType === "subtraction" ? "-" : "+";

            if(calculationType === "fixed") {
                return `${name} ${sign} S/ ${this.separatorNumber(rate)}`;
            }

            return `${name} ${sign}${this.separatorNumber(rate)}%`;
        },
        calculateTaxAmount(tax = {}, baseAmount = 0) {
            const base = Number(baseAmount || 0);
            const rate = Number(tax?.rate || 0);
            const calculationType = tax?.calculation_type || "percentage";
            const operationType = tax?.operation_type || "addition";
            const quantity = this.isFixedTax(tax) ? this.selectedTaxQuantity(tax.id) : 1;
            const amount = calculationType === "fixed"
                ? rate * quantity
                : base * (rate / 100);

            return this.fixedNumber(operationType === "subtraction" ? amount * -1 : amount);
        },
        isFixedTax(tax = {}) {
            return (tax?.calculation_type || "percentage") === "fixed";
        },
        taxQuantityMinimum(tax = {}) {
            return Math.max(1, Number(tax?.min_apply_quantity ?? 1));
        },
        taxQuantityMaximum(tax = {}) {
            return tax?.max_apply_quantity == null ? undefined : Math.max(this.taxQuantityMinimum(tax), Number(tax.max_apply_quantity));
        },
        taxById(taxId) {
            return this.taxes.find(tax => Number((tax?.data || tax)?.id) === Number(taxId))?.data || this.taxes.find(tax => Number(tax?.id) === Number(taxId)) || {};
        },
        clampTaxQuantity(taxId, quantity) {
            const tax = this.taxById(taxId);
            const minimum = this.taxQuantityMinimum(tax);
            const maximum = this.taxQuantityMaximum(tax);
            const normalized = Math.max(minimum, parseInt(Number(quantity || minimum), 10));

            return maximum === undefined ? normalized : Math.min(normalized, maximum);
        },
        selectedTaxQuantity(taxId) {
            return this.clampTaxQuantity(taxId, this.selectedTaxQuantities[taxId]);
        },
        normalizeSelectedTaxQuantity(taxId) {
            this.selectedTaxQuantities[taxId] = this.clampTaxQuantity(taxId, this.selectedTaxQuantities[taxId]);
        },
        syncSelectedTaxQuantity(tax = {}) {
            if(!this.isFixedTax(tax)) return;
            this.selectedTaxQuantities[tax.id] = this.selectedTaxIds.includes(tax.id) ? this.taxQuantityMinimum(tax) : 0;
        },
        taxIsRequired(tax = {}) {
            return [true, 1, "1", "true"].includes(tax?.is_required);
        },
        isIgvTax(tax = {}) {
            const code = String(tax?.code || "").toUpperCase();
            const name = String(tax?.name || "").toUpperCase();

            return code.includes("IGV") || name === "IGV";
        },
        calculateSaleTaxLine(tax = {}) {
            const rate = Number(tax?.rate || 0);
            const calculationType = tax?.calculation_type || "percentage";
            const operationType = tax?.operation_type || "addition";
            const isIgvTax = this.isIgvTax(tax);
            let base = 0;
            let amount = 0;
            let totalImpact = 0;

            if(calculationType === "fixed") {
                amount = this.calculateTaxAmount(tax, 0);
                totalImpact = amount;
            }else {
                this.cart.forEach(line => {
                    const lineTotal = this.lineTotal(line);
                    if(lineTotal <= 0) return;
                    if(isIgvTax && this.itemIsIgvExempt(line.item)) return;

                    const taxIsIncluded = this.includesTax(line.item) && operationType === "addition" && rate > 0;

                    if(taxIsIncluded) {
                        const lineBase = Number(this.fixedNumber(lineTotal / (1 + (rate / 100))));
                        const lineAmount = Number(this.fixedNumber(lineTotal - lineBase));
                        base += lineBase;
                        amount += lineAmount;
                        return;
                    }

                    const lineAmount = Number(this.calculateTaxAmount(tax, lineTotal));
                    base += lineTotal;
                    amount += lineAmount;
                    totalImpact += lineAmount;
                });
            }

            return {
                baseAmount: this.fixedNumber(base),
                amount: this.fixedNumber(amount),
                totalImpact: this.fixedNumber(totalImpact)
            };
        },
        async initParams() {
            this.loading = true;
            const result = await Requests.get({route: this.config.routes.initParams, data: {page: "pos"}});
            this.loading = false;

            if(!Requests.valid({result})) {
                Alerts.toastrs({type: "error", subtitle: "No fue posible cargar el POS."});
                return;
            }

            const data = result.data.config;
            this.branches = data.branches?.records || [];
            this.warehouses = data.warehouses?.records || [];
            this.customerConfig = data.customers || {};
            this.customers = data.customers?.records || [];
            this.items = data.items?.records || [];
            this.categories = data.categories?.records || [];
            this.currencies = data.currencies?.records || [];
            this.taxes = data.taxes?.records || [];
            this.paymentMethods = data.paymentMethods?.records || [];
            this.cashSessions = data.cashSessions?.records || [];

            this.selectedBranch = this.branchOptions[0] || null;
            this.selectedCustomer = this.customerOptions[0] || null;
            this.selectedPaymentMethod = this.paymentMethodOptions[0] || null;
            this.selectedCashSession = this.cashSessionOptions[0] || null;
            this.syncFromCashSession();
            this.resetPayments();
            Alerts.tooltips({});
            await this.preloadServiceSession();
        },
        async preloadServiceSession() {
            const sessionId = Number(new URLSearchParams(window.location.search).get("service_session_id") || 0);
            if(!sessionId) return;

            const serviceRoutes = Requests.config({entity: "service_operations"}).routes;
            const result = await Requests.get({route: `${serviceRoutes.sessions}/${sessionId}`});

            if(!Requests.valid({result})) {
                Alerts.toastrs({type: "warning", subtitle: result.data?.msg || "La atención ya no está disponible."});
                return;
            }

            const session = result.data.data;
            if(!["pending", "in_progress"].includes(session.status)) {
                Alerts.toastrs({type: "warning", subtitle: "La atención ya fue finalizada y no puede volver a cobrarse."});
                return;
            }

            this.serviceSessionId = session.id;
            this.serviceSession = session;
            this.selectedBranch = this.branchOptions.find(branch => branch.id === session.branch_id) || this.selectedBranch;
            this.selectedCashSession = this.cashSessionOptions.find(cash => cash.branch_id === session.branch_id) || null;
            this.syncFromCashSession();

            if(session.customer_id) {
                this.selectedCustomer = this.customerOptions.find(customer => customer.id === session.customer_id) || this.selectedCustomer;
            }

            this.cart = (session.items || []).map(detail => {
                const item = this.items.find(record => record.id === detail.item_id);
                if(!item) return null;

                return {
                    item,
                    quantity: Number(detail.quantity || 1),
                    price: Number(detail.unit_price ?? item.price ?? 0)
                };
            }).filter(Boolean);
            this.resetPayments();

            if(!this.cart.length) {
                Alerts.toastrs({type: "warning", subtitle: "La atención no contiene detalles disponibles para cobrar."});
            }
        },
        syncBranchDependents() {
            this.selectedSerie = this.serieOptions[0] || null;
            this.selectedWarehouse = this.warehouseOptions[0] || null;
            this.selectedCashSession = this.cashSessionOptions[0] || null;
        },
        syncFromCashSession() {
            const branchId = this.selectedCashSession?.branch_id;

            this.selectedBranch = this.branchOptions.find(branch => branch.id === branchId) || this.selectedBranch || this.branchOptions[0] || null;
            this.selectedSerie = this.serieOptions[0] || null;
            this.selectedWarehouse = this.warehouseOptions[0] || null;
        },
        serieLabel(serie) {
            const documentType = serie?.document_type || serie?.documentType || {};
            const documentName = this.documentTypeLabel(documentType);
            const serieName = serie?.legible_serie || serie?.serie || serie?.code || serie?.name || "";

            return [serieName, documentName].filter(Boolean).join(" - ");
        },
        documentTypeLabel(documentType) {
            const value = String(documentType?.name || documentType?.code || "Comprobante").trim().toUpperCase();

            if(value.includes("BOLETA") || value === "BV") return "Boleta";
            if(value === "FACTURA" || value === "FA") return "Factura";

            return value;
        },
        defaultPaymentMethod() {
            return this.paymentMethodOptions.find(method => {
                const name = String(method.name || method.label || "").toLowerCase();
                const code = String(method.code || "").toLowerCase();

                return method.is_default || code.includes("cash") || name.includes("efectivo");
            }) || this.paymentMethodOptions[0] || null;
        },
        paymentAssetUrl(record) {

            const path = record?.image_path || record?.data?.image_path;

            if(!path) return "";

            if(/^https?:\/\//i.test(path) || path.startsWith("/")) return path;

            return `/${path}`;

        },
        paymentVariantOptions(payment) {

            const method = payment?.method?.data || payment?.method || {};
            const variants = method.supports_variants ? (method.variants || []) : [];

            return variants
                .filter(variant => variant.status !== "inactive")
                .map(variant => ({
                    code: variant.id,
                    label: variant.name,
                    data: variant
                }));

        },
        syncPaymentVariant(payment) {

            if(!payment) return;

            const options = this.paymentVariantOptions(payment);

            if(options.length === 0) {
                payment.variant = null;
                return;
            }

            const current = options.find(option => Number(option.code) === Number(payment.variant?.code));
            payment.variant = current || options.find(option => option.data?.is_default) || options[0];

        },
        resetPayments() {
            const payment = {
                uid: Date.now(),
                method: this.defaultPaymentMethod(),
                variant: null,
                amount: this.fixedNumber(this.total)
            };

            this.syncPaymentVariant(payment);
            this.payments = [payment];
            this.showPaymentEditor = false;
        },
        syncDefaultPaymentAmount() {
            if(this.payments.length !== 1) return;

            this.payments[0].amount = this.fixedNumber(this.total);
        },
        addPayment(openEditor = true) {
            if(openEditor) {
                this.showPaymentEditor = true;
            }

            const payment = {
                uid: Date.now() + this.payments.length,
                method: this.paymentMethodOptions.find(method => !this.payments.some(payment => payment.method?.id === method.id)) || this.paymentMethodOptions[0] || null,
                variant: null,
                amount: Math.max(this.fixedNumber(this.paymentDifference), 0)
            };

            this.syncPaymentVariant(payment);
            this.payments.push(payment);
        },
        removePayment(index) {
            if(this.payments.length === 1) return;

            this.payments.splice(index, 1);
            this.syncDefaultPaymentAmount();
        },
        itemCategoryIds(item) {
            return (item.category_items || item.categoryItems || [])
                .map(row => row.category_id || row.category?.id)
                .filter(Boolean);
        },
        countByCategory(categoryId) {
            return this.items.filter(item => this.itemCategoryIds(item).includes(categoryId)).length;
        },
        addItem(item) {
            const line = this.cart.find(row => row.item.id === item.id);

            if(line) {
                line.quantity = Number(line.quantity) + 1;
                return;
            }

            this.cart.push({
                item,
                quantity: 1,
                price: Number(item.price || 0)
            });
        },
        decreaseItem(itemId) {
            const line = this.cart.find(row => row.item.id === itemId);
            if(!line) return;

            line.quantity = Number(line.quantity) - 1;

            if(line.quantity <= 0) {
                this.cart = this.cart.filter(row => row.item.id !== itemId);
            }
        },
        quantityInCart(itemId) {
            return this.cart.find(row => row.item.id === itemId)?.quantity || 0;
        },
        clearCart() {
            this.cart = [];
            this.resetPayments();
        },
        lineTotal(line) {
            return this.fixedNumber(Number(line.quantity || 0) * Number(line.price || 0));
        },
        fixedNumber(value, decimals = null) {
            return Number(Utils.fixedNumber(value || 0, decimals));
        },
        currencySign(item = null) {
            return item?.currency?.sign || this.currencies[0]?.sign || "S/";
        },
        separatorNumber(value) {
            return Utils.separatorNumber(value || 0);
        },
        today() {
            return new Date().toISOString().slice(0, 10);
        },
        itemIsIgvExempt(item) {
            const value = item?.igv_exempt ?? item?.data?.igv_exempt ?? false;

            return [true, 1, "1", "true"].includes(value);
        },
        includesTax(item) {
            if(this.itemIsIgvExempt(item)) return false;

            const value = item?.price_includes_tax ?? item?.data?.price_includes_tax ?? true;

            return [true, 1, "1", "true"].includes(value);
        },
        itemIcon(item) {
            if(item?.type === "service") return "fa-solid fa-dumbbell";
            if(item?.type === "subscription") return "fa-solid fa-id-card";

            return "fa-solid fa-box-open";
        },
        itemImageStyle(item) {
            if(item?.type === "service") {
                return {
                    background: "color-mix(in srgb, #fef3c7 78%, #ffffff)",
                    color: "#b45309"
                };
            }

            if(item?.type === "subscription") {
                return {
                    background: "color-mix(in srgb, var(--br-success-soft) 74%, #ffffff)",
                    color: "var(--br-success-hover)"
                };
            }

            return {
                background: "color-mix(in srgb, var(--br-primary-soft) 78%, #ffffff)",
                color: "var(--br-primary-active)"
            };
        },
        itemCardStyle(item) {
            if(item?.type === "service") {
                return {
                    "--br-pos-item-accent": "#b45309",
                    "--br-pos-item-hover": "color-mix(in srgb, #fef3c7 68%, #ffffff)"
                };
            }

            if(item?.type === "subscription") {
                return {
                    "--br-pos-item-accent": "var(--br-success-hover)",
                    "--br-pos-item-hover": "color-mix(in srgb, var(--br-success-soft) 68%, #ffffff)"
                };
            }

            return {
                "--br-pos-item-accent": "var(--br-primary)",
                "--br-pos-item-hover": "color-mix(in srgb, var(--br-primary-soft) 68%, #ffffff)"
            };
        },
        openItemDetail(item) {
            this.selectedItemDetail = item;
            Alerts.modals({type: "show", id: "brPosItemDetailModal"});
        },
        itemTypeLabel(item) {
            if(item?.type === "service") return "Servicio";
            if(item?.type === "subscription") return "Membresía";

            return "Producto";
        },
        formattedItemCategories(item) {
            const categories = (item?.category_items || item?.categoryItems || [])
                .map(row => row.category?.name)
                .filter(Boolean);

            return categories.length ? categories.join(", ") : "Sin categorías";
        },
        boolLabel(value) {
            return value ? "Sí" : "No";
        },
        openSaleConfirmation() {
            if(!this.cart.length) {
                Alerts.toastrs({type: "warning", subtitle: "Agrega al menos un producto, servicio o membresía al detalle."});
                return;
            }

            if(this.posConfigurationIssue) {
                Alerts.toastrs({type: "warning", subtitle: this.posConfigurationIssue});
                return;
            }

            this.showPaymentEditor = false;
            Alerts.modals({type: "show", id: "brPosSaleConfirmationModal"});
        },
        confirmSale() {
            if(!this.canSubmit) {
                Alerts.toastrs({type: "warning", subtitle: "Revisa los métodos de pago. El total pagado debe cuadrar con la venta."});
                return;
            }

            Alerts.modals({type: "hide", id: "brPosSaleConfirmationModal"});
            this.submitSale();
        },
        addCustomerPostAction({response = null}) {
            if(!Requests.valid({result: response}) || !response?.data?.customer) return;

            const customer = response.data.customer;
            const exists = this.customers.some(record => record.id === customer.id);

            if(!exists) {
                this.customers.push(customer);
            }

            this.selectedCustomer = {...customer, label: customer.name};
        },
        buildPayload() {
            return {
                source_channel: "pos",
                service_session_id: this.serviceSessionId,
                branch_id: this.selectedBranch?.id,
                serie_id: this.selectedSerie?.id,
                warehouse_id: this.selectedWarehouse?.id,
                cash_session_id: this.selectedCashSession?.id,
                holder_id: this.selectedCustomer?.id,
                currency_id: this.currencies[0]?.id || this.cart[0]?.item?.currency_id,
                issue_date: this.today(),
                observation: "Venta POS",
                details: this.cart.map(line => ({
                    item_id: line.item.id,
                    type: line.item.type,
                    currency_id: line.item.currency_id,
                    name: line.item.name,
                    quantity: line.quantity,
                    price: line.price,
                    igv_exempt: this.itemIsIgvExempt(line.item),
                    price_includes_tax: this.includesTax(line.item),
                    commission_type: line.item?.commission_type || (Number(line.item?.commission_rate || 0) > 0 ? "percentage" : "none"),
                    commission_value: Number(line.item?.commission_value ?? line.item?.commission_rate ?? 0),
                    observation: null
                })),
                taxes: this.taxSummary.map(tax => ({
                    tax_id: tax.tax_id,
                    rate: tax.rate,
                    calculation_type: tax.calculation_type,
                    operation_type: tax.operation_type,
                    is_required: tax.is_required,
                    quantity: tax.quantity,
                    base_amount: this.fixedNumber(tax.base_amount),
                    amount: this.fixedNumber(tax.amount)
                })),
                payments: this.payments.map(payment => ({
                    payment_method_id: payment.method?.id,
                    payment_method_variant_id: payment.variant?.code || null,
                    name: payment.variant?.label || payment.method?.label || payment.method?.name || null,
                    amount: this.fixedNumber(payment.amount || 0),
                    reference: "POS",
                    note: null
                }))
            };
        },
        async submitSale() {
            if(!this.canSubmit) {
                Alerts.toastrs({type: "warning", subtitle: "Completa caja, cliente, pagos y productos. Los pagos deben cuadrar con el total."});
                return;
            }

            this.saving = true;
            Alerts.loading?.({message: "Generando venta"});

            const result = await Requests.post({
                route: this.config.routes.store,
                data: this.buildPayload()
            });

            this.saving = false;
            Alerts.close?.();
            window.Swal?.close?.();

            if(!Requests.valid({result})) {
                Alerts.toastrs({type: "error", subtitle: result.data?.msg || "No fue posible generar la venta."});
                return;
            }

            Alerts.toastrs({type: "success", subtitle: result.data?.msg || "Venta generada correctamente."});
            this.clearCart();
            this.serviceSessionId = null;
            this.serviceSession = null;
        }
    }
};
</script>
