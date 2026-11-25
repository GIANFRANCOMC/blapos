<template>
    <Breadcrumb :list="breadcrumbTitles"/>
    <FgPageHeader title="Libro de reclamaciones" description="Revisa los casos registrados y da seguimiento a su atención."/>

    <!-- Filters Section -->
    <InitialLoadTransition>
        <PageSkeleton v-if="initialLoading" table/>
        <div v-else class="fg-page-content">
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
                :add-button-text="MODULE.texts.actions.add"
                :show-add-button="false"
                show-clear-button
                :title-class="[config.forms.classes.title]"
                :select-class="config.forms.classes.select2"
                @search="handleSearch"/>

            <section class="mb-3" aria-labelledby="book-complaint-list-title">
                <h2 id="book-complaint-list-title" class="fg-section-title mb-2">Casos registrados</h2>
                <ResponsiveTable>
                    <table class="fg-table fg-entity-table--directory fg-record-table" role="table">
                        <colgroup>
                            <col style="width: 10%;">
                            <col style="width: 32%;">
                            <col style="width: 16%;">
                            <col style="width: 14%;">
                            <col style="width: 9%;">
                            <col style="width: 12%;">
                            <col style="width: 7%;">
                        </colgroup>
                        <thead role="rowgroup">
                            <tr role="row">
                                <th scope="col" role="columnheader">Caso</th>
                                <th scope="col" role="columnheader">Cliente y asunto</th>
                                <th scope="col" role="columnheader">Sucursal</th>
                                <th scope="col" role="columnheader">Fecha</th>
                                <th scope="col" role="columnheader">Tipo</th>
                                <th scope="col" role="columnheader" class="fg-align-center">Estado</th>
                                <th scope="col" role="columnheader"><span class="visually-hidden">Acciones</span></th>
                            </tr>
                        </thead>
                        <tbody role="rowgroup">
                            <tr v-if="entityList.extras.loading" class="fg-table__state" role="row">
                                <td colspan="7" role="cell"><Loader label="Buscando reclamaciones…"/></td>
                            </tr>
                            <tr v-else-if="entityList.extras.loadFailed" class="fg-table__state" role="row">
                                <td colspan="7" role="cell">
                                    <FgEmptyState title="No se pudieron cargar las reclamaciones" description="Reintenta con los mismos filtros." role="alert">
                                        <button type="button" class="fg-button fg-button--secondary" @click="listEntity({})">Reintentar</button>
                                    </FgEmptyState>
                                </td>
                            </tr>
                            <template v-else-if="entityList.records.total > 0">
                                <tr v-for="record in entityList.records.data" :key="record.id" role="row">
                                    <td role="cell" class="fg-record-table__identity"><span class="fg-entity-code">#{{ record.id }}</span></td>
                                    <td role="cell">
                                        <span class="fg-record-table__label" aria-hidden="true">Cliente y asunto</span>
                                        <div class="fg-record-table__value">
                                            <span class="fg-entity-table__name text-break">{{ record.name || '—' }}</span>
                                            <small class="fg-help d-block text-truncate" :title="record.description">{{ record.description || MODULE.texts.card.noDescription }}</small>
                                            <details v-if="record.document_number || record.email || record.phone_number" class="fg-help">
                                                <summary class="fg-link">Contacto y documento</summary>
                                                <span v-if="record.document_number" class="d-block text-break">{{ record.document_number }}</span>
                                                <span v-if="record.email" class="d-block text-break">{{ record.email }}</span>
                                                <span v-if="record.phone_number" class="d-block text-break">{{ record.phone_number }}</span>
                                            </details>
                                        </div>
                                    </td>
                                    <td role="cell">
                                        <span class="fg-record-table__label" aria-hidden="true">Sucursal</span>
                                        <span class="fg-record-table__value text-break">{{ record.branch?.name || '—' }}</span>
                                    </td>
                                    <td role="cell">
                                        <span class="fg-record-table__label" aria-hidden="true">Fecha</span>
                                        <span class="fg-record-table__value">{{ legibleFormatDate({dateString: record.created_at, type: 'datetime'}) }}</span>
                                    </td>
                                    <td role="cell">
                                        <span class="fg-record-table__label" aria-hidden="true">Tipo</span>
                                        <span class="fg-record-table__value">{{ getType(record)?.label || '—' }}</span>
                                    </td>
                                    <td role="cell" class="fg-record-table__state">
                                        <StatusBadge :status="record.status" :formatted-status="record.formatted_status" :custom-variants="bookComplaintsStatusBadgeVariants"/>
                                    </td>
                                    <td role="cell" class="fg-record-table__actions">
                                        <TableActionsDropdown :label="`Acciones del caso ${record.id}`">
                                            <li>
                                                <button type="button" class="fg-menu__item" @click="openModal(record)">{{ MODULE.texts.actions.manage }}</button>
                                            </li>
                                        </TableActionsDropdown>
                                    </td>
                                </tr>
                            </template>
                            <tr v-else class="fg-table__state" role="row">
                                <td colspan="7" role="cell">
                                    <FgEmptyState
                                        :title="entityList.appliedFilters?.word ? 'No hay coincidencias' : 'Aún no hay reclamaciones registradas'"
                                        :description="entityList.appliedFilters?.word ? 'Prueba con otros filtros.' : undefined"/>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </ResponsiveTable>
            </section>

            <!-- Pagination -->
            <Paginator :links="entityList.records.links" :pagination="entityList.records" :loading="entityList.extras.loading" @clickPage="listEntity"/>

            <!-- Modal: Create/Update -->

        </div>
    </InitialLoadTransition>
    <div class="modal fade fg-modal" aria-labelledby="book-complaint-title" :id="forms[entity].createUpdate.extras.modals.default.id" data-bs-backdrop="static" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
                <div class="fg-modal__header">
                    <h2 id="book-complaint-title" class="fg-modal__title mb-0" v-text="modalTitles[isUpdate ? 'update' : 'store']"></h2>
                    <button type="button" class="fg-button fg-button--icon" data-bs-dismiss="modal" aria-label="Cerrar">
                        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                    </button>
                </div>
                <div class="fg-modal__body">
                    <form @submit.prevent="saveEntity">
                        <div class="row g-3">
                            <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12">
                                <div class="card">
                                    <div class="card-header fg-table-header-surface">
                                        <h3 class="fg-section-title mb-0" v-text="MODULE.texts.sections.clientData"></h3>
                                    </div>
                                    <div class="card-body py-3">
                                        <div class="d-flex align-items-start justify-content-between gap-3 mb-3">
                                            <div class="d-flex flex-column flex-grow-1 flex-min-w-0">
                                                <span class="text-muted small fw-semibold">Cliente</span>
                                                <span class="fw-bold text-dark text-truncate" v-text="forms[entity].createUpdate.data.name || MODULE.texts.card.notSpecified"></span>
                                            </div>
                                        </div>
                                        <div class="text-start mb-1">
                                            <span class="text-dark fw-bold colon-at-end" v-text="MODULE.texts.form.documentNumber"></span>
                                            <span v-text="forms[entity].createUpdate.data.document_number || MODULE.texts.card.notSpecified" class="ms-2"></span>
                                        </div>
                                        <div class="text-start mb-1">
                                            <span class="text-dark fw-bold colon-at-end" v-text="MODULE.texts.form.identityDocumentType"></span>
                                            <span v-text="forms[entity].createUpdate.data.identity_document_type?.label || MODULE.texts.card.notSpecified" class="ms-2"></span>
                                        </div>
                                        <div class="text-start mb-1">
                                            <span class="text-dark fw-bold colon-at-end" v-text="MODULE.texts.form.email"></span>
                                            <a :href="'mailto:'+forms[entity].createUpdate.data.email" class="d-inline-flex align-items-center ms-2">
                                                <span v-text="forms[entity].createUpdate.data.email || MODULE.texts.card.notSpecified"></span>
                                            </a>
                                        </div>
                                        <div class="text-start">
                                            <span class="text-dark fw-bold colon-at-end" v-text="MODULE.texts.form.phoneNumber"></span>
                                            <a :href="'tel:'+forms[entity].createUpdate.data.phone_number" class="d-inline-flex align-items-center ms-2">
                                                <span v-text="forms[entity].createUpdate.data.phone_number || MODULE.texts.card.notSpecified"></span>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12">
                                <div class="card">
                                    <div class="card-header fg-table-header-surface">
                                        <h3 class="fg-section-title mb-0" v-text="MODULE.texts.sections.complaintDetail"></h3>
                                    </div>
                                    <div class="card-body py-3">
                                        <div class="d-flex align-items-start justify-content-between gap-3 mb-3">
                                            <div class="d-flex flex-column flex-grow-1 flex-min-w-0">
                                                <span class="text-muted small fw-semibold" v-text="legibleFormatDate({dateString: forms[entity].createUpdate.data.created_at, type: 'datetime'})"></span>
                                                <span class="fw-bold text-dark text-truncate" v-text="getType(forms[entity].createUpdate.data)?.label"></span>
                                            </div>
                                            <StatusBadge
                                                class="flex-shrink-none"
                                                :status="forms[entity].createUpdate.data.copy?.status?.code"
                                                :formatted-status="forms[entity].createUpdate.data.copy?.status?.label"
                                                :custom-variants="bookComplaintsStatusBadgeVariants"/>
                                        </div>
                                        <div class="text-start mb-1">
                                            <span class="text-dark fw-bold colon-at-end" v-text="MODULE.texts.form.branch"></span>
                                            <span v-text="forms[entity].createUpdate.data?.branch?.name || MODULE.texts.card.notSpecified" class="ms-2"></span>
                                        </div>
                                        <div class="text-start mb-1">
                                            <span class="text-dark fw-bold colon-at-end" v-text="MODULE.texts.form.description"></span>
                                            <span v-text="forms[entity].createUpdate.data.description || MODULE.texts.card.notRegistered" class="ms-2"></span>
                                        </div>
                                        <div class="text-start">
                                            <span class="text-dark fw-bold colon-at-end" v-text="MODULE.texts.form.request"></span>
                                            <span v-text="forms[entity].createUpdate.data.request || MODULE.texts.card.notRegistered" class="ms-2"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12">
                                <div class="card">
                                    <div class="card-header fg-table-header-surface">
                                        <h3 class="fg-section-title mb-0" v-text="MODULE.texts.sections.evidence"></h3>
                                    </div>
                                    <div class="card-body py-3">
                                        <div class="row g-3">
                                            <div class="col-xl-6 col-lg-6 col-md-12 col-sm-12">
                                                <span class="text-dark fw-bold colon-at-end d-block mb-2" v-text="MODULE.texts.form.attachments"></span>
                                                <div v-if="(forms[entity].createUpdate.data.attachments ?? []).length > 0" class="d-flex flex-wrap gap-2">
                                                    <a
                                                        v-for="attachment in forms[entity].createUpdate.data.attachments"
                                                        :key="attachment.id"
                                                        class="fg-book-complaint-attachment"
                                                        :href="bookComplaintAttachmentUrl(attachment)"
                                                        target="_blank"
                                                        rel="noopener noreferrer">
                                                        <i class="fa-solid fa-paperclip" aria-hidden="true"></i>
                                                        <span v-text="attachment.file_name"></span>
                                                    </a>
                                                </div>
                                                <span v-else class="text-muted small" v-text="MODULE.texts.card.notRegistered"></span>
                                            </div>
                                            <div class="col-xl-6 col-lg-6 col-md-12 col-sm-12">
                                                <span class="text-dark fw-bold colon-at-end d-block mb-2" v-text="MODULE.texts.form.statusHistory"></span>
                                                <div v-if="(forms[entity].createUpdate.data.status_histories ?? []).length > 0" class="fg-book-complaint-history">
                                                    <span v-for="history in forms[entity].createUpdate.data.status_histories" :key="history.id" class="fg-book-complaint-history__item">
                                                        <strong v-text="getStatusHistoryLabel(history)"></strong>
                                                        <small v-text="legibleFormatDate({dateString: history.changed_at || history.created_at, type: 'datetime'})"></small>
                                                    </span>
                                                </div>
                                                <span v-else class="text-muted small" v-text="MODULE.texts.card.notRegistered"></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12">
                                <div class="card">
                                    <div class="card-header fg-table-header-surface">
                                        <h3 class="fg-section-title mb-0" v-text="MODULE.texts.sections.technicalInfo"></h3>
                                    </div>
                                    <div class="card-body py-3">
                                        <div class="text-start mb-1">
                                            <span class="text-dark fw-bold colon-at-end" v-text="MODULE.texts.form.submittedIp"></span>
                                            <span v-text="forms[entity].createUpdate.data.submitted_ip || MODULE.texts.card.notSpecified" class="ms-2"></span>
                                        </div>
                                        <div class="text-start mb-1">
                                            <span class="text-dark fw-bold colon-at-end" v-text="MODULE.texts.form.submittedPlatform"></span>
                                            <span v-text="forms[entity].createUpdate.data.submitted_platform || MODULE.texts.card.notSpecified" class="ms-2"></span>
                                        </div>
                                        <div class="text-start mb-1">
                                            <span class="text-dark fw-bold colon-at-end" v-text="MODULE.texts.form.submittedBrowser"></span>
                                            <span v-text="forms[entity].createUpdate.data.submitted_browser || MODULE.texts.card.notSpecified" class="ms-2"></span>
                                        </div>
                                        <div class="text-start">
                                            <span class="text-dark fw-bold colon-at-end" v-text="MODULE.texts.form.submittedUserAgent"></span>
                                            <span v-text="forms[entity].createUpdate.data.submitted_user_agent || MODULE.texts.card.notSpecified" class="ms-2"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12">
                                <div class="card">
                                    <div class="card-header fg-table-header-surface">
                                        <h3 class="fg-section-title mb-0" v-text="MODULE.texts.sections.adminManagement"></h3>
                                    </div>
                                    <div class="card-body py-3">
                                        <div class="row g-3">
                                            <InputSlot
                                                hasDiv
                                                :title="MODULE.texts.form.status"
                                                :titleClass="[config.forms.classes.title, 'fw-semibold']"
                                                isRequired
                                                hasTextBottom
                                                :textBottomInfo="forms[entity].createUpdate.errors?.status"
                                                xl="12"
                                                lg="12">
                                                <template v-slot:input>
                                                    <v-select
                                                        v-model="forms[entity].createUpdate.data.status"
                                                        :options="statuses"
                                                        :class="config.forms.classes.select2"
                                                        :clearable="false"
                                                        :searchable="false"/>
                                                </template>
                                            </InputSlot>
                                            <InputTextArea
                                                v-model="forms[entity].createUpdate.data.public_response"
                                                hasDiv
                                                :title="MODULE.texts.form.publicResponse"
                                                :titleClass="[config.forms.classes.title, 'fw-semibold']"
                                                maxlength="600"
                                                rows="4"
                                                hasTextBottom
                                                :textBottomInfo="forms[entity].createUpdate.errors?.public_response"
                                                xl="12"
                                                lg="12"/>
                                            <InputTextArea
                                                v-model="forms[entity].createUpdate.data.admin_response"
                                                hasDiv
                                                :title="MODULE.texts.form.adminResponse"
                                                :titleClass="[config.forms.classes.title, 'fw-semibold']"
                                                isRequired
                                                maxlength="600"
                                                rows="5"
                                                hasTextBottom
                                                :textBottomInfo="forms[entity].createUpdate.errors?.admin_response"
                                                xl="12"
                                                lg="12"/>
                                            <InputTextArea
                                                v-model="forms[entity].createUpdate.data.status_note"
                                                hasDiv
                                                :title="MODULE.texts.form.statusNote"
                                                :titleClass="[config.forms.classes.title, 'fw-semibold']"
                                                maxlength="500"
                                                rows="2"
                                                hasTextBottom
                                                :textBottomInfo="forms[entity].createUpdate.errors?.status_note"
                                                xl="12"
                                                lg="12"/>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="fg-modal__footer">
                    <button
                        type="button"
                        class="fg-button fg-button--secondary"
                        data-bs-dismiss="modal"
                        v-text="MODULE.texts.modal.close">
                    </button>
                    <button
                        type="button"
                        class="fg-button"
                        @click="saveEntity"
                        :disabled="isSaving">
                        <i class="fa fa-save"></i>
                        <span class="ms-2" v-text="MODULE.texts.modal.save"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import ResponsiveTable from "@/Shared/ResponsiveTable.vue";
