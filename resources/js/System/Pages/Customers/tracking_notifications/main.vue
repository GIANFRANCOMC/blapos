<template>
    <Breadcrumb :list="breadcrumbTitles"/>

    <section class="br-entity-page br-tracking-notifications">
        <section class="br-filter-bar">
            <div class="row align-items-end g-2">
                <InputSlot
                    hasDiv
                    title="Estado"
                    :titleClass="[config.forms.classes.title]"
                    xl="3"
                    lg="3">
                    <template #input>
                        <v-select
                            v-model="lists.entity.filters.status"
                            :options="statusOptions"
                            :class="config.forms.classes.select2"
                            :clearable="false"
                            :searchable="false"
                            append-to-body/>
                    </template>
                </InputSlot>

                <InputSlot
                    hasDiv
                    :isInputGroup="false"
                    :divInputClass="['br-filter-bar__actions']"
                    xl="9"
                    lg="9">
                    <template #input>
                        <button
                            type="button"
                            class="br-btn br-btn-sm br-btn-search"
                            :disabled="lists.entity.extras.loading"
                            @click="listEntity({})">
                            <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                            <span>Buscar</span>
                        </button>
                    </template>
                </InputSlot>
            </div>
        </section>

        <div class="table-responsive br-entity-table-wrap">
            <table class="table br-entity-table mb-0">
                <thead>
                    <tr>
                        <th style="width: 26%;">Destinatario</th>
                        <th style="width: 27%;">Mensaje</th>
                        <th style="width: 16%;">Intentos</th>
                        <th style="width: 16%;">Estado</th>
                        <th class="text-center" style="width: 15%;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="lists.entity.extras.loading">
                        <td colspan="5" class="text-center py-4">
                            <Loader/>
                        </td>
                    </tr>
                    <template v-else-if="lists.entity.records.total > 0">
                        <tr v-for="record in lists.entity.records.data" :key="record.id">
                            <td>
                                <strong class="br-entity-primary">{{ customerName(record) }}</strong>
                                <span class="br-entity-table__meta">
                                    <i class="fa-solid fa-envelope" aria-hidden="true"></i>
                                    {{ record.to || "Sin correo" }}
                                </span>
                                <span v-if="customerPhone(record)" class="br-entity-table__meta">
                                    <i class="fa-solid fa-phone" aria-hidden="true"></i>
                                    {{ customerPhone(record) }}
                                </span>
                            </td>
                            <td>
                                <strong class="br-entity-primary">{{ record.subject || "Sin asunto" }}</strong>
                                <span class="br-entity-table__meta">
                                    Creada {{ formatDate(record.created_at) }}
                                </span>
                                <span v-if="record.last_error" class="br-notification-error">
                                    {{ record.last_error }}
                                </span>
                            </td>
                            <td>
                                <span class="br-notification-attempts">
                                    {{ record.attempts || 0 }} / {{ record.max_attempts || 0 }}
                                </span>
                                <span v-if="record.next_attempt_at" class="br-entity-table__meta">
                                    Próximo {{ formatDate(record.next_attempt_at) }}
                                </span>
                            </td>
                            <td>
                                <StatusBadge :status="record.status" :formatted-status="record.formatted_status"/>
                            </td>
                            <td class="text-center">
                                <div class="br-table-actions">
                                    <button
                                        type="button"
                                        class="br-icon-action br-icon-action-info"
                                        data-bs-toggle="tooltip"
                                        data-bs-placement="top"
                                        title="Ver detalle"
                                        :aria-label="`Ver detalle de ${record.subject || 'notificación'}`"
                                        @click="openDetail(record)">
                                        <i class="fa-solid fa-eye" aria-hidden="true"></i>
                                    </button>
                                    <button
                                        v-if="canRetry(record)"
                                        type="button"
                                        class="br-icon-action br-icon-action-retry"
                                        data-bs-toggle="tooltip"
                                        data-bs-placement="top"
                                        title="Reintentar"
                                        :aria-label="`Reintentar ${record.subject || 'notificación'}`"
                                        :disabled="retryingId === record.id"
                                        @click="retryNotification(record)">
                                        <i class="fa-solid fa-rotate-right" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>
                    <tr v-else>
                        <td colspan="5">
                            <WithoutData type="image"/>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <nav v-if="!lists.entity.extras.loading && lists.entity.records.total > 0" class="d-flex justify-content-center mt-3">
            <Paginator :links="lists.entity.records.links" @clickPage="listEntity"/>
        </nav>
    </section>

    <div class="modal fade" :id="detailModalId" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content br-entity-modal">
                <button type="button" class="br-modal-close" data-bs-dismiss="modal" aria-label="Cerrar">
                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                </button>
                <div class="modal-header br-entity-modal__header">
                    <div>
                        <p class="br-entity-modal__eyebrow mb-1">Seguimiento</p>
                        <h5 class="modal-title">Detalle de notificación</h5>
                    </div>
                </div>
                <div class="modal-body br-entity-modal__body">
                    <div class="br-notification-detail">
                        <div>
                            <span>Destinatario</span>
                            <strong>{{ selectedRecord?.to || "Sin correo" }}</strong>
                        </div>
                        <div>
                            <span>Asunto</span>
                            <strong>{{ selectedRecord?.subject || "Sin asunto" }}</strong>
                        </div>
                        <div>
                            <span>Creación</span>
                            <strong>{{ formatDate(selectedRecord?.created_at) }}</strong>
                        </div>
                        <div>
                            <span>Estado</span>
                            <StatusBadge :status="selectedRecord?.status" :formatted-status="selectedRecord?.formatted_status"/>
                        </div>
                    </div>

                    <section class="br-notification-preview">
                        <h6>Contenido enviado</h6>
                        <div v-html="selectedRecord?.body || '<p>Sin contenido.</p>'"></div>
                    </section>
                </div>
                <div class="modal-footer br-entity-modal__footer">
                    <button type="button" class="br-btn br-btn-cancel" data-bs-dismiss="modal">Cerrar</button>
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

