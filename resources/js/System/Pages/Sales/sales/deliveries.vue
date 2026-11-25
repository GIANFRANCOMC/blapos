<template>
    <FgPageHeader title="Entregas pendientes" description="Consulta los productos pendientes y registra sus entregas."/>

    <InitialLoadTransition>
        <PageSkeleton v-if="initialLoading" table/>
        <div v-else-if="initialError">
            <FgEmptyState title="No pudimos abrir entregas pendientes" description="Intenta cargar la pantalla de nuevo.">
                <button type="button" class="fg-button fg-button--secondary" @click="initializePage">Reintentar</button>
            </FgEmptyState>
        </div>
        <div v-else>
            <FgResponsiveFilters
                id="sales-filters-deliveries"
                class="fg-responsive-filters--deliveries"
                label="Filtros de entregas"
                :loading="loading"
                @search="listDeliveries({applyFilters: true})"
                @clear="clearFilters">
                <div class="fg-field-cell col-12 col-sm-6 col-lg-4">
                    <InputSlot hasDiv title="Sucursal" :titleClass="[config.forms.classes.title]" xl="3" lg="4">
                    <template #input>
                        <FgSelect
                            v-model="filters.branch"
                            :options="branches"
                            :clearable="true"
                            :searchable="branches.length > 6"
                            placeholder="Todas las sucursales"/>
                    </template>
                </InputSlot>
                </div>

                <div class="fg-field-cell col-12 col-sm-6 col-lg-4">
                    <InputSlot hasDiv title="Almacén" :titleClass="[config.forms.classes.title]" xl="3" lg="4">
                    <template #input>
                        <FgSelect
                            v-model="filters.warehouse"
                            :options="warehouses"
                            :clearable="true"
                            :searchable="warehouses.length > 6"
                            placeholder="Todos los almacenes"/>
                    </template>
                </InputSlot>
                </div>

                <div class="fg-field-cell col-12 col-sm-6 col-lg-4">
                    <InputSlot hasDiv title="Cliente" :titleClass="[config.forms.classes.title]" xl="3" lg="4">
                    <template #input>
                        <FgSelect
                            v-model="filters.holder"
                            :options="holders"
                            :filterable="false"
                            :loading="remoteOptions.customers.loading"
                            :clearable="true"
                            placeholder="Todos los clientes"
                            @open="openOptions('customers')"
                            @search="searchOptions('customers', $event)">
                            <template #selected-option="{ label }">
                                <span class="fg-selection-summary__value" :title="label">{{ label }}</span>
                            </template>
                            <template #list-footer>
                                <li v-if="remoteOptions.customers.hasMore" class="px-2 py-1">
                                    <button type="button" class="w-100 text-start fg-button fg-button--link" @mousedown.prevent @click.stop="loadMoreOptions('customers')">Cargar más clientes</button>
                                </li>
                            </template>
                            <template #no-options>Sin clientes para esta búsqueda.</template>
                        </FgSelect>
                    </template>
                </InputSlot>
                </div>

                <div class="fg-field-cell col-12 col-sm-6 col-lg-4">
                    <InputSlot hasDiv title="Estado de entrega" :titleClass="[config.forms.classes.title]" xl="3" lg="4">
                    <template #input>
                        <FgSelect
                            v-model="filters.deliveryStatus"
                            :options="deliveryStatuses"
                            :clearable="true"
                            :searchable="false"
                            placeholder="Todos"/>
                    </template>
                </InputSlot>
                </div>

                <div class="fg-field-cell col-12 col-sm-6 col-lg-7">
                    <SearchInput
                    v-model="filters.search"
                    hasDiv
                    title="Búsqueda"
                    :titleClass="[config.forms.classes.title]"
                    placeholder="Documento, cliente, producto o código"
                    xl="7"
                    lg="7"/>
                </div>
            </FgResponsiveFilters>

            <ResponsiveTable class="fg-sales-table-scroll">
                <table role="table" class="mb-0 fg-table fg-record-table fg-record-table--deliveries">
                    <thead role="rowgroup">
                        <tr role="row">
                            <th scope="col" role="columnheader" style="width: 16%;">Venta</th>
                            <th scope="col" role="columnheader" style="width: 20%;">Cliente</th>
                            <th scope="col" role="columnheader" style="width: 18%;">Almacén</th>
                            <th scope="col" role="columnheader" style="width: 24%;">Pendiente</th>
                            <th scope="col" role="columnheader" style="width: 12%;">Estado</th>
                            <th scope="col" role="columnheader" class="text-center" style="width: 10%;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody role="rowgroup">
                        <tr role="row" class="fg-table__state" v-if="loading">
                            <td role="cell" colspan="6" class="text-center py-4"><FgListLoading label="Buscando entregas…"/></td>
                        </tr>
                        <template v-else-if="records.total > 0">
                            <tr role="row" v-for="record in records.data" :key="record.id">
                                <td role="cell" class="fg-record-table__identity">
                                    <strong class="fg-row-title" v-text="saleLabel(record)"></strong>
                                    <span class="fg-table-note" v-text="record.sale_header?.formatted_issue_date || ''"></span>
                                </td>
                                <td role="cell">
                                    <span class="fg-record-table__label" aria-hidden="true">Cliente</span>
                                    <div class="fg-record-table__value">
                                        <strong class="fg-row-title" v-text="record.sale_header?.holder?.name || 'Cliente no identificado'"></strong>
                                        <span class="fg-table-note" v-text="record.sale_header?.holder?.document_number || 'Sin documento'"></span>
                                    </div>
                                </td>
                                <td role="cell">
                                    <span class="fg-record-table__label" aria-hidden="true">Almacén</span>
                                    <div class="fg-record-table__value">
                                        <strong class="fg-row-title" v-text="record.warehouse?.name || 'Por definir'"></strong>
                                        <span class="fg-table-note" v-text="record.warehouse?.branch?.name || record.sale_header?.serie?.branch?.name || ''"></span>
                                    </div>
                                </td>
                                <td role="cell">
                                    <span class="fg-record-table__label" aria-hidden="true">Pendiente</span>
                                    <div class="fg-record-table__value">
                                        <div class="fg-sale-delivery-progress">
                                            <span>
                                                <strong>{{ separatorNumber(record.pending_quantity) }}</strong>
                                                pendiente de {{ separatorNumber(record.total_quantity) }}
                                            </span>
                                            <span class="fg-sale-delivery-progress__percent">{{ Math.round(deliveryPercent(record)) }} % entregado</span>
                                            <div class="fg-sale-delivery-progress__bar" aria-hidden="true">
                                                <span :style="{width: `${deliveryPercent(record)}%`}"></span>
                                            </div>
                                        </div>
                                        <div class="fg-sale-delivery-products">
                                            <span
                                                v-for="item in pendingItems(record).slice(0, 3)"
                                                :key="item.id"
                                                class="fg-sale-delivery-products__item">
                                                {{ item.sale_body?.name || item.item?.name }}:
                                                <strong>{{ separatorNumber(item.quantity_pending) }}</strong>
                                            </span>
                                            <span v-if="pendingItems(record).length > 3" class="fg-sale-delivery-products__item is-muted">
                                                +{{ pendingItems(record).length - 3 }} más
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                <td role="cell" class="fg-record-table__state">
                                    <StatusBadge :status="record.status" :formatted-status="record.formatted_status"/>
                                    <span v-if="record.events?.length" class="fg-table-note mt-1">
                                        {{ record.events.length }} movimiento{{ record.events.length === 1 ? "" : "s" }}
                                    </span>
                                    <span v-if="record.last_delivered_at" class="fg-table-note">
                                        Última entrega: {{ legibleDate(record.last_delivered_at) }}
                                    </span>
                                </td>
                                <td role="cell" class="text-center fg-record-table__actions">
                                    <button
                                        type="button"
                                        class="fg-button fg-button--link"
                                        title="Registrar entrega"
                                        :aria-label="`Registrar entrega de ${saleLabel(record)}`"
                                        @click="openDeliveryModal(record)">
                                        Registrar
                                    </button>
                                </td>
                            </tr>
                        </template>
                        <tr role="row" class="fg-table__state" v-else>
                            <td role="cell" colspan="6" class="text-center">
                                <FgEmptyState v-if="failedList" title="No pudimos cargar entregas pendientes" description="Vuelve a intentar la consulta.">
                                    <button type="button" class="fg-button fg-button--secondary" @click="listDeliveries({url: failedList.url})">Reintentar</button>
                                </FgEmptyState>
                                <FgEmptyState v-else-if="Object.values(requestFilters()).some(Boolean)" title="No encontramos entregas pendientes" description="Ajusta los filtros y vuelve a buscar."/>
                                <FgEmptyState v-else title="No hay entregas pendientes"/>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </ResponsiveTable>

            <Paginator v-if="!failedList" :links="records.links" :pagination="records" :loading="loading" @clickPage="listDeliveries"/>

        </div>
    </InitialLoadTransition>
    <div
        :id="modal.id"
        :aria-labelledby="modal.id + '-title'"
        class="modal fade fg-modal fg-modal--delivery"
        tabindex="-1"
        data-bs-backdrop="static"
        data-bs-keyboard="false"
        aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content fg-modal__content">
                <div class="modal-header fg-modal__header">
                    <h2 :id="modal.id + '-title'" class="modal-title fg-modal__title">Registrar entrega</h2>
                    <button type="button" class="fg-button fg-button--icon" data-bs-dismiss="modal" aria-label="Cerrar">
                        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                    </button>
                </div>
                <div class="modal-body fg-modal__body fg-sale-delivery-form">
                    <div v-if="modal.record" class="fg-sale-delivery-form__identity">
                        <div><strong>{{ saleLabel(modal.record) }}</strong><span class="fg-table-note">{{ modal.record.sale_header?.holder?.name }}</span></div>
                        <StatusBadge :status="modal.record.status" :formatted-status="modal.record.formatted_status"/>
                    </div>
                    <div class="row g-2">
                        <div class="fg-field-cell col-12 col-sm-6 col-lg-6">
                            <InputSlot hasDiv title="Almacén de salida" :titleClass="[config.forms.classes.title]" isRequired xl="6" lg="6">
                            <template #input>
                                <FgSelect
                                    v-model="form.warehouse"
                                    :options="warehouses"
                                    :clearable="false"
                                    :searchable="warehouses.length > 6"/>
                            </template>
                        </InputSlot>
                        </div>
                        <div class="fg-field-cell col-12 col-sm-6 col-lg-6">
                            <InputDate
                            v-model="form.delivered_at"
                            hasDiv
                            title="Fecha de entrega"
                            :titleClass="[config.forms.classes.title]"
                            xl="6"
                            lg="6"/>
                        </div>
                    </div>

                    <ResponsiveTable class="mt-2 fg-sales-table-scroll">
                        <table role="table" class="fg-record-table fg-record-table--delivery-lines mb-0 fg-table">
                            <thead role="rowgroup">
                                <tr role="row">
                                    <th scope="col" role="columnheader">Producto</th>
                                    <th scope="col" role="columnheader" class="text-end">Pendiente</th>
                                    <th scope="col" role="columnheader" class="text-center">Entregar ahora</th>
                                </tr>
                            </thead>
                            <tbody role="rowgroup">
                                <tr role="row" v-for="item in form.items" :key="item.sale_delivery_item_id">
                                    <td role="cell" class="fg-record-table__identity">
                                        <ProductIdentity :product="item.product"/>
                                    </td>
                                    <td role="cell" class="text-end"><span class="fg-record-table__label" aria-hidden="true">Pendiente</span><div class="fg-record-table__value">
                                        <strong v-text="separatorNumber(item.pending_quantity)"></strong>
                                    </div></td>
                                    <td role="cell"><span class="fg-record-table__label" aria-hidden="true">Entregar ahora</span><div class="fg-record-table__value">
                                        <InputNumber
                                            v-model="item.quantity"
                                            :aria-label="`Entregar ahora: ${item.product.name}`"
                                            :minValue="0"
                                            :maxValue="Number(item.pending_quantity)"
                                            :placeholder="separatorNumber(item.pending_quantity)"/>
                                    </div></td>
                                </tr>
                            </tbody>
                        </table>
                    </ResponsiveTable>

                    <p v-if="hasQuantityAbovePending" class="fg-sales-message fg-sales-message--warning" role="status">
                        La cantidad a entregar supera el saldo pendiente de un producto.
                    </p>
                    <p v-else-if="deliveryQuantity > 0" class="fg-sales-message" role="status">
                        Entregarás {{ separatorNumber(deliveryQuantity) }} {{ deliveryQuantity === 1 ? "unidad" : "unidades" }}.
                        <span v-if="remainingDeliveryQuantity === 1">Quedará {{ separatorNumber(remainingDeliveryQuantity) }} unidad pendiente.</span>
                        <span v-else-if="remainingDeliveryQuantity > 0">Quedarán {{ separatorNumber(remainingDeliveryQuantity) }} unidades pendientes.</span>
                    </p>
                    <InputTextArea
                        v-model="form.observation"
                        hasDiv
                        title="Observación"
                        :titleClass="[config.forms.classes.title]"
                        placeholder="Detalle opcional de la entrega"
                        xl="12"
                        lg="12"/>

                    <section v-if="modal.record?.events?.length" class="fg-sale-delivery-trace mt-2">
                        <h3 class="fg-section-title mb-0">Historial de entregas</h3>
                        <article v-for="event in modal.record.events" :key="event.id" class="fg-sale-delivery-trace__item">
                            <div>
                                <strong>{{ legibleDate(event.delivered_at) }}</strong>
                                <span>{{ event.delivered_by?.name || "Usuario no identificado" }}</span>
                            </div>
                            <div>
                                <strong>{{ separatorNumber(event.total_quantity) }}</strong>
                                <span>{{ event.warehouse?.name || "Almacén no identificado" }}</span>
                            </div>
                            <p v-if="event.observation" v-text="event.observation"></p>
                        </article>
                    </section>
                </div>
                <div class="modal-footer fg-modal__footer">
                    <button type="button" class="fg-button fg-button--secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="button" class="fg-button" :disabled="saving || !canSubmitDelivery" @click="submitDelivery">
                        <i class="fa-solid fa-truck-ramp-box" aria-hidden="true"></i>
                        <span>Registrar entrega</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import {navbarItem} from "@System/Layout/Menu.js";
