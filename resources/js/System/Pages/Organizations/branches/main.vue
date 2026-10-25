<template>
    <Breadcrumb :list="breadcrumbTitles"/>

    <!-- Filters -->
    <FiltersSection
        :filter-by-value="filterByValue"
        @update:filterByValue="filterByValue = $event"
        :filter-word-value="filterWordValue"
        @update:filterWordValue="filterWordValue = $event"
        :filter-by-options="filterByOptions"
        :search-placeholder="searchPlaceholder"
        :loading="entityList.extras.loading"
        :filter-by-title="MODULE.texts.filters.filterBy"
        :search-title="MODULE.texts.filters.search"
        :search-button-text="MODULE.texts.actions.search"
        :add-button-text="MODULE.texts.actions.add"
        :show-add-button="true"
        :title-class="[config.forms.classes.title]"
        :select-class="config.forms.classes.select2"
        @search="handleSearch"
        @add="openModal()">
        <template #secondaryActions>
            <div class="dropdown">
                <button type="button" class="br-btn br-btn-sm br-btn-actions" data-bs-toggle="dropdown" aria-expanded="false" :disabled="entityList.extras.loading">
                    Acciones
                    <i class="fa-solid fa-chevron-down br-btn-actions__chevron" aria-hidden="true"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end br-btn-actions-menu">
                    <li>
                        <button type="button" class="dropdown-item" @click="toggleSeriesAudit">
                            <i class="fa-solid fa-list-check" aria-hidden="true"></i>
                            <span>{{ seriesAudit.visible ? 'Ocultar auditoría de series' : 'Auditoría de series' }}</span>
                        </button>
                    </li>
                    <li v-if="seriesAudit.visible">
                        <button type="button" class="dropdown-item" :disabled="seriesAudit.loading" @click="downloadSeriesAudit">
                            <i class="fa-solid fa-file-excel" aria-hidden="true"></i>
                            <span>Descargar auditoría</span>
                        </button>
                    </li>
                </ul>
            </div>
        </template>
    </FiltersSection>

    <section v-if="seriesAudit.visible" class="br-entity-list br-branch-series-audit">
        <div class="br-branch-series-audit__summary">
            <span>
                <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                Bitácora de emisiones, anulaciones y saltos de correlativo.
            </span>
            <strong :class="seriesAudit.gaps.length ? 'text-warning' : 'text-success'">
                {{ seriesAudit.gaps.length ? `${seriesAudit.gaps.length} saltos detectados` : 'Sin saltos detectados' }}
            </strong>
        </div>
        <Loader v-if="seriesAudit.loading"/>
        <div v-else-if="seriesAudit.records.total > 0" class="table-responsive">
            <table class="table br-entity-table mb-0">
                <thead class="br-table-header-surface">
                    <tr>
                        <th>Fecha</th>
                        <th>Sucursal</th>
                        <th>Serie</th>
                        <th class="text-end">Correlativo</th>
                        <th>Acción</th>
                        <th>Origen</th>
                        <th>Responsable</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="record in seriesAudit.records.data" :key="record.id">
                        <td>{{ formatDateTime(record.occurred_at) }}</td>
                        <td>{{ record.branch_name || '-' }}</td>
                        <td>
                            <strong class="br-entity-primary">{{ record.serie_code }}{{ record.serie_number }}</strong>
                        </td>
                        <td class="text-end fw-bold">{{ record.sequential }}</td>
                        <td>
                            <span :class="['br-status-label', record.action === 'issued' ? 'br-status-active' : 'br-status-inactive']">
                                {{ seriesActionLabel(record.action) }}
                            </span>
                        </td>
                        <td>{{ record.source || '-' }}</td>
                        <td>{{ record.user_name || '-' }}</td>
                    </tr>
                </tbody>
            </table>
            <Paginator :links="seriesAudit.records.links" @clickPage="loadSeriesAudit"/>
        </div>
        <WithoutData v-else type="image" text="Sin movimientos de series" description="No se registran emisiones ni anulaciones con los filtros actuales."/>
    </section>

    <!-- Records -->
    <section class="br-entity-list br-branches-list" aria-label="Listado de sucursales">
        <div class="table-responsive">
            <table class="table br-entity-table br-entity-table--directory mb-0">
                <colgroup>
                    <col style="width: 20%;">
                    <col style="width: 18%;">
                    <col style="width: 19%;">
                    <col style="width: 19%;">
                    <col style="width: 10%;">
                    <col style="width: 8%;">
                    <col style="width: 6%;">
                </colgroup>
                <thead class="br-table-header-surface">
                    <tr>
                        <th>Identificación</th>
                        <th>Sucursal</th>
                        <th>Ubicación</th>
                        <th>Contacto</th>
                        <th class="text-center">Capacidad</th>
                        <th class="text-center">Estado</th>
                        <th class="text-center"><span class="visually-hidden">Acciones</span></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="entityList.extras.loading">
                        <td colspan="7" class="py-4"><Loader/></td>
                    </tr>
                    <template v-else-if="entityList.records.total > 0">
                        <tr v-for="record in entityList.records.data" :key="record.id">
                            <td class="br-entity-table__identification-cell">
                                <div class="br-entity-identifiers br-entity-identifiers--plain">
                                    <div class="br-entity-identifier">
                                        <span class="br-entity-identifier__label colon-at-end">Cód. interno</span>
                                        <span class="br-entity-identifier__value">
                                            <span class="br-entity-code" v-text="record.internal_code || '—'"></span>
                                            <span v-if="record.internal_code" class="br-entity-identifier__actions">
                                                <CopyButton :value="record.internal_code" label="Código interno"/>
                                            </span>
                                        </span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="br-entity-table__name" v-text="record.name"></span>
                            </td>
                            <td>
                                <span class="br-branches-list__primary" v-text="record.address || 'Sin dirección'"></span>
                                <span v-if="record.reference" class="br-entity-table__meta" v-text="record.reference"></span>
                            </td>
                            <td>
                                <span class="br-branches-list__primary" v-text="record.telephone || 'Sin teléfono'"></span>
                                <span v-if="record.email" class="br-entity-table__meta" v-text="record.email"></span>
                            </td>
                            <td class="text-center" v-text="record.capacity ?? '—'"></td>
                            <td class="text-center"><StatusBadge :status="record.status" :formatted-status="record.formatted_status"/></td>
                            <td class="text-center">
                                <TableActionsDropdown :label="`Acciones de ${record.name}`">
                                    <li>
                                        <button type="button" class="dropdown-item" @click="openModal(record)">
                                            <i class="fa-solid fa-pen" aria-hidden="true"></i>
                                            <span>Editar sucursal</span>
                                        </button>
                                    </li>
                                    <li v-if="isDefined(record.map_url)">
                                        <a :href="record.map_url" class="dropdown-item" target="_blank" rel="noopener noreferrer">
                                            <i class="fa-solid fa-map-location-dot" aria-hidden="true"></i>
                                            <span>Ver mapa</span>
                                        </a>
                                    </li>
                                </TableActionsDropdown>
                            </td>
                        </tr>
                    </template>
                    <tr v-else>
                        <td colspan="7"><WithoutData class="br-entity-empty-state" type="image"/></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

    <!-- Pagination -->
    <nav v-if="!entityList.extras.loading && entityList.records.total > 0" class="d-flex justify-content-center">
        <Paginator :links="entityList.records.links" @clickPage="listEntity"/>
    </nav>

    <!-- Modal: Create/Update -->
    <div class="modal fade br-entity-modal br-modal-standard" :id="forms[entity].createUpdate.extras.modals.default.id" data-bs-backdrop="static" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header br-entity-modal__header">
                    <h5 class="modal-title br-entity-modal__title br-entity-modal__title--form" v-text="isUpdate ? 'EDITAR SUCURSAL' : 'AGREGAR SUCURSAL'"></h5>
                    <button type="button" class="br-modal-close" data-bs-dismiss="modal" aria-label="Cerrar">
                        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                    </button>
                </div>
                <div class="modal-body br-entity-modal__body">
                    <form @submit.prevent="saveEntity">
                        <div class="row g-3">
                            <InputText
                                v-model="forms[entity].createUpdate.data.internal_code"
                                hasDiv
                                :title="MODULE.texts.form.internalCode"
                                :titleClass="[config.forms.classes.title]"
                                isRequired
                                :maxlength="internalCodeEditableMaxlength"
                                :showCharCounter="false"
                                hasTextBottom
                                :textBottomInfo="forms[entity].createUpdate.errors?.internal_code"
                                xl="4"
                                lg="4">
                                <template v-if="internalCodePrefixLabel" v-slot:inputGroupPrepend>
                                    <span class="input-group-text br-internal-code-prefix" v-text="internalCodePrefixLabel"></span>
                                </template>
                                <template v-slot:inputGroupAppend v-if="!isUpdate">
                                    <button type="button" class="br-input-action" @click="generateCodeAction" data-bs-toggle="tooltip" data-bs-placement="top" :title="MODULE.texts.form.generateCodeTooltip">
                                        <i class="fa fa-rotate"></i>
                                    </button>
                                </template>
                            </InputText>
                            <InputText
                                v-model="forms[entity].createUpdate.data.name"
                                hasDiv
                                :title="MODULE.texts.form.name"
                                :titleClass="[config.forms.classes.title]"
                                isRequired
                                maxlength="50"
                                showCharCounter
                                hasTextBottom
                                :textBottomInfo="forms[entity].createUpdate.errors?.name"
                                xl="8"
                                lg="8"/>
                            <InputText
                                v-model="forms[entity].createUpdate.data.address"
                                hasDiv
                                :title="MODULE.texts.form.address"
                                :titleClass="[config.forms.classes.title]"
                                maxlength="100"
                                showCharCounter
                                hasTextBottom
                                :textBottomInfo="forms[entity].createUpdate.errors?.address"
                                xl="6"
                                lg="6"/>
                            <InputText
                                v-model="forms[entity].createUpdate.data.reference"
                                hasDiv
                                :title="MODULE.texts.form.reference"
                                :titleClass="[config.forms.classes.title]"
                                maxlength="100"
                                showCharCounter
                                hasTextBottom
                                :textBottomInfo="forms[entity].createUpdate.errors?.reference"
                                xl="6"
                                lg="6"/>
                            <InputText
                                v-model="forms[entity].createUpdate.data.telephone"
                                hasDiv
                                :title="MODULE.texts.form.telephone"
                                :titleClass="[config.forms.classes.title]"
                                maxlength="15"
                                showCharCounter
                                hasTextBottom
                                :textBottomInfo="forms[entity].createUpdate.errors?.telephone"
                                xl="4"
                                lg="4"/>
                            <InputText
                                v-model="forms[entity].createUpdate.data.email"
                                hasDiv
                                :title="MODULE.texts.form.email"
                                :titleClass="[config.forms.classes.title]"
                                maxlength="100"
                                showCharCounter
                                hasTextBottom
                                :textBottomInfo="forms[entity].createUpdate.errors?.email"
                                xl="8"
                                lg="8"/>
                            <InputNumber
                                v-model="forms[entity].createUpdate.data.capacity"
                                hasDiv
                                :title="MODULE.texts.form.capacity"
                                :titleClass="[config.forms.classes.title]"
                                :decimals="0"
                                :minValue="0"
                                hasTextBottom
                                :textBottomInfo="forms[entity].createUpdate.errors?.capacity"
                                xl="4"
                                lg="4">
                                <template v-slot:default>
                                    <i class="fa fa-info-circle cursor-pointer text-i-help me-1" data-bs-toggle="tooltip" data-bs-placement="top" :title="MODULE.texts.form.capacityTooltip"></i>
                                </template>
                            </InputNumber>
                            <InputText
                                v-model="forms[entity].createUpdate.data.map_url"
                                hasDiv
                                :title="MODULE.texts.form.mapUrl"
                                :titleClass="[config.forms.classes.title]"
                                maxlength="500"
                                showCharCounter
                                hasTextBottom
                                :textBottomInfo="forms[entity].createUpdate.errors?.map_url"
                                xl="8"
                                lg="8"/>
                            <InputSlot
                                hasDiv
                                :title="MODULE.texts.form.status"
                                :titleClass="[config.forms.classes.title]"
                                isRequired
                                hasTextBottom
                                :textBottomInfo="forms[entity].createUpdate.errors?.status"
                                xl="4"
                                lg="4">
                                <template v-slot:input>
                                    <v-select
                                        v-model="forms[entity].createUpdate.data.status"
                                        :options="statuses"
                                        :class="config.forms.classes.select2"
                                        :clearable="false"
                                        :searchable="false"/>
                                </template>
                            </InputSlot>
                        </div>
                    </form>
                </div>
                <div class="modal-footer br-entity-modal__footer">
                    <button
                        type="button"
                        class="br-btn br-btn-cancel"
                        data-bs-dismiss="modal">Cancelar</button>
                    <button
                        type="button"
                        :class="['br-btn', isUpdate ? 'br-btn-action-update' : 'br-btn-action-create']"
                        @click="saveEntity"
                        :disabled="isSaving">
                        <span v-text="MODULE.texts.modal.save"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import * as Alerts from "@System/Helpers/Alerts.js";
