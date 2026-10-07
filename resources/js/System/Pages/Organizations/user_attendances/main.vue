<template>
    <Breadcrumb :list="breadcrumbTitles"/>

    <section class="br-entity-list">
        <div class="br-filter-bar mb-3">
            <div class="row g-2 align-items-end w-100">
                <div class="col-xl-3 col-md-6">
                    <label class="form-label">Sucursal</label>
                    <v-select v-model="filters.branch" :options="branchOptions" :clearable="false"/>
                </div>
                <div class="col-xl-4 col-md-6">
                    <label class="form-label">Colaborador</label>
                    <v-select v-model="filters.user" :options="userOptions" :clearable="false" searchable/>
                </div>
                <div class="col-xl-3 col-md-6">
                    <label class="form-label">Semana</label>
                    <input v-model="filters.weekStart" type="date" class="form-control">
                </div>
                <div class="col-xl-2 col-md-6 d-flex gap-2">
                    <button type="button" class="br-btn br-btn-search flex-grow-1" @click="refresh">
                        <span>Consultar</span>
                    </button>
                    <button
                        type="button"
                        class="br-btn br-btn-action-export"
                        data-bs-toggle="tooltip"
                        data-bs-placement="top"
                        title="Exportar nómina"
                        @click="exportPayroll">
                        <i class="fa-solid fa-file-excel" aria-hidden="true"></i>
                        <span class="d-xl-none">Exportar</span>
                    </button>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <div class="br-home-section h-100 p-3">
                    <small class="text-muted">Horas de la semana</small>
                    <strong class="d-block fs-4 text-secondary">{{ summary.total_hours || 0 }} h</strong>
                    <span class="text-muted">{{ summary.week_start || '-' }} al {{ summary.week_end || '-' }}</span>
                </div>
            </div>
            <div class="col-md-4">
                <div class="br-home-section h-100 p-3">
                    <small class="text-muted">Estado actual</small>
                    <strong class="d-block fs-5" :class="activeAttendance ? 'text-success' : 'text-secondary'">
                        {{ activeAttendance ? 'Jornada en curso' : 'Sin jornada abierta' }}
                    </strong>
                    <span class="text-muted">{{ activeAttendance?.branch?.name || 'Selecciona una sede para operar' }}</span>
                </div>
            </div>
            <div class="col-md-4">
                <div class="br-home-section h-100 p-3 d-flex align-items-center justify-content-end gap-2">
                    <button
                        type="button"
                        class="br-btn br-btn-success"
                        :disabled="saving || !canOperate || Boolean(activeAttendance)"
                        @click="checkIn">
                        <i class="fa-solid fa-right-to-bracket" aria-hidden="true"></i>
                        <span>Registrar ingreso</span>
                    </button>
                    <button
                        type="button"
                        class="br-btn br-btn-danger"
                        :disabled="saving || !activeAttendance"
                        @click="checkOut">
                        <i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i>
                        <span>Registrar salida</span>
                    </button>
                </div>
            </div>
        </div>

        <Loader v-if="loading"/>
        <template v-else>
            <div class="table-responsive">
                <table class="table br-entity-table mb-0">
                    <thead>
                        <tr>
                            <th>Colaborador</th>
                            <th>Sucursal</th>
                            <th>Ingreso</th>
                            <th>Salida</th>
                            <th class="text-end">Tiempo</th>
                            <th class="text-end">Ordinarias / Extra</th>
                            <th>Pausas y correcciones</th>
                            <th class="text-center">Estado</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="record in records.data" :key="record.id">
                            <td><strong>{{ record.user?.name }}</strong></td>
                            <td>{{ record.branch?.name }}</td>
                            <td>{{ formatDateTime(record.checked_in_at) }}</td>
                            <td>{{ formatDateTime(record.checked_out_at) }}</td>
                            <td class="text-end fw-semibold">{{ formatMinutes(record.worked_minutes) }}</td>
                            <td class="text-end">
                                <div class="br-attendance-metrics">
                                    <strong>{{ formatMinutes(record.ordinary_minutes) }}</strong>
                                    <small>Extra: {{ formatMinutes(record.overtime_minutes) }}</small>
                                    <small v-if="record.late_minutes" class="text-danger">Tardanza: {{ formatMinutes(record.late_minutes) }}</small>
                                </div>
                            </td>
                            <td>
                                <span class="br-entity-table__meta d-block">
                                    Pausas: {{ formatMinutes(record.break_minutes) }}
                                </span>
                                <span v-if="activeBreak(record)" class="br-status-label br-status-label--warning">Pausa en curso</span>
                                <span v-if="pendingCorrection(record)" class="br-status-label br-status-inactive">Corrección pendiente</span>
                            </td>
                            <td class="text-center">
                                <span class="br-status-label" :class="statusClass(record.status)">
                                    {{ statusLabel(record.status) }}
                                </span>
                            </td>
                            <td class="text-center">
                                <div class="br-table-actions">
                                    <button
                                        v-if="record.status === 'active' && !activeBreak(record)"
                                        type="button"
                                        class="br-icon-action br-icon-action-info"
                                        data-bs-toggle="tooltip"
                                        data-bs-placement="top"
                                        title="Iniciar pausa"
                                        @click="startBreak(record)">
                                        <i class="fa-solid fa-mug-hot" aria-hidden="true"></i>
                                    </button>
                                    <button
                                        v-if="record.status === 'active' && activeBreak(record)"
                                        type="button"
                                        class="br-icon-action br-icon-action-primary"
                                        data-bs-toggle="tooltip"
                                        data-bs-placement="top"
                                        title="Finalizar pausa"
                                        @click="endBreak(record)">
                                        <i class="fa-solid fa-play" aria-hidden="true"></i>
                                    </button>
                                    <button
                                        type="button"
                                        class="br-icon-action br-icon-action-edit"
                                        data-bs-toggle="tooltip"
                                        data-bs-placement="top"
                                        title="Solicitar corrección"
                                        @click="requestCorrection(record)">
                                        <i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i>
                                    </button>
                                    <button
                                        v-if="pendingCorrection(record)"
                                        type="button"
                                        class="br-icon-action br-icon-action-primary"
                                        data-bs-toggle="tooltip"
                                        data-bs-placement="top"
                                        title="Aprobar corrección"
                                        @click="reviewCorrection(record, true)">
                                        <i class="fa-solid fa-check" aria-hidden="true"></i>
                                    </button>
                                    <button
                                        v-if="pendingCorrection(record)"
                                        type="button"
                                        class="br-icon-action br-icon-action-danger"
                                        data-bs-toggle="tooltip"
                                        data-bs-placement="top"
                                        title="Rechazar corrección"
                                        @click="reviewCorrection(record, false)">
                                        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <WithoutData v-if="!records.data.length"/>
            <Paginator :links="records.links" @clickPage="getRecords"/>
        </template>
    </section>