const STATUS_OPTIONS = [
    {code: "", label: "Todos los estados"},
    {code: "pending", label: "Pendiente"},
    {code: "sent", label: "Enviada"},
    {code: "failed", label: "Fallida"}
];

export default {
    name: "TrackingNotificationsMain",
    mounted: async function() {
        Utils.navbarItem("menu-parent-customers", {addClass: "open"});
        Utils.navbarItem(this.config.entity.page.menu.id, {});
        Alerts.swals({type: "initParams"});

        const initParams = await this.initParams({});

        if(initParams) {
            Alerts.swals({show: false});
            this.listEntity({});
        }
    },
    data() {
        return {
            detailModalId: Utils.uuid(),
            selectedRecord: null,
            retryingId: null,
            lists: {
                entity: {
                    extras: {
                        loading: false,
                        route: Requests.config({entity: "tracking_notifications", type: "list"})
                    },
                    filters: {
                        status: STATUS_OPTIONS[0]
                    },
                    records: {total: 0, data: [], links: []}
                }
            },
            config: {
                ...Constants.generalConfig,
                entity: {
                    ...Requests.config({entity: "tracking_notifications"}),
                    page: {
                        title: "Notificaciones",
                        active: true,
                        menu: {id: "menu-customers-notifications"}
                    }
                }
            }
        };
    },
    methods: {
        async initParams({}) {
            const initParams = await Requests.get({
                route: this.config.entity.routes.initParams,
                data: {page: "main"},
                showAlert: true
            });

            return Requests.valid({result: initParams});
        },
        async listEntity({url = null}) {
            const filterData = {status: this.lists.entity.filters.status?.code || ""};
            let route = url || this.lists.entity.extras.route;
            let data = filterData;

            if(url) {
                const urlObj = new URL(url, window.location.origin);
                Object.entries(filterData).forEach(([key, value]) => {
                    if(value && !urlObj.searchParams.has(key)) urlObj.searchParams.set(key, value);
                });
                route = `${urlObj.pathname}${urlObj.search}`;
                data = {};
            }

            this.lists.entity.extras.loading = true;
            const result = await Requests.get({
                route,
                data
            });
            this.lists.entity.records = result?.data || {total: 0, data: [], links: []};
            this.lists.entity.extras.loading = false;
        },
        openDetail(record) {
            this.selectedRecord = record;
            Alerts.modals({type: "show", id: this.detailModalId});
        },
        async retryNotification(record) {
            if(this.retryingId) return;

            this.retryingId = record.id;
            Alerts.swals({type: "loading", message: "Preparando reintento"});

            const result = await Requests.patch({
                route: `${this.config.entity.routes.consult}/${record.id}/retry`
            });

            this.retryingId = null;
            Alerts.swals({show: false});

            if(Requests.valid({result})) {
                Alerts.generateAlert({type: "success", msgContent: result.data.msg || "La notificación quedó lista para reintento."});
                this.listEntity({});
                return;
            }

            Alerts.generateAlert({type: "error", messages: [result?.data?.msg || "No se pudo preparar el reintento."]});
        },
        canRetry(record) {
            return record?.status === "failed";
        },
        customerName(record) {
            return record?.formatted_extras_json?.customer?.name || "Cliente no identificado";
        },
        customerPhone(record) {
            return record?.formatted_extras_json?.customer?.phone || "";
        },
        formatDate(value) {
            return Utils.legibleFormatDate({dateString: value, type: "datetime"}) || "Sin fecha";
        }
    },
    computed: {
        breadcrumbTitles() {
            return [{title: "Seguimiento"}, this.config.entity.page];
        },
        statusOptions() {
            return STATUS_OPTIONS;
        }
    },
    watch: {
        "lists.entity.filters.status": function() {
            this.listEntity({});
        }
    }
};
</script>
