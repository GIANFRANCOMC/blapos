<template>
    <Breadcrumb :list="breadcrumbTitles"/>

    <MasterDataSkeleton v-if="initialLoading && skeletonEnabled"/>

    <section v-else class="br-master-data">
        <CatalogFormTabs
            :model-value="selectedResourceCode"
            :tabs="resourceTabs"
            id-prefix="master-data"
            shared-controls-id="master-data-panel"
            variant="descriptive"
            label="Tipos de maestros internos"
            @update:modelValue="selectResource"/>

        <main
            id="master-data-panel"
            class="br-master-data__workspace"
            role="tabpanel"
            :aria-labelledby="`master-data-tab-trigger-${selectedResourceCode}`">
            <header class="br-master-data__header">
                <div>
                    <h1>{{ selectedResource.label }}</h1>
                    <p>{{ selectedResource.help }}</p>
                </div>
                <button v-if="selectedResourceCode !== 'automatic'" type="button" class="br-btn br-btn-action-create" @click="startCreate">
                    <span>Agregar {{ selectedResource.singular }}</span>
                </button>
            </header>

            <section v-if="selectedResourceCode === 'automatic'" class="br-master-data-automatic" aria-label="Datos automáticos">
                <div class="br-master-data__records-head">
                    <div class="br-master-data-search">
                        <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                        <SearchInput v-model.trim="searchTerm" placeholder="Buscar dato automático" aria-label="Buscar dato automático"/>
                    </div>
                    <span>{{ filteredRecords.length }} dato{{ filteredRecords.length === 1 ? "" : "s" }}</span>
                </div>
                <Loader v-if="loading"/>
                <WithoutData v-else-if="filteredRecords.length === 0" type="image" text="Sin datos automáticos" description="No hay datos que coincidan con la búsqueda."/>
                <div v-else class="br-master-data-automatic__list">
                    <article v-for="record in filteredRecords" :key="record.code" class="br-master-data-automatic__item">
                        <div class="br-master-data-automatic__detail">
                            <strong>{{ record.name }}</strong>
                            <span>{{ record.description }}</span>
                            <small>Condición: {{ record.condition_label }} · Origen: {{ record.source }}</small>
                        </div>
                        <div class="br-master-data-automatic__value">
                            <StatusBadge :status="record.value ? 'active' : 'inactive'" :formatted-status="record.value ? 'Activo' : 'Inactivo'"/>
                            <small>Valor: {{ record.value ? "Sí" : "No" }}</small>
                        </div>
                    </article>
                </div>
            </section>

            <div v-else class="br-master-data__grid">
                <section class="br-entity-list br-master-data__records">
                    <div class="br-master-data__records-head">
                        <div class="br-master-data-search">
                            <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                            <SearchInput v-model.trim="searchTerm" placeholder="Buscar registro" aria-label="Buscar registro"/>
                        </div>
                        <span>{{ filteredRecords.length }} registro{{ filteredRecords.length === 1 ? "" : "s" }}</span>
                    </div>

                    <Loader v-if="loading"/>
                    <WithoutData v-else-if="filteredRecords.length === 0" type="image" text="Sin registros disponibles" description="Agrega un registro o prueba otra búsqueda."/>
                    <div v-else class="br-master-data-list">
                        <article
                            v-for="record in filteredRecords"
                            :key="record.id"
                            :class="['br-master-data-row', {'is-selected': form.id === record.id}]">
                            <div class="br-master-data-row__body">
                                <strong>{{ recordTitle(record) }}</strong>
                                <span>{{ recordSubtitle(record) }}</span>
                                <small>{{ recordMeta(record) }}</small>
                            </div>
                            <div class="br-master-data-row__actions">
                                <StatusBadge :status="record.status" :formatted-status="statusLabel(record.status)"/>
                                <button type="button" class="br-icon-action br-icon-action-edit" data-bs-toggle="tooltip" title="Editar" @click="startEdit(record)">
                                    <i class="fa-solid fa-pen" aria-hidden="true"></i>
                                </button>
                            </div>
                        </article>
                    </div>
                </section>

                <section class="br-master-data-form">
                    <header>
                        <div>
                            <h2>{{ isEditing ? recordTitle(form) : `Agregar ${selectedResource.singular}` }}</h2>
                        </div>
                        <button v-if="isEditing" type="button" class="br-btn br-btn-sm br-btn-cancel" @click="startCreate">
                            Limpiar
                        </button>
                    </header>

                    <div class="br-master-data-form__notice">
                        <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                        <span>{{ selectedResource.notice }}</span>
                    </div>

                    <div class="row g-3">
                        <template v-for="field in activeFields" :key="field.key">
                            <div :class="field.wrapperClass || 'form-group col-xl-6 col-lg-6 col-md-12 col-sm-12'">
                                <label class="form-label fw-semibold">{{ field.label }}<span v-if="field.required" class="text-danger ms-1">*</span></label>

                                <template v-if="field.type === 'select'">
                                    <v-select
                                        v-model="form[field.key]"
                                        :options="field.options"
                                        :clearable="false"
                                        :searchable="field.searchable ?? false"
                                        :class="config.forms.classes.select2">
                                        <template #no-options>Sin opciones disponibles</template>
                                    </v-select>
                                </template>

                                <template v-else-if="field.type === 'checkbox'">
                                    <label class="br-master-data-check">
                                        <input v-model="form[field.key]" type="checkbox" class="form-check-input">
                                        <span>{{ field.help }}</span>
                                    </label>
                                </template>

                                <template v-else-if="field.type === 'textarea'">
                                    <textarea v-model.trim="form[field.key]" class="form-control" rows="3" :maxlength="field.maxlength || 500"></textarea>
                                </template>

                                <template v-else-if="field.type === 'file'">
                                    <input ref="imageInput" class="form-control" type="file" accept="image/png,image/jpeg,image/webp" @change="onImageChange">
                                    <small v-if="form.image_path" class="br-master-data-help">Imagen actual: {{ form.image_path }}</small>
                                </template>

                                <template v-else>
                                    <input
                                        v-model.trim="form[field.key]"
                                        class="form-control"
                                        :type="field.type || 'text'"
                                        :maxlength="field.maxlength || 255"
                                        :step="field.step || undefined"
                                        :disabled="field.disabled?.(form) || false">
                                </template>

                                <small class="br-form-error">{{ firstError(field.key) }}</small>
                                <small v-if="field.help && field.type !== 'checkbox'" class="br-master-data-help">{{ field.help }}</small>
                            </div>
                        </template>
                    </div>

                    <section v-if="isEditing && statusCode === 'inactive'" class="br-master-data-warning">
                        <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
                        <div>
                            <strong>Revisa el impacto antes de inactivar</strong>
                            <span>{{ selectedResource.deactivationImpact }}</span>
                        </div>
                    </section>

                    <footer>
                        <button type="button" class="br-btn br-btn-cancel" @click="startCreate">Cancelar</button>
                        <button type="button" :class="['br-btn', isEditing ? 'br-btn-action-update' : 'br-btn-action-create']" :disabled="saving" @click="saveRecord">
                            <span>{{ saving ? "Guardando..." : `Guardar ${selectedResource.singular.toLocaleLowerCase('es-PE')}` }}</span>
                        </button>
                    </footer>
                </section>
            </div>
        </main>
    </section>