import TableActionsDropdown from "@System/Components/Generics/TableActionsDropdown.vue";
import FgEmptyState from "@System/Components/Figma/FgEmptyState.vue";
import FgPageHeader from "@System/Components/Figma/FgPageHeader.vue";
import {navbarItem} from "@System/Layout/Menu.js";
import InitialPageMixin from "@System/Helpers/InitialPageMixin.js";
import * as Alerts from "@System/Helpers/Alerts.js";
import { initCrudModule } from "@System/Helpers/ModuleFactory.js";
import * as Forms from "@System/Helpers/Forms.js";
import * as Requests from "@System/Helpers/Requests.js";
import * as Utils from "@System/Helpers/Utils.js";
import {STATUS_BADGE_CUSTOM_BOOK_COMPLAINTS} from "@System/Helpers/ModuleConstants.js";

const MODULE_CONFIG = {
    entity: "book_complaints",
    menuId: "menu-customers-book_complaints",
    pageTitle: "Libro de reclamaciones y sugerencias",
    breadcrumbParent: "Operaciones y clientes",
    perPage: 6
};

const FORM_FIELDS = {
    branch: null,
    identity_document_type: null,
    document_number: "",
    name: "",
    email: "",
    phone_number: "",
    type: "",
    description: "",
    request: "",
    evidence: "",
    admin_response: "",
    public_response: "",
    status_note: "",
    submitted_ip: "",
    submitted_user_agent: "",
    submitted_platform: "",
    submitted_browser: "",
    status: null,
    attachments: [],
    status_histories: [],
    created_at: "",
    copy: null
};

