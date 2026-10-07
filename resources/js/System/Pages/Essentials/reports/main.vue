<template>
    <Breadcrumb :list="breadcrumbTitles"/>

    <section class="br-reports">
        <FiltersSection
            class="br-reports__parameters"
            :filter-by-value="forms.entity.createUpdate.data.report"
            @update:filterByValue="changeReport"
            :filter-word-value="quickSearch"
            @update:filterWordValue="quickSearch = $event"
            :filter-by-options="reports"
            :search-placeholder="quickSearchPlaceholder"
            :loading="isExporting"
            filter-by-title="Reporte"
            search-title="Búsqueda rápida"
            :show-search-input="reportUsesQuickSearch"
            :show-add-button="false"
            :show-search-button="false"
            :show-download-button="true"
            :download-button-text="primaryActionLabel"
            :download-button-tooltip="primaryActionTooltip"
            :download-button-icon="primaryActionIcon"
            :download-button-class="primaryActionClass"
            :title-class="[config.forms.classes.title]"
            :select-class="config.forms.classes.select2"
            @download="exportReport({})">
            <template #extraFilters>
                <template v-if="selectedReportCode === 'customers'">
                    <InputText v-model="forms.entity.createUpdate.data.customers.document_number" hasDiv title="Número de documento" :titleClass="[config.forms.classes.title]" xl="3" lg="4"/>
                </template>

                <template v-else-if="selectedReportCode === 'users'">
                    <InputText v-model="forms.entity.createUpdate.data.users.document_number" hasDiv title="Número de documento" :titleClass="[config.forms.classes.title]" xl="3" lg="4"/>
                </template>

                <template v-else-if="selectedReportCode === 'items'">
                    <InputText v-model="forms.entity.createUpdate.data.items.description" hasDiv title="Descripción" :titleClass="[config.forms.classes.title]" xl="3" lg="4"/>
                </template>

                <template v-else-if="selectedReportCode === 'sales'">
                    <InputSlot
                        hasDiv
                        title="Tipo"
                        :titleClass="[config.forms.classes.title]"
                        isRequired
                        :textBottomInfo="forms.entity.createUpdate.errors?.type"
                        xl="3"
                        lg="4">
                        <template #input>
                            <v-select
                                v-model="forms.entity.createUpdate.data.sales.type"
                                :options="salesType"
                                :class="config.forms.classes.select2"
                                :clearable="false"
                                :searchable="false"
                                append-to-body/>
                        </template>
                    </InputSlot>

                    <InputMonth
                        v-if="['by_month', 'range_months'].includes(forms.entity.createUpdate.data.sales.type?.code)"
                        v-model="forms.entity.createUpdate.data.sales.start_month"
                        hasDiv
                        title="Mes de"
                        :titleClass="[config.forms.classes.title]"
                        isRequired
                        :textBottomInfo="forms.entity.createUpdate.errors?.start_month"
                        xl="3"
                        lg="4"/>
                    <InputMonth
                        v-if="forms.entity.createUpdate.data.sales.type?.code === 'range_months'"
                        v-model="forms.entity.createUpdate.data.sales.end_month"
                        hasDiv
                        title="Mes al"
                        :titleClass="[config.forms.classes.title]"
                        isRequired
                        :textBottomInfo="forms.entity.createUpdate.errors?.end_month"
                        xl="3"
                        lg="4"/>

                    <InputDate
                        v-if="['by_date', 'range_dates'].includes(forms.entity.createUpdate.data.sales.type?.code)"
                        v-model="forms.entity.createUpdate.data.sales.start_date"
                        hasDiv
                        title="Fecha del"
                        :titleClass="[config.forms.classes.title]"
                        isRequired
                        :textBottomInfo="forms.entity.createUpdate.errors?.start_date"
                        xl="3"
                        lg="4"/>
                    <InputDate
                        v-if="forms.entity.createUpdate.data.sales.type?.code === 'range_dates'"
                        v-model="forms.entity.createUpdate.data.sales.end_date"
                        hasDiv
                        title="Fecha al"
                        :titleClass="[config.forms.classes.title]"
                        isRequired
                        :textBottomInfo="forms.entity.createUpdate.errors?.end_date"
                        xl="3"
                        lg="4"/>
                </template>

                <template v-else-if="selectedReportCode === 'settlements'">
                    <InputSlot
                        hasDiv
                        title="Resumen"
                        :titleClass="[config.forms.classes.title]"
                        isRequired
                        :textBottomInfo="forms.entity.createUpdate.errors?.type"
                        xl="3"
                        lg="4">
                        <template #input>
                            <v-select
                                v-model="forms.entity.createUpdate.data.settlements.type"
                                :options="settlementType"
                                :class="config.forms.classes.select2"
                                :clearable="false"
                                :searchable="false"
                                append-to-body/>
                        </template>
                    </InputSlot>

                    <InputSlot
                        hasDiv
                        title="Alcance"
                        :titleClass="[config.forms.classes.title]"
                        isRequired
                        :textBottomInfo="forms.entity.createUpdate.errors?.scope"
                        xl="3"
                        lg="4">
                        <template #input>
                            <v-select
                                v-model="forms.entity.createUpdate.data.settlements.scope"
                                :options="settlementScope"
                                :class="config.forms.classes.select2"
                                :clearable="false"
                                :searchable="false"
                                append-to-body/>
                        </template>
                    </InputSlot>

                    <InputDate
                        v-model="forms.entity.createUpdate.data.settlements.date_from"
                        hasDiv
                        title="Fecha desde"
                        :titleClass="[config.forms.classes.title]"
                        :textBottomInfo="forms.entity.createUpdate.errors?.date_from"
                        xl="3"
                        lg="4"/>
                    <InputDate
                        v-model="forms.entity.createUpdate.data.settlements.date_to"
                        hasDiv
                        title="Fecha hasta"
                        :titleClass="[config.forms.classes.title]"
                        :textBottomInfo="forms.entity.createUpdate.errors?.date_to"
                        xl="3"
                        lg="4"/>
                </template>

            </template>
        </FiltersSection>

        <section class="br-reports__help">
            <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
            <span>{{ selectedReportHelp }}</span>
        </section>

        <section v-if="selectedReportCode === 'settlements'" class="br-entity-list br-reports__results">
            <div class="table-responsive">
                <table class="table br-entity-table mb-0">
                    <thead class="br-table-header-surface">
                        <tr>
                            <th>Alcance</th>
                            <th>Concepto</th>
                            <th class="text-end">Documentos</th>
                            <th class="text-end">Cantidad</th>
                            <th class="text-end">Base</th>
                            <th class="text-end">Monto</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="settlements.loading">
                            <td colspan="6" class="py-4"><Loader/></td>
                        </tr>
                        <template v-else-if="settlements.records.length">
                            <tr v-for="(record, index) in settlements.records" :key="`${record.scope}-${record.name}-${index}`">
                                <td>
                                    <span class="br-status-label br-status-active">{{ scopeLabel(record.scope) }}</span>
                                </td>
                                <td>
                                    <strong class="br-entity-primary">{{ record.name }}</strong>
                                    <span v-if="record.calculation_type" class="br-entity-table__meta">
                                        {{ settlementCalculationLabel(record) }}
                                    </span>
                                </td>
                                <td class="text-end">{{ numberLabel(record.documents) }}</td>
                                <td class="text-end">{{ settlementQuantity(record) }}</td>
                                <td class="text-end">{{ amountLabel(record.base_amount) }}</td>
                                <td class="text-end fw-bold">{{ amountLabel(record.amount) }}</td>
                            </tr>
                        </template>
                        <tr v-else>
                            <td colspan="6">
                                <WithoutData v-if="settlements.hasConsulted" type="image"/>
                                <span v-else class="br-reports__empty-hint">Consulta un resumen financiero para visualizar resultados.</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </section>
