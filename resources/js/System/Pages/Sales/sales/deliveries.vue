<template>
    <Breadcrumb :list="breadcrumbTitles"/>

    <section class="br-filter-bar br-sales-list__filters">
        <div class="row align-items-end g-2">
            <InputSlot hasDiv title="Sucursal" :titleClass="[config.forms.classes.title]" xl="3" lg="4">
                <template #input>
                    <v-select
                        v-model="filters.branch"
                        :options="branches"
                        :class="config.forms.classes.select2"
                        :clearable="true"
                        :searchable="branches.length > 6"
                        append-to-body
                        placeholder="Todas las sucursales"/>
                </template>
            </InputSlot>

            <InputSlot hasDiv title="Almacén" :titleClass="[config.forms.classes.title]" xl="3" lg="4">
                <template #input>
                    <v-select
                        v-model="filters.warehouse"
                        :options="warehouses"
                        :class="config.forms.classes.select2"
                        :clearable="true"
                        :searchable="warehouses.length > 6"
                        append-to-body
                        placeholder="Todos los almacenes"/>
                </template>
            </InputSlot>

            <InputSlot hasDiv title="Cliente" :titleClass="[config.forms.classes.title]" xl="3" lg="4">
                <template #input>
                    <v-select
                        v-model="filters.holder"
                        :options="holders"
                        :filterable="false"
                        :loading="commercialSelection.customers.loading"
                        :class="config.forms.classes.select2"
                        :clearable="true"
                        append-to-body
                        placeholder="Todos los clientes"
                        @open="openCommercialOptions('customers')"
                        @search="searchCommercialOptions('customers', $event)">
                        <template #selected-option="{ label }">
                            <span class="br-select-selected-text" :title="label">{{ label }}</span>
                        </template>
                        <template #list-footer>
                            <li v-if="commercialSelection.customers.hasMore" class="px-2 py-1">
                                <button type="button" class="btn btn-link btn-sm w-100 text-start" @mousedown.prevent @click.stop="loadMoreCommercialOptions('customers')">Cargar más clientes</button>
                            </li>
                        </template>
                        <template #no-options>Sin clientes para esta búsqueda.</template>
                    </v-select>
                </template>
            </InputSlot>

            <InputSlot hasDiv title="Estado de entrega" :titleClass="[config.forms.classes.title]" xl="3" lg="4">
                <template #input>
                    <v-select
                        v-model="filters.deliveryStatus"
                        :options="deliveryStatuses"
                        :class="config.forms.classes.select2"
                        :clearable="true"
                        :searchable="false"
                        append-to-body
                        placeholder="Todos"/>
                </template>
            </InputSlot>

            <InputText
                v-model="filters.search"
                @enterKeyPressed="listDeliveries({})"
                hasDiv
                title="Búsqueda"
                :titleClass="[config.forms.classes.title]"
                placeholder="Documento, cliente, producto o código"
                xl="7"
                lg="7"/>

            <InputSlot
                hasDiv
                :isInputGroup="false"
                :divInputClass="['br-filter-bar__actions']"
                xl="5"
                lg="5">
                <template #input>
                    <button type="button" class="br-btn br-btn-sm br-btn-search" :disabled="loading" @click="listDeliveries({})">
                        <span>Buscar</span>
                    </button>
                    <button type="button" class="br-btn br-btn-sm br-btn-outline-secondary" :disabled="loading" @click="clearFilters">
                        <i class="fa-solid fa-eraser" aria-hidden="true"></i>
                        <span>Limpiar</span>
                    </button>
                </template>
            </InputSlot>
        </div>
    </section>

    <div class="table-responsive br-entity-table-wrap">
        <table class="table br-entity-table mb-0">
            <thead>
                <tr>
                    <th style="width: 16%;">Venta</th>
                    <th style="width: 20%;">Cliente</th>
                    <th style="width: 18%;">Almacén</th>
                    <th style="width: 24%;">Pendiente</th>
                    <th style="width: 12%;">Estado</th>
                    <th class="text-center" style="width: 10%;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <tr v-if="loading">
                    <td colspan="6" class="text-center py-4"><Loader/></td>
                </tr>
                <template v-else-if="records.total > 0">
                    <tr v-for="record in records.data" :key="record.id">
                        <td>
                            <strong class="br-entity-primary" v-text="saleLabel(record)"></strong>
                            <span class="br-entity-table__meta" v-text="record.sale_header?.formatted_issue_date || ''"></span>
                        </td>
                        <td>
                            <strong class="br-entity-primary" v-text="record.sale_header?.holder?.name || 'Cliente no identificado'"></strong>
                            <span class="br-entity-table__meta" v-text="record.sale_header?.holder?.document_number || 'Sin documento'"></span>
                        </td>
                        <td>
                            <strong class="br-entity-primary" v-text="record.warehouse?.name || 'Por definir'"></strong>
                            <span class="br-entity-table__meta" v-text="record.warehouse?.branch?.name || record.sale_header?.serie?.branch?.name || ''"></span>
                        </td>
                        <td>
                            <div class="br-sale-delivery-progress">
                                <span>
                                    <strong>{{ separatorNumber(record.pending_quantity) }}</strong>
                                    pendiente de {{ separatorNumber(record.total_quantity) }}
                                </span>
                                <div class="br-sale-delivery-progress__bar" aria-hidden="true">
                                    <span :style="{width: `${deliveryPercent(record)}%`}"></span>
                                </div>
                            </div>
                            <div class="br-sale-delivery-products">
                                <span
                                    v-for="item in pendingItems(record).slice(0, 3)"
                                    :key="item.id"
                                    class="br-sale-delivery-products__item">
                                    {{ item.sale_body?.name || item.item?.name }}:
                                    <strong>{{ separatorNumber(item.quantity_pending) }}</strong>
                                </span>
                                <span v-if="pendingItems(record).length > 3" class="br-sale-delivery-products__item is-muted">
                                    +{{ pendingItems(record).length - 3 }} más
                                </span>
                            </div>
                        </td>
                        <td>
                            <StatusBadge :status="record.status" :formatted-status="record.formatted_status"/>
                            <span v-if="record.events?.length" class="br-entity-table__meta mt-1">
                                {{ record.events.length }} movimiento{{ record.events.length === 1 ? "" : "s" }}
                            </span>
                            <span v-if="record.last_delivered_at" class="br-entity-table__meta">
                                Última entrega: {{ legibleDate(record.last_delivered_at) }}
                            </span>
                        </td>
                        <td class="text-center">
                            <button
                                type="button"
                                class="br-icon-action br-icon-action-primary"
                                data-bs-toggle="tooltip"
                                data-bs-placement="top"
                                title="Registrar entrega"
                                :aria-label="`Registrar entrega de ${saleLabel(record)}`"
                                @click="openDeliveryModal(record)">
                                <i class="fa-solid fa-truck-ramp-box" aria-hidden="true"></i>
                            </button>
                        </td>
                    </tr>
                </template>
                <tr v-else>
                    <td colspan="6" class="text-center"><WithoutData type="image"/></td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="d-flex justify-content-center" v-if="!loading && records.total > 0">
        <Paginator :links="records.links" @clickPage="listDeliveries"/>
    </div>

    <div
        :id="modal.id"
        class="modal fade br-entity-modal"
        tabindex="-1"
        data-bs-backdrop="static"
        data-bs-keyboard="false"
        aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header br-modal-header">
                    <div>
                        <span class="br-modal-eyebrow">Ventas</span>
                        <h5 class="modal-title">Registrar entrega</h5>
                    </div>
                    <button type="button" class="br-modal-close" data-bs-dismiss="modal" aria-label="Cerrar">
                        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                    </button>
                </div>
                <div class="modal-body br-entity-modal__body">
                    <div class="row g-2">
                        <InputSlot hasDiv title="Almacén de salida" :titleClass="[config.forms.classes.title]" isRequired xl="6" lg="6">
                            <template #input>
                                <v-select
                                    v-model="form.warehouse"
                                    :options="warehouses"
                                    :class="config.forms.classes.select2"
                                    :clearable="false"
                                    :searchable="warehouses.length > 6"
                                    append-to-body/>
                            </template>
                        </InputSlot>
                        <InputDate
                            v-model="form.delivered_at"
                            hasDiv
                            title="Fecha de entrega"
                            :titleClass="[config.forms.classes.title]"
                            xl="6"
                            lg="6"/>
                    </div>

                    <div class="table-responsive br-entity-table-wrap mt-2">
                        <table class="table br-entity-table mb-0">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th class="text-end">Pendiente</th>
                                    <th class="text-center">Entregar ahora</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in form.items" :key="item.sale_delivery_item_id">
                                    <td>
                                        <strong class="br-entity-primary" v-text="item.name"></strong>
                                        <span class="br-entity-table__meta" v-text="item.code"></span>
                                    </td>
                                    <td class="text-end">
                                        <strong v-text="separatorNumber(item.pending_quantity)"></strong>
                                    </td>
                                    <td>
                                        <InputNumber
                                            v-model="item.quantity"
                                            :minValue="0"
                                            :maxValue="Number(item.pending_quantity)"
                                            :placeholder="separatorNumber(item.pending_quantity)"/>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <InputTextArea
                        v-model="form.observation"
                        hasDiv
                        title="Observación"
                        :titleClass="[config.forms.classes.title]"
                        placeholder="Detalle opcional de la entrega"
                        xl="12"
                        lg="12"/>

                    <section v-if="modal.record?.events?.length" class="br-sale-delivery-trace mt-2">
                        <h6>Historial de entregas</h6>
                        <article v-for="event in modal.record.events" :key="event.id" class="br-sale-delivery-trace__item">
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
                <div class="modal-footer br-entity-modal__footer">
                    <button type="button" class="br-btn br-btn-cancel" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="br-btn br-btn-success" :disabled="saving || !canSubmitDelivery" @click="submitDelivery">
                        <i class="fa-solid fa-truck-ramp-box" aria-hidden="true"></i>
                        <span>Registrar entrega</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import * as Alerts from "@System/Helpers/Alerts.js";