const FORM_FIELD_CONFIG = {
    branch: {getCode: true},
    identity_document_type: {getCode: true},
    document_number: {trim: true},
    name: {trim: true},
    email: {normalize: true},
    phone_number: {normalize: true},
    type: {trim: true},
    description: {trim: true},
    request: {normalize: true},
    evidence: {normalize: true},
    admin_response: {trim: true},
    public_response: {trim: true},
    status_note: {trim: true},
    submitted_ip: {normalize: true},
    submitted_user_agent: {normalize: true},
    submitted_platform: {normalize: true},
    submitted_browser: {normalize: true},
    status: {getCode: true}
};

const VALIDATION_RULES = {
    status: {required: true},
    admin_response: {required: false},
    public_response: {required: false},
    status_note: {required: false}
};

const ERROR_LABELS = {
    status: "Estado",
    admin_response: "Respuesta del administrador",
    public_response: "Respuesta pública",
    status_note: "Nota de cambio de estado",
    required: "Es obligatorio"
};

const TEXTS = {
    loading: `Cargando ${MODULE_CONFIG.pageTitle}...`,
    filters: {
        filterBy: "Filtrar por",
        search: "Búsqueda"
    },
    actions: {
        search: "Buscar",
        add: "Agregar",
        edit: "Editar",
        manage: "Gestionar"
    },
    card: {
        noEmail: "Sin correo registrado",
        noPhone: "Sin teléfono registrado",
        noContact: "Sin información de contacto",
        noDescription: "Sin descripción registrada",
        noRequest: "Sin pedido registrado",
        notSpecified: "No especifica",
        notRegistered: "No registrado"
    },
    sections: {
        clientData: "Datos del cliente",
        complaintDetail: "Detalle",
        technicalInfo: "Información técnica",
        adminManagement: "Gestión administrativa",
        evidence: "Adjuntos e historial"
    },
    form: {
        branch: "Sucursal",
        documentNumber: "Número de documento",
        identityDocumentType: "Tipo de documento",
        name: "Nombre",
        email: "Correo electrónico",
        phoneNumber: "Celular",
        description: "Descripción",
        request: "Pedido del cliente",
        dateTime: "Fecha y hora",
        currentStatus: "Estado actual",
        status: "Estado",
        adminResponse: "Respuesta interna",
        publicResponse: "Respuesta pública",
        statusNote: "Nota de cambio de estado",
        attachments: "Adjuntos",
        statusHistory: "Historial",
        submittedIp: "IP enviada",
        submittedPlatform: "Plataforma",
        submittedBrowser: "Navegador",
        submittedUserAgent: "User Agent"
    },
    modal: {
        close: "Cerrar",
        save: "Guardar reclamación"
    }
};