</template>

<script>
import * as Alerts from "@System/Helpers/Alerts.js";
import * as Constants from "@System/Helpers/Constants.js";
import * as Requests from "@System/Helpers/Requests.js";
import * as Utils from "@System/Helpers/Utils.js";
import {createInitialLoad} from "@System/Helpers/InitialLoad.js";
import CatalogFormTabs from "@System/Components/Catalogs/CatalogFormTabs.vue";
import SearchInput from "@System/Components/Generics/SearchInput.vue";
import MasterDataSkeleton from "./MasterDataSkeleton.vue";

const initialLoad = createInitialLoad({skeleton: true});

const OPTION = {
    statuses: [{code: "active", label: "Activo"}, {code: "inactive", label: "Inactivo"}],
    scopesSalePurchase: [{code: "sale", label: "Ventas"}, {code: "purchase", label: "Compras"}],
    scopesBoth: [{code: "sale", label: "Ventas"}, {code: "purchase", label: "Compras"}, {code: "both", label: "Ventas y compras"}],
    calculationTypes: [{code: "percentage", label: "Porcentaje"}, {code: "fixed", label: "Monto fijo"}],
    operationTypes: [{code: "addition", label: "Suma"}, {code: "subtraction", label: "Resta"}],
    valueTypes: [
        {code: "string", label: "Texto"},
        {code: "boolean", label: "Booleano"},
        {code: "integer", label: "Entero"},
        {code: "decimal", label: "Decimal"},
        {code: "json", label: "JSON"}
    ]
};