</template>

<script>
import * as Alerts from "@System/Helpers/Alerts.js";
import * as Requests from "@System/Helpers/Requests.js";
import * as Utils from "@System/Helpers/Utils.js";

export default {
    data() {
        return {
            config: Requests.config({entity: "user_attendances"}),
            options: {branches: [], users: [], statuses: []},
            filters: {branch: null, user: null, weekStart: this.currentMonday()},
            records: {data: [], links: []},
            summary: {},
            loading: false,
            saving: false
        };
    },
    computed: {
        breadcrumbTitles() {
            return [{title: "Gestión de colaboradores"}, {title: "Asistencia del personal", active: true}];
        },
        branchOptions() {
            return this.options.branches.map(record => ({...record, label: record.name}));
        },
        userOptions() {
            return this.options.users.map(record => ({...record, label: record.name}));
        },
        activeAttendance() {
            return this.records.data.find(record => record.status === "active" && record.user_id === this.filters.user?.id) || null;
        },
        canOperate() {
            return Boolean(this.filters.branch && this.filters.user);
        }
    },
    mounted() {
        Utils.navbarItem("menu-parent-infrastructure", {addClass: "open"});
        Utils.navbarItem("menu-user-attendances", {addClass: "active"});
        this.initParams();
    },
    methods: {
        currentMonday() {
            const date = new Date();
            const day = date.getDay() || 7;
            date.setDate(date.getDate() - day + 1);
            return date.toISOString().slice(0, 10);
        },
        async initParams() {
            this.loading = true;
            const result = await Requests.get({route: this.config.routes.initParams});
            this.loading = false;
            if(!Requests.valid({result})) return;

            this.options = result.data.config;
            this.filters.branch = this.branchOptions[0] || null;
            this.filters.user = this.userOptions[0] || null;
            await this.refresh();
        },
        async refresh() {
            await Promise.all([this.getRecords(), this.getSummary()]);
        },
        async getRecords(url = null) {
            this.loading = true;
            const result = await Requests.get({
                route: url || this.config.routes.list,
                data: {
                    branch_id: this.filters.branch?.id,
                    user_id: this.filters.user?.id,
                    date_from: this.filters.weekStart
                }
            });
            this.loading = false;
            if(Requests.valid({result})) this.records = result.data.data;
        },
        async getSummary() {
            if(!this.filters.user) return;
            const result = await Requests.get({
                route: this.config.routes.weekly,
                data: {
                    user_id: this.filters.user.id,
                    branch_id: this.filters.branch?.id,
                    week_start: this.filters.weekStart
                }
            });
            if(Requests.valid({result})) this.summary = result.data.summary;
        },
        async checkIn() {
            if(!this.canOperate) return;
            this.saving = true;
            Alerts.loading?.({message: "Registrando ingreso"});
            const result = await Requests.post({
                route: this.config.routes.checkIn,
                data: {branch_id: this.filters.branch.id, user_id: this.filters.user.id}
            });
            this.finishAction(result);
        },
        async checkOut() {
            if(!this.activeAttendance) return;
            this.saving = true;
            Alerts.loading?.({message: "Registrando salida"});
            const result = await Requests.patch({
                route: this.config.routes.checkOut,
                data: {branch_id: this.activeAttendance.branch_id, user_id: this.filters.user.id}
            });
            this.finishAction(result);
        },
        async startBreak(record) {
            const confirmation = await Swal.fire({
                title: "Iniciar pausa",
                input: "text",
                inputLabel: "Motivo",
                inputPlaceholder: "Almuerzo, descanso, gestión interna...",
                showCancelButton: true,
                buttonsStyling: false,
                confirmButtonText: "Iniciar pausa",
                cancelButtonText: "Cancelar",
                customClass: {
                    container: "br-swal-backdrop",
                    popup: "br-swal-alert br-swal-alert--question",
                    confirmButton: "br-btn br-swal-alert__confirm br-swal-alert__confirm--question",
                    cancelButton: "br-btn br-btn-cancel ms-2"
                }
            });

            if(!confirmation.isConfirmed) return;

            this.saving = true;
            Alerts.swals({type: "loading", message: "Registrando pausa"});
            const result = await Requests.post({
                route: `${this.config.routes.store}/${record.id}/breaks`,
                data: {reason: confirmation.value || ""}
            });
            this.finishAction(result);
        },
        async endBreak(record) {
            this.saving = true;
            Alerts.swals({type: "loading", message: "Finalizando pausa"});
            const result = await Requests.patch({
                route: `${this.config.routes.store}/${record.id}/breaks/end`
            });
            this.finishAction(result);
        },
        async requestCorrection(record) {
            const confirmation = await Swal.fire({
                title: "Solicitar corrección",
                html: `
                    <p class="mb-2">Indica los horarios corregidos y el motivo. La corrección queda pendiente de revisión.</p>
                    <input id="brCorrectionIn" class="swal2-input" type="datetime-local" value="${this.datetimeInputValue(record.checked_in_at)}">
                    <input id="brCorrectionOut" class="swal2-input" type="datetime-local" value="${this.datetimeInputValue(record.checked_out_at)}">
                    <textarea id="brCorrectionReason" class="swal2-textarea" placeholder="Motivo de la corrección"></textarea>
                `,
                showCancelButton: true,
                buttonsStyling: false,
                confirmButtonText: "Solicitar",
                cancelButtonText: "Cancelar",
                customClass: {
                    container: "br-swal-backdrop",
                    popup: "br-swal-alert br-swal-alert--question",
                    confirmButton: "br-btn br-swal-alert__confirm br-swal-alert__confirm--question",
                    cancelButton: "br-btn br-btn-cancel ms-2"
                },
                preConfirm: () => {
                    const reason = document.getElementById("brCorrectionReason")?.value?.trim();
                    if(!reason) {
                        Swal.showValidationMessage("Ingresa el motivo de la corrección.");
                        return false;
                    }

                    return {
                        checked_in_at: document.getElementById("brCorrectionIn")?.value || null,
                        checked_out_at: document.getElementById("brCorrectionOut")?.value || null,
                        reason
                    };
                }
            });

            if(!confirmation.isConfirmed) return;

            this.saving = true;
            Alerts.swals({type: "loading", message: "Registrando corrección"});
            const result = await Requests.post({
                route: `${this.config.routes.store}/${record.id}/corrections`,
                data: confirmation.value
            });
            this.finishAction(result);
        },
        async reviewCorrection(record, approve) {
            const correction = this.pendingCorrection(record);
            if(!correction) return;

            const confirmation = await Swal.fire({
                title: approve ? "Aprobar corrección" : "Rechazar corrección",
                input: "textarea",
                inputLabel: "Nota de revisión",
                inputPlaceholder: approve ? "Observación opcional" : "Indica el motivo del rechazo",
                showCancelButton: true,
                buttonsStyling: false,
                confirmButtonText: approve ? "Aprobar" : "Rechazar",
                cancelButtonText: "Cancelar",
                customClass: {
                    container: "br-swal-backdrop",
                    popup: approve ? "br-swal-alert br-swal-alert--question" : "br-swal-alert br-swal-alert--warning",
                    confirmButton: approve ? "br-btn br-swal-alert__confirm br-swal-alert__confirm--question" : "br-btn br-swal-alert__confirm br-swal-alert__confirm--warning",
                    cancelButton: "br-btn br-btn-cancel ms-2"
                },
                preConfirm: value => {
                    if(!approve && !value?.trim()) {
                        Swal.showValidationMessage("Indica el motivo del rechazo.");
                        return false;
                    }

                    return value || "";
                }
            });

            if(!confirmation.isConfirmed) return;

            this.saving = true;
            Alerts.swals({type: "loading", message: approve ? "Aprobando corrección" : "Rechazando corrección"});
            const result = await Requests.patch({
                route: `${this.config.routes.store}/corrections/${correction.id}`,
                data: {
                    approve,
                    note: confirmation.value || null
                }
            });
            this.finishAction(result);
        },
        exportPayroll() {
            const url = new URL(this.config.routes.export, window.location.origin);
            url.searchParams.set("branch_id", this.filters.branch?.id || "");
            url.searchParams.set("user_id", this.filters.user?.id || "");
            url.searchParams.set("date_from", this.filters.weekStart || "");
            window.location.href = `${url.pathname}${url.search}`;
        },
        async finishAction(result) {
            this.saving = false;
            Alerts.close?.();
            window.Swal?.close?.();
            Alerts.toastrs({
                type: Requests.valid({result}) ? "success" : "error",
                subtitle: result.data?.msg || "No fue posible completar la acción."
            });
            if(Requests.valid({result})) await this.refresh();
        },
        formatDateTime(value) {
            return value ? new Intl.DateTimeFormat("es-PE", {dateStyle: "short", timeStyle: "short"}).format(new Date(value)) : "-";
        },
        formatMinutes(value) {
            const minutes = Number(value || 0);
            return `${Math.floor(minutes / 60)} h ${minutes % 60} min`;
        },
        statusLabel(status) {
            return {active: "En curso", finalized: "Finalizada", canceled: "Cancelada"}[status] || status;
        },
        statusClass(status) {
            return {active: "br-status-active", finalized: "br-status-inactive", canceled: "br-status-inactive"}[status];
        },
        activeBreak(record) {
            return (record.breaks || []).find(item => item.status === "active") || null;
        },
        pendingCorrection(record) {
            return (record.corrections || []).find(item => item.status === "pending") || null;
        },
        datetimeInputValue(value) {
            if(!value) return "";
            const date = new Date(value);
            date.setMinutes(date.getMinutes() - date.getTimezoneOffset());
            return date.toISOString().slice(0, 16);
        }
    }
};
</script>
