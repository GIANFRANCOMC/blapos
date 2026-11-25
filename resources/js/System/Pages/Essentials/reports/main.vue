<template>
    <FgPageHeader title="Reportes" description="Configura una consulta o descarga la información autorizada."/>

    <InitialLoadTransition>
        <PageSkeleton v-if="initialLoading"/>
        <div v-else class="fg-page-content fg-reports">
            <form class="fg-filters fg-filter-grid fg-reports__parameters" aria-label="Parámetros del reporte"
                @submit.prevent="exportReport({})">
                <FgField label="Reporte" :error="forms.entity.createUpdate.errors.report">
                    <FgSelect :model-value="forms.entity.createUpdate.data.report" :options="reports"
                        :disabled="isExporting" :searchable="false" :clearable="false" @update:model-value="changeReport"/>
                </FgField>

                <FgField v-if="reportUsesQuickSearch" label="Búsqueda rápida" v-slot="{id}">
                    <div class="fg-field__control">
                        <input :id="id" v-model="quickSearch" class="fg-input" type="search"
                            :placeholder="quickSearchPlaceholder" :disabled="isExporting"/>
                    </div>
                </FgField>

                <FgField v-for="field in reportFields" :key="field.name" :label="field.label" :required="field.required"
                    :error="forms.entity.createUpdate.errors[field.name]" v-slot="{id, invalid, describedBy}">
                    <FgSelect v-if="field.options" v-model="forms.entity.createUpdate.data[selectedReportCode][field.name]"
                        :input-id="id" :options="field.options" :disabled="isExporting" :searchable="false" :clearable="false"/>
                    <div v-else class="fg-field__control" :class="{'fg-field__control--invalid': invalid}">
                        <input :id="id" v-model="forms.entity.createUpdate.data[selectedReportCode][field.name]"
                            class="fg-input" :type="field.type" :disabled="isExporting"
                            :aria-required="field.required || undefined" :aria-invalid="invalid || undefined" :aria-describedby="describedBy"/>
                    </div>
                </FgField>

                <div class="fg-reports__actions">
                    <FgButton type="submit" :loading="isExporting">
                        {{ selectedReportCode === 'settlements' ? 'Consultar' : 'Descargar Excel' }}
                    </FgButton>
                    <FgButton tone="link" :disabled="isExporting" @click="clearFilters">Limpiar</FgButton>
                </div>
            </form>

            <div class="fg-reports__help">
                <h2 class="fg-section-title">{{ forms.entity.createUpdate.data.report?.label || 'Información del reporte' }}</h2>
                <p class="fg-help mb-0">{{ selectedReportHelp }}</p>
            </div>

            <section v-if="selectedReportCode === 'settlements'" aria-labelledby="fg-reports-preview">
                <h2 class="fg-section-title" id="fg-reports-preview">Vista previa del resumen financiero</h2>
                <div class="fg-reports__results" :aria-busy="settlements.loading">
                    <ResponsiveTable>
                        <table class="fg-table fg-record-table fg-reports__table" role="table" aria-labelledby="fg-reports-preview">
                            <thead role="rowgroup">
                                <tr role="row">
                                    <th role="columnheader" scope="col">Concepto</th>
                                    <th role="columnheader" scope="col">Origen</th>
                                    <th role="columnheader" scope="col" class="text-end">Documentos</th>
                                    <th role="columnheader" scope="col" class="text-end">Cantidad</th>
                                    <th role="columnheader" scope="col" class="text-end">Base</th>
                                    <th role="columnheader" scope="col" class="text-end">Monto</th>
                                </tr>
                            </thead>
                            <tbody role="rowgroup">
                                <tr role="row" class="fg-table__state" v-if="settlements.loading">
                                    <td role="cell" colspan="6"><FgListLoading label="Consultando resumen financiero…"/></td>
                                </tr>
                                <template v-else-if="settlements.records.length">
                                    <tr role="row" v-for="(record, index) in settlements.records" :key="[record.scope, record.name, index].join('-')">
                                        <td role="cell" class="fg-record-table__identity">
                                            <strong class="fg-entity-primary">{{ record.name }}</strong>
                                            <span v-if="record.calculation_type" class="fg-entity-table__meta">
                                                {{ settlementCalculationLabel(record) }}
                                            </span>
                                        </td>
                                        <td role="cell">
                                            <span class="fg-record-table__label" aria-hidden="true">Origen</span>
                                            <span class="fg-record-table__value">{{ scopeLabel(record.scope) }}</span>
                                        </td>
                                        <td role="cell" class="text-end">
                                            <span class="fg-record-table__label" aria-hidden="true">Documentos</span>
                                            <span class="fg-record-table__value">{{ numberLabel(record.documents) }}</span>
                                        </td>
                                        <td role="cell" class="text-end">
                                            <span class="fg-record-table__label" aria-hidden="true">Cantidad</span>
                                            <span class="fg-record-table__value">{{ settlementQuantity(record) }}</span>
                                        </td>
                                        <td role="cell" class="text-end">
                                            <span class="fg-record-table__label" aria-hidden="true">Base</span>
                                            <span class="fg-record-table__value">{{ amountLabel(record.base_amount, record) }}</span>
                                        </td>
                                        <td role="cell" class="text-end">
                                            <span class="fg-record-table__label" aria-hidden="true">Monto</span>
                                            <strong class="fg-record-table__value">{{ amountLabel(record.amount, record) }}</strong>
                                        </td>
                                    </tr>
                                </template>
                                <tr role="row" class="fg-table__state" v-else>
                                    <td role="cell" colspan="6">
                                        <FgEmptyState v-if="settlements.error" title="No se pudo consultar el resumen financiero" :description="settlements.error">
                                            <FgButton tone="secondary" @click="consultSettlements(settlements.lastPayload)">Volver a intentar</FgButton>
                                        </FgEmptyState>
                                        <FgEmptyState v-else-if="settlements.hasConsulted" title="No encontramos resultados" description="Prueba con otro periodo u origen."/>
                                        <FgEmptyState v-else title="Consulta el resumen financiero" description="Elige el origen y pulsa Consultar para ver los resultados."/>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </ResponsiveTable>
                    <p v-if="settlements.records.length" class="fg-help mb-0">
                        {{ settlements.records.length }} {{ settlements.records.length === 1 ? 'concepto' : 'conceptos' }}
                    </p>
                </div>
            </section>
        </div>
    </InitialLoadTransition>