const RESOURCES = [
    {
        code: "automatic",
        label: "Automáticos",
        icon: "fa-solid fa-bolt",
        shortHelp: "Valores condicionados",
        help: "Valores de solo lectura calculados a partir de condiciones reales de la empresa.",
        typeLabel: "Valor calculado",
        scopeLabel: "Empresa actual",
        impact: "Controla funciones dependientes de módulos activos.",
        fields: []
    },
    {
        code: "company-settings",
        label: "Configuraciones",
        singular: "configuración",
        icon: "fa-solid fa-sliders",
        shortHelp: "Políticas y parámetros",
        help: "Administra políticas tipadas por grupo y clave. No almacena secretos ni credenciales.",
        typeLabel: "Clave tipada",
        scopeLabel: "Empresa actual",
        impact: "Afecta reglas de negocio, prefijos, caja, inventario y módulos dependientes.",
        notice: "Describe siempre qué hace la clave y qué módulo consume el valor.",
        deactivationImpact: "La clave dejará de ser usada por consumidores que lean solo configuraciones activas.",
        fields: [
            {key: "group", label: "Grupo", required: true, maxlength: 255},
            {key: "key", label: "Clave", required: true, maxlength: 255},
            {key: "value_type", label: "Tipo de valor", type: "select", options: OPTION.valueTypes, required: true},
            {key: "value", label: "Valor", maxlength: 500, wrapperClass: "form-group col-xl-12 col-lg-12 col-md-12 col-sm-12"},
            {key: "description", label: "Descripción e impacto", type: "textarea", maxlength: 500, wrapperClass: "form-group col-12"},
            {key: "status", label: "Estado", type: "select", options: OPTION.statuses, required: true}
        ]
    },
    {
        code: "taxes",
        label: "Tributos",
        singular: "tributo",
        icon: "fa-solid fa-receipt",
        shortHelp: "IGV, ICBP y cargos",
        help: "Configura tributos por alcance de venta o compra, con cálculo porcentual o fijo.",
        typeLabel: "Tributo financiero",
        scopeLabel: "Ventas o compras",
        impact: "Afecta totales, trazabilidad contable, POS, compras y reportes financieros.",
        notice: "Los tributos obligatorios se aplican automáticamente; los opcionales se muestran como impuestos extras.",
        deactivationImpact: "El tributo ya no se calculará en documentos nuevos, aunque los documentos históricos conservan su foto.",
        fields: [
            {key: "code", label: "Código", required: true, maxlength: 30},
            {key: "name", label: "Nombre", required: true, maxlength: 255},
            {key: "scope", label: "Alcance", type: "select", options: OPTION.scopesSalePurchase, required: true},
            {key: "rate", label: "Valor", type: "number", step: "0.0001", required: true},
            {key: "calculation_type", label: "Tipo de cálculo", type: "select", options: OPTION.calculationTypes, required: true},
            {key: "operation_type", label: "Operación", type: "select", options: OPTION.operationTypes, required: true},
            {key: "min_apply_quantity", label: "Cantidad mínima", type: "number", disabled: form => form.calculation_type?.code === "percentage"},
            {key: "max_apply_quantity", label: "Cantidad máxima", type: "number", disabled: form => form.calculation_type?.code === "percentage"},
            {key: "is_required", label: "Obligatorio", type: "checkbox", help: "Se aplica siempre en el alcance configurado."},
            {key: "is_default", label: "Predeterminado", type: "checkbox", help: "Prioriza el tributo en listados y resúmenes."},
            {key: "description", label: "Descripción e impacto", type: "textarea", maxlength: 500, wrapperClass: "form-group col-12"},
            {key: "status", label: "Estado", type: "select", options: OPTION.statuses, required: true}
        ]
    },
    {
        code: "payment-methods",
        label: "Métodos de pago",
        singular: "método de pago",
        icon: "fa-solid fa-wallet",
        shortHelp: "Efectivo, tarjetas, billeteras",
        help: "Administra medios de pago para ventas y compras, incluyendo referencia e imagen visible.",
        typeLabel: "Medio de pago",
        scopeLabel: "Ventas, compras o ambos",
        impact: "Afecta caja, ventas, compras, arqueos y reportes de liquidación.",
        notice: "Si requiere referencia, ventas y compras deben registrar el comprobante o código asociado.",
        deactivationImpact: "El método dejará de estar disponible en nuevos pagos; los documentos históricos mantienen su información.",
        fields: [
            {key: "code", label: "Código", required: true, maxlength: 30},
            {key: "name", label: "Nombre", required: true, maxlength: 255},
            {key: "sunat_code", label: "Código SUNAT", maxlength: 10},
            {key: "scope", label: "Alcance", type: "select", options: OPTION.scopesBoth, required: true},
            {key: "requires_reference", label: "Requiere referencia", type: "checkbox", help: "Solicita operación, voucher o número externo."},
            {key: "is_default", label: "Predeterminado", type: "checkbox", help: "Se sugiere como medio inicial cuando aplica."},
            {key: "image", label: "Imagen", type: "file", wrapperClass: "form-group col-xl-12 col-lg-12 col-md-12 col-sm-12"},
            {key: "status", label: "Estado", type: "select", options: OPTION.statuses, required: true}
        ]
    },
    {
        code: "identity-documents",
        label: "Documentos de identidad",
        singular: "documento",
        icon: "fa-solid fa-id-card",
        shortHelp: "DNI, RUC, CE",
        help: "Configura documentos usados por clientes, colaboradores, empresa y libro de reclamaciones.",
        typeLabel: "Maestro estructural",
        scopeLabel: "Empresa actual",
        impact: "Afecta validaciones de personas, empresas y consultas externas.",
        notice: "No inactives documentos usados en clientes, usuarios o empresa.",
        deactivationImpact: "El backend bloqueará la inactivación si el documento está referenciado.",
        fields: [
            {key: "code", label: "Código", required: true, maxlength: 50},
            {key: "name", label: "Nombre", required: true, maxlength: 100},
            {key: "min_length", label: "Longitud mínima", type: "number", required: true},
            {key: "max_length", label: "Longitud máxima", type: "number", required: true},
            {key: "is_searchable", label: "Permite búsqueda externa", type: "checkbox", help: "Habilita consulta por servicios externos cuando exista integración."},
            {key: "status", label: "Estado", type: "select", options: OPTION.statuses, required: true}
        ]
    },
    {
        code: "document-types",
        label: "Tipos de comprobante",
        singular: "tipo de comprobante",
        icon: "fa-solid fa-file-invoice",
        shortHelp: "Boleta, factura, notas",
        help: "Administra comprobantes comerciales usados por series, ventas y compras.",
        typeLabel: "Maestro estructural",
        scopeLabel: "Empresa actual",
        impact: "Afecta series documentales, emisión y correlativos.",
        notice: "Inactivar un comprobante usado por series puede bloquear operaciones; el backend valida referencias.",
        deactivationImpact: "No estará disponible para nuevas series o documentos si no tiene bloqueo por referencia.",
        fields: [
            {key: "code", label: "Código", required: true, maxlength: 50},
            {key: "name", label: "Nombre", required: true, maxlength: 100},
            {key: "status", label: "Estado", type: "select", options: OPTION.statuses, required: true}
        ]
    },
    {
        code: "currencies",
        label: "Monedas",
        singular: "moneda",
        icon: "fa-solid fa-coins",
        shortHelp: "Signo y nombre",
        help: "Define monedas disponibles para empresa, catálogo, ventas, compras y activos.",
        typeLabel: "Maestro financiero",
        scopeLabel: "Empresa actual",
        impact: "Afecta importes, reportes, compras, ventas y catálogo comercial.",
        notice: "No inactives monedas usadas por productos, compras, ventas o empresa.",
        deactivationImpact: "El backend bloqueará la inactivación si la moneda está referenciada.",
        fields: [
            {key: "code", label: "Código", required: true, maxlength: 10},
            {key: "sign", label: "Signo", required: true, maxlength: 10},
            {key: "singular_name", label: "Nombre singular", required: true, maxlength: 50},
            {key: "plural_name", label: "Nombre plural", required: true, maxlength: 50},
            {key: "status", label: "Estado", type: "select", options: OPTION.statuses, required: true}
        ]
    }
];