const FILTER_OPTIONS = [
    {code: "all", label: "Todos los filtros"},
    {code: "document_number", label: "Número de documento"},
    {code: "name", label: "Nombre"},
    {code: "email", label: "Correo electrónico"},
    {code: "phone_number", label: "Celular"}
];

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
    components: {ResponsiveTable, TableActionsDropdown, FgEmptyState, FgPageHeader},
    mixins: [InitialPageMixin],
    name: "BookComplaintsMain",
    data() {

        const crudModule = initCrudModule({entity: MODULE.config.entity, menuId: MODULE.config.menuId, pageTitle: MODULE.config.pageTitle});

        crudModule.lists[MODULE.config.entity].filters.filter_by = MODULE.filterOptions[0];
        crudModule.forms[MODULE.config.entity].createUpdate.data = Forms.initFormData(MODULE.formFields);

        return {
            ...crudModule,
            MODULE: MODULE,
            isInitialized: false,
            isSaving: false,
            bookComplaintsStatusBadgeVariants: STATUS_BADGE_CUSTOM_BOOK_COMPLAINTS
        };

    },
    mounted: async function() {

        navbarItem("menu-parent-operations", {addClass: "open"});
        navbarItem(this.config.entity.page.menu.id, {});

        const initParams = await this.loadInitialParams(() => this.initParams());

        this.isInitialized = true;

        if(initParams) {

            this.listEntity({});

        }

    },
    methods: {
        async initParams() {

            const response = await Requests.get({route: this.routeActions.initParams, data: {page: "main"}, showAlert: true});

            this.options[this.entity] = response?.data?.config?.[this.entity] ?? {};
            this.options.identity_document_types = response?.data?.config?.identity_document_types ?? {};
            this.options.branches = response?.data?.config?.branches ?? {};

            return Requests.valid({result: response});

        },
        // List
        async listEntity(params = null) {

            const entityList   = this.lists[this.entity];
            const emptyRecords = {total: 0, data: []};
            const filters      = (entityList.appliedFilters ??= {...entityList.filters});
            const filterData   = {per_page: this.MODULE.config.perPage, filter_by: filters.filter_by?.code, word: filters.word};

            entityList.extras.loading = true;
            entityList.extras.loadFailed = false;
            this.entityList.records = Requests.emptyPagination();

            try {

                const url = this.isDefined(params) && typeof params === "object" ? params.url : params;

                let requestUrl  = url || entityList.extras.route;
                let requestData = {};

                if(this.isDefined(url)) {

                    const urlObj = new URL(url, window.location.origin);

                    Object.entries(filterData).forEach(([key, value]) => {

                        if(value != null) urlObj.searchParams.set(key, value);
                        else urlObj.searchParams.delete(key);

                    });

                    requestUrl = `${urlObj.pathname}${urlObj.search}`;

                }else {

                    requestData = filterData;

                }

                const response = await Requests.get({route: requestUrl, data: requestData});
                entityList.extras.loadFailed = !response?.bool;

                entityList.records = response?.bool ? (response.data || Requests.emptyPagination()) : Requests.emptyPagination();

            }catch(error) {

                entityList.extras.loadFailed = true;
                entityList.records = emptyRecords;

            }finally {

                entityList.extras.loading = false;

            }

        },
        handleSearch() {
            this.entityList.appliedFilters = {...this.entityList.filters};
            return this.listEntity({});
        },
        // Forms
        openModal(record = null) {

            if(!this.isDefined(record)) return;

            const entityForms = this.forms[this.entity].createUpdate;

            entityForms.errors = {};
            Forms.clearFormData(entityForms.data, this.MODULE.formFields);

            entityForms.data.id = record?.id;

            // Mapear datos del record
            Object.keys(this.MODULE.formFields).forEach(key => {

                if(key === "status") {

                    entityForms.data.status = this.statuses.find(e => e.code === record?.status) || null;

                }else if(key === "identity_document_type") {

                    const identityDocType = this.identityDocumentTypes.find(e => e.code === record?.identity_document_type_id);
                    entityForms.data.identity_document_type = identityDocType || null;

                }else if(key === "branch") {

                    entityForms.data.branch = record?.branch || null;

                }else if(key === "copy") {

                    // Guardar copia del estado original
                    entityForms.data.copy = {
                        status: this.statuses.find(e => e.code === record?.status) || null
                    };

                }else {

                    entityForms.data[key] = record?.[key] ?? this.MODULE.formFields[key];

                }

            });

            Alerts.modals({type: "show", id: entityForms.extras.modals.default.id});
            Alerts.tooltips({show: true, time: 500});

        },
        async saveEntity() {

            if(this.isSaving) return;

            const entityForms = this.forms[this.entity].createUpdate;

            Alerts.swals({});

            entityForms.errors = {};
            this.isSaving = true;

            try {

                const formData   = Utils.cloneJson(entityForms.data);
                const validation = Forms.validateFormData(formData, this.MODULE.validationRules, {isDescriptive: true, errorLabels: this.MODULE.errorLabels});

                if(!validation.bool) {

                    Alerts.generateAlert({messages: Utils.getErrors({errors: validation.errors}), msgContent: this.config.messages.errorValidate});
                    this.isSaving = false;
                    return;

                }

                const preparedData  = Forms.prepareFormData(formData, this.MODULE.formFieldConfig);
                const id            = preparedData.id;
                const isUpdate       = this.isDefined(id);
                const requestMethod  = isUpdate ? "patch" : "post";
                const route          = this.routeActions[isUpdate ? "update" : "store"];

                // Preparar datos para envío
                const dataToSend = {
                    status: preparedData.status,
                    admin_response: preparedData.admin_response,
                    public_response: preparedData.public_response,
                    status_note: preparedData.status_note
                };

                const result = await Requests[requestMethod]({route, data: dataToSend, id});

                if(Requests.valid({result})) {

                    Alerts.modals({type: "hide", id: entityForms.extras.modals.default.id});
                    Alerts.generateAlert({type: "success", msgContent: result.data.msg});

                    Forms.clearFormData(entityForms.data, this.MODULE.formFields);

                    const entityList  = this.entityList;
                    const currentPage = entityList?.records?.current_page ?? 1;

                    this.listEntity({url: `${entityList?.extras?.route || ""}?page=${currentPage}`});

                }else {

                    Forms.handleFormResponseErrors({result, formErrorsObject: entityForms.errors, config: this.config});

                }

            }catch(error) {

                Alerts.generateAlert({type: "error", messages: [error], msgContent: this.config.messages.catchError});

            }finally {

                this.isSaving = false;

            }

        },
        // Utils
        getType(record) {

            return (this.types ?? []).find(e => e.code === record?.type) ?? null;

        },
        getStatusHistoryLabel(history) {

            const status = history?.new_status || history?.status;
            const found = (this.statuses ?? []).find(e => e.code === status);

            return found?.label ?? status ?? this.MODULE.texts.card.notSpecified;

        },
        bookComplaintAttachmentUrl(attachment) {

            return `${this.config.entity.routes.consult}/attachments/${attachment.id}`;

        },
        // Others
        isDefined(value) {

            return Utils.isDefined({value});

        },
        legibleFormatDate({dateString = null, type = "datetime"}) {

            return Utils.legibleFormatDate({dateString, type});

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
                {title: "Atención al cliente"},
                {title: "Libro de reclamaciones", active: true}
            ];

        },
        filterByOptions() {

            return this.MODULE.filterOptions;

        },
        identityDocumentTypes() {

            return (this.options?.identity_document_types?.records ?? []).map(e => ({code: e.id, label: e.name, data: e}));

        },
        types() {

            return (this.options?.[this.entity]?.types ?? []).map(e => ({code: e.code, label: e.label, data: e}));

        },
        statuses() {

            return (this.options?.[this.entity]?.statuses ?? []).map(e => ({code: e.code, label: e.label}));

        },
        isUpdate() {

            return this.isDefined(this.forms[this.entity].createUpdate.data.id);

        },
        modalTitles() {

            return this.forms[this.entity].createUpdate.extras.modals.default.titles || {
                store: `AGREGAR ${this.MODULE.config.pageTitle.toUpperCase()}`,
                update: `GESTIONAR ${this.MODULE.config.pageTitle.toUpperCase()}`
            };

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

        }

    }
};
</script>

<style scoped>
</style>