import { initCrudModule } from "@System/Helpers/ModuleFactory.js";
import * as Forms from "@System/Helpers/Forms.js";
import * as Requests from "@System/Helpers/Requests.js";
import * as Utils from "@System/Helpers/Utils.js";
import InternalCodePrefixMixin from "@System/Mixins/InternalCodePrefixMixin.js";

const MODULE_CONFIG = {
    entity: "branches",
    menuId: "menu-configuration-branches",
    pageTitle: "Sucursales",
    pageTitleSingular: "Sucursal",
    internalCodeEntity: "branch",
    breadcrumbParent: "Administración",
    perPage: 15
};

const FORM_FIELDS = {
    internal_code: "",
    name: "",
    address: "",
    reference: "",
    telephone: "",
    email: "",
    capacity: "",
    map_url: "",
    status: null
};

const FORM_FIELD_CONFIG = {
    internal_code: {trim: true},
    name: {trim: true},
    address: {normalize: true},
    reference: {normalize: true},
    telephone: {normalize: true},
    email: {normalize: true},
    capacity: {toNumber: true, minValue: 0},
    map_url: {normalize: true},
    status: {getCode: true}
};

const VALIDATION_RULES = {
    internal_code: {required: true},
    name: {required: true},
    address: {required: false},
    reference: {required: false},
    telephone: {required: false},
    email: {required: false, email: true},
    capacity: {required: false, number: true, min: 0},
    map_url: {required: false, url: true},
    status: {required: true}
};