export default {
    components: {CatalogFormTabs, SearchInput, MasterDataSkeleton},
    data() {
        return {
            resources: RESOURCES,
            selectedResourceCode: "company-settings",
            records: [],
            form: {},
            originalRecord: null,
            errors: {},
            loading: false,
            skeletonEnabled: initialLoad.skeleton,
            initialLoading: true,
            saving: false,
            searchTerm: "",
            imageFile: null,
            config: {
                ...Constants.generalConfig,
                entity: {
                    page: {
                        title: "Maestros internos",
                        active: true,
                        menu: {id: "menu-configuration-master-data"}
                    }
                }
            }
        };
    },
    async mounted() {
        Utils.navbarItem("menu-parent-configuration", {addClass: "open"});
        Utils.navbarItem(this.config.entity.page.menu.id, {});
        this.startCreate();
        initialLoad.start();

        try {
            await this.loadRecords();
        } finally {
            initialLoad.finish();
            this.initialLoading = false;
            this.$nextTick(() => Alerts.tooltips({time: 250}));
        }
    },
    computed: {
        resourceTabs() {
            return this.resources.map(resource => ({
                id: resource.code,
                label: resource.label.toLocaleUpperCase("es-PE"),
                description: resource.shortHelp,
                icon: resource.icon
            }));
        },
        selectedResource() {
            return this.resources.find(resource => resource.code === this.selectedResourceCode) || this.resources[0];
        },
        activeFields() {
            return this.selectedResource.fields;
        },
        filteredRecords() {
            const term = this.searchTerm.toLowerCase();
            if(!term) return this.records;

            return this.records.filter(record => JSON.stringify(record).toLowerCase().includes(term));
        },
        isEditing() {
            return Boolean(this.form?.id);
        },
        statusCode() {
            return this.form.status?.code || this.form.status;
        },
        breadcrumbTitles() {
            return [{title: "Administración"}, this.config.entity.page];
        }
    },
    methods: {
        async selectResource(resourceCode) {
            if(this.selectedResourceCode === resourceCode) return;

            this.selectedResourceCode = resourceCode;
            this.searchTerm = "";
            this.startCreate();
            await this.loadRecords();
        },
        async loadRecords() {
            this.loading = true;

            const result = await Requests.get({
                route: this.resourceRoute(),
                showAlert: true
            });

            this.records = Requests.valid({result}) ? (result.data.data || []) : [];
            this.loading = false;
            Alerts.tooltips({time: 250});
        },
        startCreate() {
            this.form = this.emptyForm();
            this.originalRecord = null;
            this.errors = {};
            this.imageFile = null;
            const imageInput = Array.isArray(this.$refs.imageInput) ? this.$refs.imageInput[0] : this.$refs.imageInput;
            if(imageInput) imageInput.value = "";
        },
        startEdit(record) {
            this.originalRecord = record;
            this.errors = {};
            this.imageFile = null;
            this.form = this.mapRecordToForm(record);
            this.$nextTick(() => Alerts.tooltips({time: 250}));
        },
        emptyForm() {
            const form = {id: null};

            this.activeFields.forEach(field => {
                form[field.key] = this.defaultValueForField(field);
            });

            form.status = this.optionByCode(OPTION.statuses, "active");

            return form;
        },
        mapRecordToForm(record) {
            const form = {id: record.id};

            this.activeFields.forEach(field => {
                const value = record[field.key];

                if(field.type === "select") {
                    form[field.key] = this.optionByCode(field.options, value);
                }else if(field.type === "checkbox") {
                    form[field.key] = Boolean(value);
                }else if(field.type === "file") {
                    form[field.key] = null;
                    form.image_path = record.image_path;
                }else {
                    form[field.key] = value ?? "";
                }
            });

            form.status = this.optionByCode(OPTION.statuses, record.status || "active");

            return form;
        },
        defaultValueForField(field) {
            if(field.type === "select") return field.options[0] || null;
            if(field.type === "checkbox") return false;
            if(field.type === "number") return "";

            return "";
        },
        optionByCode(options, code) {
            return options.find(option => option.code === code) || options[0] || null;
        },
        onImageChange(event) {
            this.imageFile = event.target.files?.[0] || null;
        },
        async saveRecord() {
            if(this.saving) return;
            if(!(await this.confirmDeactivationImpact())) return;

            this.saving = true;
            this.errors = {};
            Alerts.swals({type: this.isEditing ? "update" : "create", entity: this.selectedResource.singular});

            const payload = this.preparePayload();
            const request = this.isEditing
                ? Requests.patch({route: this.resourceRoute(), id: this.form.id, formData: payload instanceof FormData ? payload : null, data: payload instanceof FormData ? {} : payload})
                : Requests.post({route: this.resourceRoute(), formData: payload instanceof FormData ? payload : null, data: payload instanceof FormData ? {} : payload});
            const result = await request;

            Alerts.swals({show: false});
            this.saving = false;

            if(Requests.valid({result})) {
                Alerts.generateAlert({type: "success", msgContent: result.data.msg || "Registro guardado correctamente."});
                await this.loadRecords();
                this.startCreate();
                return;
            }

            this.errors = result.errors || {};
            Alerts.generateAlert({
                type: "error",
                msgContent: result.data?.msg || "Revisa la información ingresada."
            });
        },
        async confirmDeactivationImpact() {
            const wasActive = (this.originalRecord?.status || "active") === "active";
            const willDeactivate = this.isEditing && wasActive && this.statusCode === "inactive";

            if(!willDeactivate) return true;

            const confirmation = await Swal.fire({
                title: "Inactivar registro",
                icon: "warning",
                html: `
                    <p class="mb-2"><strong>${this.recordTitle(this.form)}</strong> dejará de estar disponible para nuevas operaciones.</p>
                    <div class="text-start">
                        <p class="mb-1"><strong>Tipo:</strong> ${this.selectedResource.typeLabel}</p>
                        <p class="mb-1"><strong>Alcance:</strong> ${this.selectedResource.scopeLabel}</p>
                        <p class="mb-0"><strong>Impacto:</strong> ${this.selectedResource.deactivationImpact}</p>
                    </div>
                `,
                showCancelButton: true,
                showCloseButton: true,
                closeButtonAriaLabel: "Cerrar confirmación",
                buttonsStyling: false,
                confirmButtonText: "Inactivar registro",
                cancelButtonText: "Cancelar",
                allowOutsideClick: false,
                allowEscapeKey: false,
                customClass: {
                    container: "br-swal-backdrop",
                    popup: "br-swal-alert br-swal-alert--warning",
                    confirmButton: "br-btn br-swal-alert__confirm br-swal-alert__confirm--warning",
                    cancelButton: "br-btn br-btn-cancel ms-2"
                }
            });

            return confirmation.isConfirmed;
        },
        preparePayload() {
            const data = {};

            this.activeFields.forEach(field => {
                if(field.type === "file") return;

                const value = this.form[field.key];
                data[field.key] = this.normalizeValue(field, value);
            });

            if(this.selectedResourceCode === "taxes" && data.calculation_type === "percentage") {
                data.min_apply_quantity = null;
                data.max_apply_quantity = null;
            }

            if(this.selectedResourceCode !== "payment-methods" || !this.imageFile) {
                return data;
            }

            const formData = new FormData();
            Object.entries(data).forEach(([key, value]) => formData.append(key, value ?? ""));
            formData.append("image", this.imageFile);

            return formData;
        },
        normalizeValue(field, value) {
            if(field.type === "select") return value?.code || "";
            if(field.type === "checkbox") return value ? 1 : 0;
            if(field.type === "number") return value === "" || value === null ? null : value;

            return value ?? "";
        },
        resourceRoute() {
            return `/master-data/${this.selectedResourceCode}`;
        },
        firstError(field) {
            return this.errors?.[field]?.[0] || "";
        },
        recordTitle(record) {
            if(this.selectedResourceCode === "company-settings") {
                return `${record.group || ""}.${record.key || ""}`;
            }

            return record.name || record.code || "-";
        },
        recordSubtitle(record) {
            if(this.selectedResourceCode === "company-settings") return record.description || "Sin descripción";
            if(this.selectedResourceCode === "taxes") return record.description || `${this.scopeLabel(record.scope)} · ${this.calculationLabel(record)}`;
            if(this.selectedResourceCode === "payment-methods") return `${this.scopeLabel(record.scope)} · ${record.requires_reference ? "Requiere referencia" : "Sin referencia obligatoria"}`;
            if(this.selectedResourceCode === "currencies") return `${record.sign || ""} · ${record.singular_name || ""} / ${record.plural_name || ""}`;

            return record.code || "Sin código";
        },
        recordMeta(record) {
            if(this.selectedResourceCode === "company-settings") return `Tipo: ${this.valueTypeLabel(record.value_type)} · Valor: ${record.value ?? "Sin valor"}`;
            if(this.selectedResourceCode === "taxes") return `${record.operation_type === "subtraction" ? "Resta" : "Suma"} · ${record.is_required ? "Obligatorio" : "Opcional"}`;
            if(this.selectedResourceCode === "payment-methods") return `Código: ${record.code}${record.sunat_code ? ` · SUNAT: ${record.sunat_code}` : ""}`;

            return `Código: ${record.code}`;
        },
        statusLabel(status) {
            return status === "active" ? "Activo" : "Inactivo";
        },
        scopeLabel(scope) {
            return {sale: "Ventas", purchase: "Compras", both: "Ventas y compras"}[scope] || "Empresa";
        },
        valueTypeLabel(type) {
            return OPTION.valueTypes.find(option => option.code === type)?.label || type || "-";
        },
        calculationLabel(record) {
            if(record.calculation_type === "percentage") return `${record.rate}%`;

            return `Monto fijo ${record.rate}`;
        }
    }
};
</script>
