<template>
    <Breadcrumb :list="breadcrumbTitles"/>

    <main class="br-entity">
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
        :show-download-button="true"
        :show-import-button="true"
        :group-secondary-actions="true"
        import-button-text="Cargar masivamente"
        download-button-text="Descargar Excel"
        :downloading="isExporting"
        :title-class="[config.forms.classes.title]"
        :select-class="config.forms.classes.select2"
        @search="handleSearch"
        @add="openModal()"
        @import="openImportModal"
        @download="downloadRecords"/>

    <CatalogReferenceImportModal
        ref="categoryImportModal"
        modal-id="categoryImportModal"
        resource="categorías"
        template-file-name="plantilla_categorias.xlsx"
        :import-route="routeActions.import"
        @imported="listEntity({})"/>

    <section class="br-entity-list" aria-label="Listado de categorías">
        <div class="table-responsive">
        <table class="table br-entity-table mb-0">
            <colgroup>
                <col style="width: 20%;">
                <col style="width: 44%;">
                <col style="width: 16%;">
                <col style="width: 13%;">
                <col style="width: 7%;">
            </colgroup>
            <thead class="br-table-header-surface">
                <tr>
                    <th>Identificación</th>
                    <th>Categoría</th>
                    <th class="text-center">Uso actual</th>
                    <th class="text-center">Estado</th>
                    <th><span class="visually-hidden">Acciones</span></th>
                </tr>
            </thead>
            <tbody>
                <tr v-if="entityList.extras.loading">
                    <td colspan="5" class="py-4">
                        <Loader/>
                    </td>
                </tr>
                <template v-else-if="entityList.records.total > 0">
                    <tr v-for="record in entityList.records.data" :key="record.id">
                        <td>
                            <div class="br-entity-identifier">
                                <span class="br-entity-identifier__label">Código interno</span>
                                <span class="br-entity-identifier__value">
                                    <span class="br-entity-code" v-text="record.internal_code"></span>
                                    <CopyButton :value="record.internal_code" label="Código interno"/>
                                </span>
                            </div>
                        </td>
                        <td>
                            <span class="br-entity-table__name" v-text="record.name"></span>
                            <span v-if="record.description" class="br-entity-table__description" v-text="record.description"></span>
                        </td>
                        <td class="text-center">
                            <span class="br-entity-table__meta">
                                {{ record.active_items_count || 0 }}
                                {{ Number(record.active_items_count || 0) === 1 ? "ítem activo" : "ítems activos" }}
                            </span>
                        </td>
                        <td class="text-center">
                            <StatusBadge class="flex-shrink-none" :status="record.status" :formatted-status="record.formatted_status"/>
                        </td>
                        <td class="text-center">
                            <TableActionsDropdown :label="`Acciones de ${record.name}`">
                                <li>
                                    <button type="button" class="dropdown-item" @click="openModal(record)">
                                        <i class="fa-solid fa-pen" aria-hidden="true"></i>
                                        <span>Editar</span>
                                    </button>
                                </li>
                            </TableActionsDropdown>
                        </td>
                    </tr>
                </template>
                <tr v-else>
                    <td colspan="5" class="py-4">
                        <WithoutData class="br-entity-empty-state" type="image" image-src="/System/assets/img/utils/without_data/empty_products.png?v=2"/>
                    </td>
                </tr>
            </tbody>
        </table>
        </div>
    </section>

    <nav v-if="!entityList.extras.loading && entityList.records.total > 0" class="d-flex justify-content-center mt-3" aria-label="Paginación de categorías">
        <Paginator :links="entityList.records.links" @clickPage="listEntity"/>
    </nav>
    </main>

    <FormModal
        :modal-id="forms[entity].createUpdate.extras.modals.default.id"
        :title="modalTitles.createUpdate[isUpdate ? 'update' : 'store']"
        modal-size="modal-lg"
        :submit-button-class="isUpdate ? 'br-btn-action-update' : 'br-btn-action-create'"
        :submit-disabled="isSaving"
        @submit="saveEntity">
        <template #body>
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
                                <template v-if="!isUpdate" v-slot:inputGroupAppend>
                                    <button type="button" class="br-input-action" @click="generateCodeAction" data-bs-toggle="tooltip" data-bs-placement="top" :title="MODULE.texts.form.generateCodeTooltip" :aria-label="MODULE.texts.form.generateCodeTooltip">
                                        <i class="fa-solid fa-rotate" aria-hidden="true"></i>
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
                                v-model="forms[entity].createUpdate.data.description"
                                hasDiv
                                :title="MODULE.texts.form.description"
                                :titleClass="[config.forms.classes.title]"
                                maxlength="100"
                                showCharCounter
                                hasTextBottom
                                :textBottomInfo="forms[entity].createUpdate.errors?.description"
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
                                        :searchable="false"
                                        append-to-body/>
                                </template>
                            </InputSlot>
                        </div>
                    </form>
        </template>
    </FormModal>
</template>