const ERROR_LABELS = {
    internal_code: "Código interno",
    name: "Nombre",
    address: "Dirección",
    reference: "Referencia",
    telephone: "Teléfono",
    email: "Correo electrónico",
    capacity: "Capacidad",
    map_url: "URL del mapa",
    status: "Estado"
};

const FILTER_OPTIONS = [
    {code: "all", label: "Todos los filtros"},
    {code: "internal_code", label: "Código interno"},
    {code: "name", label: "Nombre"},
    {code: "address", label: "Dirección"},
    {code: "reference", label: "Referencia"},
    {code: "telephone", label: "Teléfono"},
    {code: "email", label: "Correo electrónico"}
];

const TEXTS = {
    filters: {
        filterBy: "Filtrar por",
        search: "Búsqueda"
    },
    actions: {
        search: "Buscar",
        add: "Agregar sucursal"
    },
    form: {
        internalCode: "Código interno",
        name: "Nombre",
        address: "Dirección",
        reference: "Referencia",
        telephone: "Teléfono",
        email: "Correo electrónico",
        capacity: "Capacidad",
        capacityTooltip: "Cantidad de personas",
        mapUrl: "URL del mapa",
        status: "Estado",
        generateCodeTooltip: "Generar aleatoriamente"
    },
    modal: {
        close: "Cerrar",
        save: "Guardar sucursal"
    }
};