import InitialPageMixin from "@System/Helpers/InitialPageMixin.js";
import * as Alerts from "@System/Helpers/Alerts.js";
import * as Constants from "@System/Helpers/Constants.js";
import * as DateUtils from "@System/Helpers/DateUtils.js";
import * as Requests from "@System/Helpers/Requests.js";
import * as Utils from "@System/Helpers/Utils.js";
import {createOptionSearch} from "@/Shared/Options.js";
import ProductIdentity from "@System/Components/Generics/ProductIdentity.vue";
import SearchInput from "@System/Components/Generics/SearchInput.vue";

export default {
    components: {ProductIdentity, SearchInput},
    mixins: [InitialPageMixin, createOptionSearch("customers")],
    mounted: async function() {

        navbarItem("menu-parent-sales", {addClass: "open"});
        navbarItem(this.config.entity.page.menu.id, {});
        await this.initializePage();
    },
    data() {
        return {
            optionOrigin: "sales-deliveries",
            initialError: false,
            failedList: null,
            loading: false,
            saving: false,
            records: {
                total: 0,
                data: [],
                links: []
            },
            filters: this.defaultFilters(),
            appliedFilters: null,
            options: {},
            modal: {
                id: Utils.uuid(),
                record: null
            },
            form: this.defaultForm(),
            config: {
                ...Constants.generalConfig,
                entity: {
                    ...Requests.config({entity: "sales"}),
                    page: {
                        title: "Entregas pendientes",
                        active: true,
                        menu: {
                            id: "menu-sales-deliveries"
                        }
                    }
                }
            }
        };
    },
    methods: {
        async initializePage() {
            this.initialLoading = true;
            const ready = await this.loadInitialParams(async () => {
                const loaded = await this.initParams();
                this.initialError = !loaded;
                return loaded;
            });
            if(ready) await this.listDeliveries({});
        },
        defaultFilters() {
            return {
                branch: null,
                warehouse: null,
                holder: null,
                deliveryStatus: null,
                search: ""
            };
        },
        defaultForm() {
            return {
                id: null,
                warehouse: null,
                delivered_at: DateUtils.getCurrentDate("date"),
                observation: "",
                items: []
            };
        },
        async initParams() {

            const response = await Requests.get({
                route: this.config.entity.routes.initParams,
                data: {page: "deliveries"}
            });

            this.options.branches = response.data?.config?.branches;
            this.options.warehouses = response.data?.config?.warehouses;
            this.options.customers = {records: []};
            this.options.saleDeliveries = response.data?.config?.saleDeliveries;

            return Requests.valid({result: response});

        },
        requestFilters() {
            const filters = this.appliedFilters || {};
            return {
                branch_id: filters.branch?.code,
                warehouse_id: filters.warehouse?.code,
                holder_id: filters.holder?.code,
                delivery_status: filters.deliveryStatus?.code,
                search: filters.search
            };
        },
        async listDeliveries({url = null, applyFilters = false}) {

            if(applyFilters || !this.appliedFilters) this.appliedFilters = {...this.filters};

            const filterJson = this.requestFilters();
            let requestUrl = url || this.config.entity.routes.deliveries;
            let requestData = filterJson;

            if(url) {
                const urlObj = new URL(url, window.location.origin);
                Object.entries(filterJson).forEach(([key, value]) => {
                    if(value != null) urlObj.searchParams.set(key, value);
                    else urlObj.searchParams.delete(key);
                });
                requestUrl = `${urlObj.pathname}${urlObj.search}`;
                requestData = {};
            }

            this.loading = true;
            this.failedList = null;

            try {
                this.records = Requests.emptyPagination();
                const response = await Requests.get({route: requestUrl, data: requestData});
                if(response?.bool && Array.isArray(response.data?.data)) this.records = response.data;
                else this.failedList = {url};
            } finally {
                this.loading = false;
            }
        },
        clearFilters() {
            this.filters = this.defaultFilters();
            return Alerts.showFiltersCleared();
        },
        openDeliveryModal(record) {

            const warehouse = this.warehouses.find(item => Number(item.code) === Number(record.warehouse_id)) || null;

            this.modal.record = record;
            this.form = {
                ...this.defaultForm(),
                id: record.id,
                warehouse,
                items: this.pendingItems(record).map(item => ({
                    sale_delivery_item_id: item.id,
                    product: {...item.item, name: item.sale_body?.name || item.item?.name},
                    pending_quantity: Number(item.quantity_pending ?? 0),
                    quantity: Number(item.quantity_pending ?? 0)
                }))
            };

            Alerts.modals({type: "show", id: this.modal.id});

        },
        async submitDelivery() {

            if(!this.canSubmitDelivery) {
                Alerts.generateAlert({type: "warning", msgContent: "Indica al menos una cantidad a entregar."});
                return;
            }

            this.saving = true;
            Alerts.swals({type: "create", entity: "entrega"});

            const payload = {
                warehouse_id: this.form.warehouse?.code,
                delivered_at: this.form.delivered_at,
                observation: this.form.observation,
                items: this.form.items.map(item => ({
                    sale_delivery_item_id: item.sale_delivery_item_id,
                    quantity: item.quantity
                }))
            };

            const response = await Requests.patch({
                route: this.config.entity.routes.deliver,
                id: this.form.id,
                data: payload
            });

            this.saving = false;
            Alerts.swals({show: false});

            if(Requests.valid({result: response})) {
                Alerts.modals({type: "hide", id: this.modal.id});
                Alerts.generateAlert({type: "success", msgContent: response.data?.msg || "Entrega registrada correctamente."});
                await this.listDeliveries({});
            }else {
                Alerts.generateAlert({type: "error", msgContent: response.data?.msg || "No fue posible registrar la entrega."});
            }

        },
        pendingItems(record) {
            return (record?.items || []).filter(item => Number(item.quantity_pending ?? 0) > 0 && item.status !== "delivered");
        },
        deliveryPercent(record) {
            const total = Number(record?.total_quantity ?? 0);
            if(total <= 0) return 0;
            return Math.min(100, Math.max(0, Utils.proportion(record?.delivered_quantity, 100, total)));
        },
        saleLabel(record) {
            return record?.sale_header?.serie_sequential || `Venta #${record?.sale_header_id || record?.id}`;
        },
        legibleDate(date) {
            return DateUtils.legibleFormatDate({dateString: date, type: "datetime"});
        },
        separatorNumber(value) {
            return Utils.separatorNumber(value);
        },
        isDefined({value}) {
            return Utils.isDefined({value});
        }
    },
    computed: {
        breadcrumbTitles() {
            return [{title: "Ventas"}, this.config.entity.page];
        },
        branches() {
            return (this.options?.branches?.records ?? []).map(branch => ({
                code: branch.id,
                label: branch.name,
                data: branch
            }));
        },
        warehouses() {
            return (this.options?.warehouses?.records ?? []).map(warehouse => ({
                code: warehouse.id,
                label: `${warehouse.name} · ${warehouse.branch?.name || "Sin sucursal"}`,
                data: warehouse
            }));
        },
        holders() {
            return (this.options?.customers?.records ?? []).map(holder => ({
                code: holder.id,
                label: `${holder.document_number} - ${holder.name}`,
                data: holder
            }));
        },
        deliveryStatuses() {
            return (this.options?.saleDeliveries?.statuses ?? [])
                .filter(status => ["pending", "partial"].includes(status.code))
                .map(status => ({code: status.code, label: status.label}));
        },
        deliveryQuantity() {
            return Utils.sum(this.form.items.map(item => item.quantity || 0));
        },
        remainingDeliveryQuantity() {
            return Utils.subtract(Utils.sum(this.form.items.map(item => item.pending_quantity)), this.deliveryQuantity);
        },
        hasQuantityAbovePending() {
            return this.form.items.some(item => Utils.compareNumbers(item.quantity, item.pending_quantity) > 0);
        },
        canSubmitDelivery() {
            return Boolean(this.form.warehouse?.code)
                && !this.hasQuantityAbovePending
                && Utils.compareNumbers(this.deliveryQuantity, 0) > 0;
        }
    }
};
</script>