import * as Constants from "@System/Helpers/Constants.js";
import * as DateUtils from "@System/Helpers/DateUtils.js";
import * as Requests from "@System/Helpers/Requests.js";
import * as Utils from "@System/Helpers/Utils.js";
import commercialSelection from "../commercialSelection.js";

export default {
    mixins: [commercialSelection],
    mounted: async function() {

        Utils.navbarItem("menu-parent-sales", {addClass: "open"});
        Utils.navbarItem(this.config.entity.page.menu.id, {});
        Alerts.swals({type: "initParams"});

        const ready = await this.initParams();
        if(ready) {
            Alerts.swals({show: false});
            await this.listDeliveries({});
        }

    },
    data() {
        return {
            commercialSelectionRoute: `${Requests.config({entity: "sales", type: "consult"})}/customer-options`,
            loading: false,
            saving: false,
            records: {
                total: 0,
                data: [],
                links: []
            },
            filters: this.defaultFilters(),
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
                data: {page: "deliveries"},
                showAlert: true
            });

            this.options.branches = response.data?.config?.branches;
            this.options.warehouses = response.data?.config?.warehouses;
            this.options.customers = {records: []};
            this.options.holders = this.options.customers;
            this.options.saleDeliveries = response.data?.config?.saleDeliveries;

            return Requests.valid({result: response});

        },
        requestFilters() {
            return {
                branch_id: this.filters.branch?.code,
                warehouse_id: this.filters.warehouse?.code,
                holder_id: this.filters.holder?.code,
                delivery_status: this.filters.deliveryStatus?.code,
                search: this.filters.search
            };
        },
        async listDeliveries({url = null}) {

            const filterJson = this.requestFilters();
            let requestUrl = url || this.config.entity.routes.deliveries;
            let requestData = filterJson;

            if(url) {
                const urlObj = new URL(url, window.location.origin);
                Object.entries(filterJson).forEach(([key, value]) => {
                    if(this.isDefined({value}) && !urlObj.searchParams.has(key)) urlObj.searchParams.set(key, value);
                });
                requestUrl = `${urlObj.pathname}${urlObj.search}`;
                requestData = {};
            }

            this.loading = true;
            this.records = (await Requests.get({route: requestUrl, data: requestData}))?.data || {total: 0, data: [], links: []};
            this.loading = false;

        },
        clearFilters() {
            this.filters = this.defaultFilters();
            this.listDeliveries({});
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
                    name: item.sale_body?.name || item.item?.name || "Producto",
                    code: [
                        item.item?.internal_code ? `Cód. interno ${item.item.internal_code}` : null,
                        item.item?.barcode ? `Cód. barras ${item.item.barcode}` : null
                    ].filter(Boolean).join(" · "),
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
            return Math.min(100, Math.max(0, (Number(record?.delivered_quantity ?? 0) / total) * 100));
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
            return (this.options?.holders?.records ?? []).map(holder => ({
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
        canSubmitDelivery() {
            return Boolean(this.form.warehouse?.code)
                && this.form.items.some(item => Number(item.quantity ?? 0) > 0);
        }
    }
};
</script>