const MODULE = {
    config: MODULE_CONFIG,
    formFields: FORM_FIELDS,
    formFieldConfig: FORM_FIELD_CONFIG,
    validationRules: VALIDATION_RULES,
    errorLabels: ERROR_LABELS,
    texts: TEXTS,
    filterOptions: FILTER_OPTIONS
};

export default {
    name: "BranchesMain",
    mixins: [InternalCodePrefixMixin],
    data() {

        const crudModule = initCrudModule({
            entity: MODULE.config.entity,
            menuId: MODULE.config.menuId,
            pageTitle: MODULE.config.pageTitle,
            pageTitleSingular: MODULE.config.pageTitleSingular
        });

        crudModule.lists[MODULE.config.entity].filters.filter_by = MODULE.filterOptions[0];
        crudModule.forms[MODULE.config.entity].createUpdate.data = Forms.initFormData(MODULE.formFields);

        return {
            ...crudModule,
            MODULE: MODULE,
            isInitialized: false,
            isSaving: false,
            seriesAudit: {
                visible: false,
                loading: false,
                records: {total: 0, data: [], links: []},
                gaps: []
            }
        };

    },
    mounted: async function() {

        Utils.navbarItem("menu-parent-configuration", {addClass: "open"});
        Utils.navbarItem(this.config.entity.page.menu.id, {});

        Alerts.swals({type: "initParams"});

        const initParams = await this.initParams();

        this.isInitialized = true;

        if(initParams) {

            Alerts.swals({show: false});
            this.listEntity({});

        }

    },
    methods: {
        async initParams() {

            const response = await Requests.get({
                route: this.routeActions.initParams,
                data: {page: "main"},
                showAlert: true
            });

            if(response?.data?.config) {

                this.options.statuses = response.data.config.statuses;
                this.options.internal_code_prefixes = response.data.config.internal_code_prefixes ?? {};

            }

            return Requests.valid({result: response});

        },
        // List
        async listEntity(params = null) {

            const entityList   = this.lists[this.entity];
            const emptyRecords = {total: 0, data: [], links: []};
            const filters      = Utils.cloneJson(entityList.filters);
            const filterData   = {per_page: this.MODULE.config.perPage, filter_by: filters.filter_by?.code, word: filters.word};

            entityList.extras.loading = true;

            try {

                const url = this.isDefined(params) && typeof params === "object" ? params.url : params;

                let requestUrl  = url || entityList.extras.route;
                let requestData = {};

                if(this.isDefined(url)) {

                    const urlObj = new URL(url, window.location.origin);

                    Object.entries(filterData).forEach(([key, value]) => {

                        if(this.isDefined(value) && !urlObj.searchParams.has(key)) urlObj.searchParams.set(key, value);

                    });

                    requestUrl = `${urlObj.pathname}${urlObj.search}`;

                }else {

                    requestData = filterData;

                }

                const response = await Requests.get({route: requestUrl, data: requestData, showAlert: true});

                entityList.records = response?.data ?? emptyRecords;

            }catch(error) {

                entityList.records = emptyRecords;

            }finally {

                entityList.extras.loading = false;

            }

        },
        handleSearch() {

            this.listEntity({});

        },
        async toggleSeriesAudit() {

            this.seriesAudit.visible = !this.seriesAudit.visible;

            if(this.seriesAudit.visible && !this.seriesAudit.records.total) {

                await this.loadSeriesAudit({});

            }

            Alerts.tooltips({time: 250});

        },
        async loadSeriesAudit(params = null) {

            this.seriesAudit.loading = true;

            const url = this.isDefined(params) && typeof params === "object" ? params.url : params;
            const response = await Requests.get({
                route: url || this.routeActions.seriesAudit,
                data: {per_page: 10},
                showAlert: true
            });

            this.seriesAudit.loading = false;

            if(!Requests.valid({result: response})) return;

            this.seriesAudit.records = response.data.data || {total: 0, data: [], links: []};
            this.seriesAudit.gaps = response.data.gaps || [];

        },
        async downloadSeriesAudit() {

            Alerts.swals({type: "loading", message: "Preparando auditoría de series"});

            const result = await Requests.download({
                route: this.routeActions.seriesAuditExport,
                data: {},
                fileName: "auditoria-correlativos.csv",
                showAlert: true
            });

            Alerts.swals({show: false});

            if(result.bool) {

                Alerts.toastrs({type: "success", subtitle: "Auditoría descargada correctamente."});

            }

        },
        // Forms
        openModal(record = null) {

            const entityForms = this.forms[this.entity].createUpdate;

            entityForms.errors = {};
            Forms.clearFormData(entityForms.data, this.MODULE.formFields);

            if(this.isDefined(record)) {

                // Map record data to form
                const statusOption = this.statuses.find(s => s.code === record.status);

                entityForms.data.id            = record.id;
                entityForms.data.internal_code = this.stripInternalCodePrefix(record.internal_code);
                entityForms.data.name          = record.name;
                entityForms.data.address       = record.address;
                entityForms.data.reference     = record.reference;
                entityForms.data.telephone     = record.telephone;
                entityForms.data.email         = record.email;
                entityForms.data.capacity      = record.capacity;
                entityForms.data.map_url       = record.map_url;
                entityForms.data.status        = statusOption;

            }else {

                // Set defaults for new record
                entityForms.data.internal_code = this.generateCode({length: 7});
                entityForms.data.status        = this.statuses.length > 0 ? this.statuses[0] : null;

            }

            Alerts.modals({type: "show", id: entityForms.extras.modals.default.id});
            Alerts.tooltips({show: true, time: 500});

        },
        generateCodeAction() {

            this.forms[this.entity].createUpdate.data.internal_code = this.generateCode({length: 7});

            Alerts.toastrs({type: "success", subtitle: "Código interno generado correctamente."});
            Alerts.tooltips({show: false});

        },
        async saveEntity() {

            if(this.isSaving) return;

            const entityForms = this.forms[this.entity].createUpdate;

            Alerts.swals({});

            entityForms.errors = {};
            this.isSaving = true;

            try {

                const formData   = Utils.cloneJson(entityForms.data);
                const validation = this.validateFormData(formData);

                if(!validation.bool) {

                    Alerts.generateAlert({
                        type: "error",
                        messages: Forms.getDescriptiveErrors(validation.errors, this.MODULE.errorLabels),
                        msgContent: this.config.messages.errorValidate
                    });
                    this.isSaving = false;
                    return;

                }

                const preparedData  = Forms.prepareFormData(formData, this.MODULE.formFieldConfig);
                const id            = preparedData.id;
                const isUpdate      = this.isDefined(id);
                const requestMethod = isUpdate ? "patch" : "post";
                const route         = this.routeActions[isUpdate ? "update" : "store"];
                const result        = await Requests[requestMethod]({route, data: preparedData, id});

                if(Requests.valid({result})) {

                    Alerts.modals({type: "hide", id: entityForms.extras.modals.default.id});
                    Alerts.generateAlert({type: "success", msgContent: result.data.msg});

                    Forms.clearFormData(entityForms.data, this.MODULE.formFields);

                    const entityList  = this.entityList;
                    const currentPage = entityList?.records?.current_page ?? 1;

                    this.listEntity({url: `${entityList?.extras?.route || ""}?page=${currentPage}`});

                }else {

                    Forms.handleFormResponseErrors({
                        result,
                        formErrorsObject: entityForms.errors,
                        config: this.config,
                        errorLabels: this.MODULE.errorLabels
                    });

                }

            }catch(error) {

                Alerts.generateAlert({type: "error", messages: [error], msgContent: this.config.messages.catchError});

            }finally {

                this.isSaving = false;

            }

        },
        validateFormData(formData) {

            const result = Forms.validateFormData(formData, this.validationRules, {isDescriptive: true, errorLabels: this.MODULE.errorLabels});

            return result;

        },
        // Helpers
        // Others
        isDefined(value) {

            return Utils.isDefined({value});

        },
        generateCode({length}) {

            return Utils.generateCode({length});

        },
        formatDateTime(value) {

            if(!value) return "-";

            return new Date(value).toLocaleString("es-PE");

        },
        seriesActionLabel(action) {

            return {issued: "Emitido", cancelled: "Anulado"}[action] || action || "-";

        },
    },
    computed: {
        entity() {

            return this.MODULE.config.entity;

        },
        routeActions() {

            return this.config.entity.routes;

        },
        entityList() {

            return this.lists[this.entity];

        },
        breadcrumbTitles() {

            return [
                {title: this.MODULE.config.breadcrumbParent},
                this.config.entity.page
            ];

        },
        statuses() {

            return (this.options?.statuses ?? []).map(e => ({code: e.code, label: e.label}));

        },
        isUpdate() {

            return this.isDefined(this.forms[this.entity].createUpdate.data.id);

        },
        filterByOptions() {

            return this.MODULE.filterOptions;

        },
        filterByValue: {
            get() {

                return this.entityList.filters?.filter_by || this.MODULE.filterOptions[0];

            },
            set(value) {

                this.entityList.filters.filter_by = value;

            }
        },
        filterWordValue: {
            get() {

                return this.entityList.filters.word || "";

            },
            set(value) {

                this.entityList.filters.word = value;

            }
        },
        searchPlaceholder() {

            const filterBy = this.entityList.filters.filter_by;

            if(!filterBy) return "Buscar...";

            return `Buscar por ${(filterBy.label || "...").toLowerCase()}`;

        },
        validationRules() {

            const rules = Utils.cloneJson(this.MODULE.validationRules);

            return rules;

        }
    }
};
</script>

<style scoped>
</style>