</template>

<script>
import * as Alerts    from "@System/Helpers/Alerts.js";
import * as Constants from "@System/Helpers/Constants.js";
import * as Requests  from "@System/Helpers/Requests.js";
import * as Utils     from "@System/Helpers/Utils.js";

const REPORTS = [
    {code: "customers", label: "Clientes", fileName: "clientes.xlsx", help: "Exporta clientes con documento y nombre según los filtros visibles."},
    {code: "users", label: "Colaboradores", fileName: "colaboradores.xlsx", help: "Exporta colaboradores respetando empresa y permisos de acceso."},
    {code: "items", label: "Catálogo comercial", fileName: "catalogo-comercial.xlsx", help: "Exporta productos, servicios y membresías filtrados por nombre o descripción."},
    {code: "branches", label: "Sucursales", fileName: "sucursales.xlsx", help: "Exporta sucursales registradas para la empresa actual."},
    {code: "sales", label: "Ventas", fileName: "ventas.xlsx", help: "Exporta ventas por mes, rango de meses, fecha o rango de fechas."},
    {code: "settlements", label: "Resumen financiero", help: "Consulta tributos o métodos de pago agrupados por ventas, compras o ambos."}
];

const SALES_TYPE = [
    {code: "by_month", label: "Por mes"},
    {code: "range_months", label: "Entre meses"},
    {code: "by_date", label: "Por fecha"},
    {code: "range_dates", label: "Entre fechas"}
];