</template>



<script>
import {navbarItem} from "@System/Layout/Menu.js";
import ResponsiveTable from "@/Shared/ResponsiveTable.vue";
import FgField from "@System/Components/Figma/FgField.vue";
import FgSelect from "@System/Components/Figma/FgSelect.vue";
import FgPageHeader from "@System/Components/Figma/FgPageHeader.vue";
import FgButton from "@System/Components/Figma/FgButton.vue";
import FgEmptyState from "@System/Components/Figma/FgEmptyState.vue";
import FgListLoading from "@System/Components/Figma/FgListLoading.vue";
import InitialPageMixin from "@System/Helpers/InitialPageMixin.js";
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

const SALES_PERIODS = [
    {name: "start_month", label: "Mes desde", type: "month", modes: ["by_month", "range_months"]},
    {name: "end_month", label: "Mes hasta", type: "month", modes: ["range_months"]},
    {name: "start_date", label: "Fecha desde", type: "date", modes: ["by_date", "range_dates"]},
    {name: "end_date", label: "Fecha hasta", type: "date", modes: ["range_dates"]}
];

function defaultReportFilters() {
    return {
        customers: {document_number: "", name: ""},
        users: {document_number: "", name: ""},
        items: {name: "", description: ""},
        branches: {name: ""},
        sales: {type: SALES_TYPE[0], start_date: "", end_date: "", start_month: "", end_month: ""},
        settlements: {type: SETTLEMENT_TYPE[0], scope: SETTLEMENT_SCOPE[0], date_from: "", date_to: ""}
    };
}

export default {
    components: {FgButton, FgEmptyState, FgField, FgListLoading, FgPageHeader, FgSelect, ResponsiveTable},
    mixins: [InitialPageMixin],
    mounted: async function() {
        navbarItem(this.config.entity.page.menu.id, {});

        const initialized = await this.loadInitialParams(() => this.initParams({}));
        if(initialized) await this.initOthers({});
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
                            ...defaultReportFilters()
                        },
                        errors: {}
                    }
                }
            },
            options: {},
            settlements: {
                loading: false,
                hasConsulted: false,
                records: [],
                error: "",
                lastPayload: null
            },
            config: {
                ...Constants.generalConfig,
                entity: {
                    ...Requests.config({entity: "reports"}),
                    page: {
                        title: "Reportes",
                        active: true,
                        menu: {id: "menu-parent-analytics"}
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
            return true;
        },
        changeReport(report) {
            this.forms.entity.createUpdate.data.report = report;
            this.quickSearch = "";
            this.forms.entity.createUpdate.errors = {};
            this.settlements.hasConsulted = false;
            this.settlements.records = [];
            this.settlements.error = "";
            this.settlements.lastPayload = null;
        },
        clearFilters() {
            if(!this.selectedReportCode || this.isExporting) return;

            this.forms.entity.createUpdate.data[this.selectedReportCode] = defaultReportFilters()[this.selectedReportCode];
            this.forms.entity.createUpdate.errors = {};
            this.quickSearch = "";
            Alerts.showFiltersCleared();
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
            if (this.isExporting) return;

            this.isExporting = true;
            this.settlements.lastPayload = payload;
            this.settlements.error = "";
            this.settlements.loading = true;
            this.settlements.hasConsulted = true;
            this.settlements.records = [];
            try {
                const result = await Requests.get({
                    route: `${this.config.entity.routes.consult}/settlements`,
                    data: payload
                });

                if(Requests.valid({result})) {
                    this.settlements.records = result.data?.data || [];
                    return;
                }

                this.settlements.error = result?.data?.msg || "Revisa tu conexión y vuelve a intentar.";
            } catch {
                this.settlements.error = "Revisa tu conexión y vuelve a intentar.";
            } finally {
                this.isExporting = false;
                this.settlements.loading = false;
            }
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

                required("type", "Elige cómo agrupar el resumen.");
                required("scope", "Selecciona el origen.");

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
        amountLabel(value, currency) {
            if(value === undefined || value === null) return "—";

            return Utils.formatCurrencyAmount(value, currency);
        },
        isDefined({value}) {
            return Utils.isDefined({value});
        }
    },
    computed: {
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
        reportFields() {
            switch(this.selectedReportCode) {
                case "customers":
                case "users":
                    return [{name: "document_number", label: "Número de documento", type: "text"}];
                case "items":
                    return [{name: "description", label: "Descripción", type: "text"}];
                case "sales": {
                    const mode = this.forms.entity.createUpdate.data.sales.type?.code;
                    return [
                        {name: "type", label: "Tipo", options: this.salesType, required: true},
                        ...SALES_PERIODS.filter(field => field.modes.includes(mode))
                            .map(field => ({...field, required: true}))
                    ];
                }
                case "settlements":
                    return [
                        {name: "scope", label: "Origen", options: this.settlementScope, required: true},
                        {name: "type", label: "Agrupar por", options: this.settlementType, required: true},
                        {name: "date_from", label: "Desde", type: "date"},
                        {name: "date_to", label: "Hasta", type: "date"}
                    ];
                default:
                    return [];
            }
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