<script>
import * as Alerts from "@System/Helpers/Alerts.js";
import {initCrudModule} from "@System/Helpers/ModuleFactory.js";
import * as Forms from "@System/Helpers/Forms.js";
import * as Requests from "@System/Helpers/Requests.js";
import * as Utils from "@System/Helpers/Utils.js";
import InternalCodePrefixMixin from "@System/Mixins/InternalCodePrefixMixin.js";
import CatalogReferenceImportModal from "@System/Components/Catalogs/References/CatalogReferenceImportModal.vue";
import CopyButton from "@System/Components/CopyButton.vue";
import FormModal from "@System/Components/Generics/FormModal.vue";
import TableActionsDropdown from "@System/Components/Generics/TableActionsDropdown.vue";

const MODULE_CONFIG = {
    entity: "categories",
    menuId: "menu-items-categories",
    pageTitle: "Categorías",
    pageTitleSingular: "Categoría",
    internalCodeEntity: "category",
    breadcrumbParent: "Catálogo comercial",
    perPage: 10
};

const FORM_FIELDS = {
    internal_code: "",
    name: "",
    description: "",
    status: null
};

const FORM_FIELD_CONFIG = {
    internal_code: {trim: true},
    name: {trim: true},
    description: {normalize: true},
    status: {getCode: true}
};

const VALIDATION_RULES = {
    internal_code: {required: true},
    name: {required: true},
    description: {required: false},
    status: {required: true}
};

const ERROR_LABELS = {
    internal_code: "Código interno",
    name: "Nombre",
    description: "Descripción",
    status: "Estado"
};

const FILTER_OPTIONS = [
    {code: "all", label: "Todos los filtros"},
    {code: "internal_code", label: "Código interno"},
    {code: "name", label: "Nombre"},
    {code: "description", label: "Descripción"}
];

const TEXTS = {
    filters: {
        filterBy: "Filtrar por",
        search: "Búsqueda"
    },
    actions: {
        search: "Buscar",
        add: "Agregar categoría",
        edit: "Editar"
    },
    form: {
        internalCode: "Código interno",
        name: "Nombre",
        description: "Descripción",
        status: "Estado",
        generateCodeTooltip: "Generar aleatoriamente"
    },
    modal: {
        close: "Cerrar",
        save: "Guardar"
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
    name: "CategoriesMain",
    mixins: [InternalCodePrefixMixin],
    components: {CatalogReferenceImportModal, CopyButton, FormModal, TableActionsDropdown},
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
            isExporting: false
        };

    },
    mounted: async function() {

        Utils.navbarItem("menu-parent-items", {addClass: "open"});
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
        async downloadRecords() {

            if(this.isExporting) {

                return;

            }

            const confirmed = await Alerts.confirmDownload({resource: "el listado de categorías"});

            if(!confirmed) return;

            this.isExporting = true;
            Alerts.swals({type: "default", title: "Preparando reporte de categorías"});

            try {

                await Requests.download({
                    route: this.routeActions.export,
                    data: {
                        filter_by: this.entityList.filters.filter_by?.code,
                        word: this.entityList.filters.word
                    },
                    fileName: "categorias.xlsx",
                    showAlert: true
                });

            }finally {

                Alerts.swals({show: false});
                this.isExporting = false;

            }

        },
        openImportModal() {

            Alerts.tooltips({show: false});
            this.$refs.categoryImportModal?.open();

        },
        // Forms
        openModal(record = null) {

            const entityForms = this.forms[this.entity].createUpdate;

            entityForms.errors = {};
            Forms.clearFormData(entityForms.data, this.MODULE.formFields);

            if(this.isDefined(record)) {

                // Map record data to form
                const statusOption = this.statuses.find(e => e.code === record?.status);

                entityForms.data.id            = record.id;
                entityForms.data.internal_code = this.stripInternalCodePrefix(record.internal_code);
                entityForms.data.name          = record.name;
                entityForms.data.description   = record.description;
                entityForms.data.status        = statusOption;

            }else {

                // Set defaults for new record
                entityForms.data.internal_code = this.generateCode({length: 7});
                entityForms.data.status         = this.statuses.length > 0 ? this.statuses[0] : null;

            }

            Alerts.modals({type: "show", id: entityForms.extras.modals.default.id});
            Alerts.tooltips({show: true, time: 500});

        },
        generateCodeAction(event) {

            this.forms[this.entity].createUpdate.data.internal_code = this.generateCode({length: 7});

            Alerts.dismissTooltip(event?.currentTarget);

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

                    entityForms.errors = validation.errors;
                    Alerts.generateAlert({
                        type: "error",
                        messages: Forms.getDescriptiveErrors(validation.errors, this.MODULE.errorLabels),
                        msgContent: this.config.messages.errorValidate
                    });
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
        // Others
        isDefined(value) {

            return Utils.isDefined({value});

        },
        generateCode({length}) {

            return Utils.generateCode({length});

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
        modalTitles() {

            return {
                createUpdate: this.forms[this.entity].createUpdate.extras.modals.default.titles
            };

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