const SETTLEMENT_TYPE = [
    {code: "taxes", label: "Tributos"},
    {code: "payments", label: "Métodos de pago"}
];

const SETTLEMENT_SCOPE = [
    {code: "both", label: "Ventas y compras"},
    {code: "sale", label: "Ventas"},
    {code: "purchase", label: "Compras"}
];

export default {
    mounted: async function() {
        Utils.navbarItem(this.config.entity.page.menu.id, {});
        Alerts.swals({type: "initParams"});

        let initParams = await this.initParams({}),
            initOthers = await this.initOthers({});

        if(initParams && initOthers) {
            Alerts.swals({show: false});
        }
    },
    data() {
        return {
            isExporting: false,
            quickSearch: "",
            forms: {
                entity: {
                    createUpdate: {
                        data: {
                            report: null,
                            customers: {document_number: "", name: ""},
                            users: {document_number: "", name: ""},
                            items: {name: "", description: ""},
                            branches: {name: ""},
                            sales: {
                                type: null,
                                start_date: "",
                                end_date: "",
                                start_month: "",
                                end_month: ""
                            },
                            settlements: {
                                type: null,
                                scope: null,
                                date_from: "",
                                date_to: ""
                            }
                        },
                        errors: {}
                    }
                }
            },
            options: {},
            settlements: {
                loading: false,
                hasConsulted: false,
                records: []
            },
            config: {
                ...Constants.generalConfig,
                entity: {
                    ...Requests.config({entity: "reports"}),
                    page: {
                        title: "Reportes",
                        active: true,
                        menu: {id: "menu-parent-workspace"}
                    }
                }
            }
        };
    },
    methods: {
        async initParams({}) {
            let initParams = await Requests.get({route: this.config.entity.routes.initParams, data: {page: "main"}, showAlert: true});
            return Requests.valid({result: initParams});
        },
        async initOthers({}) {
            this.forms.entity.createUpdate.data.report = this.reports[0];
            this.forms.entity.createUpdate.data.sales.type = this.salesType[0];
            this.forms.entity.createUpdate.data.settlements.type = this.settlementType[0];
            this.forms.entity.createUpdate.data.settlements.scope = this.settlementScope[0];
            return true;
        },
        changeReport(report) {
            this.forms.entity.createUpdate.data.report = report;
            this.quickSearch = "";
            this.forms.entity.createUpdate.errors = {};
            this.settlements.hasConsulted = false;
            this.settlements.records = [];
        },
        async exportReport({}) {
            if(this.isExporting) return;

            const report = this.forms.entity.createUpdate.data.report;
            const payload = this.reportPayload(report);
            const validation = this.validateReport({report, payload});

            this.forms.entity.createUpdate.errors = validation.errors;

            if(!validation.bool) {
                Alerts.generateAlert({
                    type: "warning",
                    messages: Object.values(validation.errors).flat()
                });
                return;
            }

            if(report.code === "settlements") {
                await this.consultSettlements(payload);
                return;
            }

            this.isExporting = true;
            Alerts.swals({type: "loading", message: `Preparando ${report.label.toLowerCase()}`});

            const result = await Requests.download({
                route: `${this.config.entity.routes.consult}/${report.code}`,
                data: payload,
                fileName: report.fileName || `${report.code}.xlsx`,
                showAlert: true
            });

            Alerts.swals({show: false});
            this.isExporting = false;

            if(!result.bool) {
                Alerts.generateAlert({
                    type: "error",
                    msgContent: result?.data?.msg || "No se pudo generar el reporte. Reduce el rango o intenta nuevamente."
                });
            }
        },
        reportPayload(report) {
            const form = Utils.cloneJson(this.forms.entity.createUpdate.data[report?.code] || {});
            const quickSearch = this.quickSearch.trim();

            if(quickSearch && ["customers", "users", "branches"].includes(report?.code)) {
                form.name = quickSearch;
            }

            if(quickSearch && report?.code === "items") {
                form.name = quickSearch;
            }

            if(report?.code === "sales") {
                form.type = form?.type?.code;
            }

            if(report?.code === "settlements") {
                form.type = form?.type?.code;
                form.scope = form?.scope?.code;
            }

            return form;
        },
        async consultSettlements(payload) {
            this.isExporting = true;
            this.settlements.loading = true;
            this.settlements.hasConsulted = true;
            Alerts.swals({type: "loading", message: "Consultando resumen financiero"});

            const result = await Requests.get({
                route: `${this.config.entity.routes.consult}/settlements`,
                data: payload,
                showAlert: true
            });

            Alerts.swals({show: false});
            this.isExporting = false;
            this.settlements.loading = false;

            if(Requests.valid({result})) {
                this.settlements.records = result.data?.data || [];
                return;
            }

            this.settlements.records = [];
            Alerts.generateAlert({
                type: "error",
                msgContent: result?.data?.msg || "No se pudo consultar el resumen financiero."
            });
        },
        validateReport({report, payload}) {
            const result = {bool: true, errors: {}};

            if(!report?.code) {
                result.bool = false;
                result.errors.report = ["Selecciona un reporte."];
                return result;
            }

            if(report.code === "settlements") {
                const required = (field, message) => {
                    if(!this.isDefined({value: payload[field]})) {
                        result.bool = false;
                        result.errors[field] = [message];
                    }
                };

                required("type", "Selecciona el resumen.");
                required("scope", "Selecciona el alcance.");

                if(payload.date_from && payload.date_to && payload.date_to < payload.date_from) {
                    result.bool = false;
                    result.errors.date_to = ["La fecha hasta no puede ser menor que la fecha desde."];
                }

                return result;
            }

            if(report.code !== "sales") return result;

            const required = (field, message) => {
                if(!this.isDefined({value: payload[field]})) {
                    result.bool = false;
                    result.errors[field] = [message];
                }
            };

            required("type", "Selecciona el tipo de reporte.");

            if(payload.type === "by_month") required("start_month", "Selecciona el mes.");
            if(payload.type === "range_months") {
                required("start_month", "Selecciona el mes inicial.");
                required("end_month", "Selecciona el mes final.");
            }
            if(payload.type === "by_date") required("start_date", "Selecciona la fecha.");
            if(payload.type === "range_dates") {
                required("start_date", "Selecciona la fecha inicial.");
                required("end_date", "Selecciona la fecha final.");
            }

            return result;
        },
        scopeLabel(scope) {
            return this.settlementScope.find(option => option.code === scope)?.label || scope;
        },
        settlementCalculationLabel(record) {
            const calculation = record.calculation_type === "percentage" ? "Porcentaje" : "Monto fijo";
            const operation = record.operation_type === "subtract" ? "resta" : "suma";

            return `${calculation}, ${operation}`;
        },
        settlementQuantity(record) {
            return record.quantity === undefined || record.quantity === null
                ? "—"
                : this.numberLabel(record.quantity);
        },
        numberLabel(value) {
            return Utils.separatorNumber(value || 0, 0);
        },
        amountLabel(value) {
            if(value === undefined || value === null) return "—";

            return `S/ ${Utils.separatorNumber(value || 0)}`;
        },
        isDefined({value}) {
            return Utils.isDefined({value});
        }
    },
    computed: {
        breadcrumbTitles() {
            return [this.config.entity.page];
        },
        reports() {
            return REPORTS;
        },
        salesType() {
            return SALES_TYPE;
        },
        settlementType() {
            return SETTLEMENT_TYPE;
        },
        settlementScope() {
            return SETTLEMENT_SCOPE;
        },
        selectedReportCode() {
            return this.forms.entity.createUpdate.data.report?.code;
        },
        primaryActionLabel() {
            return this.selectedReportCode === "settlements" ? "Consultar" : "Exportar";
        },
        primaryActionIcon() {
            return this.selectedReportCode === "settlements"
                ? "fa-solid fa-chart-simple"
                : "fa-solid fa-file-excel";
        },
        primaryActionTooltip() {
            return this.selectedReportCode === "settlements"
                ? "Consultar resumen financiero"
                : "Exportar reporte";
        },
        primaryActionClass() {
            return this.selectedReportCode === "settlements"
                ? "br-btn-search"
                : "br-btn-action-export";
        },
        quickSearchPlaceholder() {
            return {
                customers: "Buscar por nombre del cliente",
                users: "Buscar por nombre del colaborador",
                items: "Buscar por nombre del ítem",
                branches: "Buscar por nombre de sucursal",
                sales: "Usa el rango de fechas o meses",
                settlements: "Usa alcance y fechas del resumen"
            }[this.selectedReportCode] || "Buscar";
        },
        reportUsesQuickSearch() {
            return ["customers", "users", "items", "branches"].includes(this.selectedReportCode);
        },
        selectedReportHelp() {
            return this.forms.entity.createUpdate.data.report?.help || "Selecciona un reporte y completa los parámetros necesarios.";
        }
    }
};
</script>
