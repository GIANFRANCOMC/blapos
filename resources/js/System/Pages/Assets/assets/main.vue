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
        @add="openModal()"/>

    <!-- Records -->
    <div class="list-section mb-1 mb-md-1 table-responsive">
        <table class="table table-hover">
            <thead class="align-middle bg-secondary text-center">
                <tr>
                    <th class="text-white" style="width: 20%;">CÓDIGO INTERNO</th>
                    <th class="text-white" style="width: 60%;" v-text="MODULE.config.pageTitleSingular"></th>
                    <th class="text-white" style="width: 10%;">ESTADO</th>
                    <th class="text-white" style="width: 10%;">ACCIONES</th>
                </tr>
            </thead>
            <tbody class="table-border-bottom-0 bg-white">
                <tr v-if="entityList.extras.loading">
                    <td colspan="99">
                        <Loader/>
                    </td>
                </tr>
                <template v-else-if="entityList.records.total > 0">
                    <tr v-for="record in entityList.records.data" :key="record.id">
                        <td class="text-center">
                            <span v-text="record.internal_code" class="fw-semibold d-block"></span>
                        </td>
                        <td>
                            <span v-text="record.name" class="fw-semibold d-block"></span>
                            <small v-if="record.description" v-text="record.description" class="text-muted"></small>
                        </td>
                        <td class="text-center">
                            <StatusBadge class="flex-shrink-none" :status="record.status" :formatted-status="record.formatted_status"/>
                        </td>
                        <td class="text-center">
                            <button type="button" class="btn btn-xs btn-warning waves-effect" @click="openModal(record)">
                                <span v-text="MODULE.texts.actions.edit"></span>
                            </button>
                        </td>
                    </tr>
                </template>
                <tr v-else>
                    <td colspan="99">
                        <WithoutData type="image"/>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <nav v-if="!entityList.extras.loading && entityList.records.total > 0" class="d-flex justify-content-center">
        <Paginator :links="entityList.records.links" @clickPage="listEntity"/>
    </nav>

    <!-- Modal: Create/Update -->
    <div class="modal fade" :id="forms[entity].createUpdate.extras.modals.default.id" data-bs-backdrop="static" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-uppercase fw-bold" v-text="modalTitles.createUpdate[isUpdate ? 'update' : 'store']"></h5>
                    <button type="button" class="btn-header-modal" data-bs-dismiss="modal">
                        <i class="fa fa-times icon-close-modal"></i>
                    </button>
                </div>
                <div class="modal-body">
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
                                    <button type="button" :class="['btn waves-effect', isUpdate ? 'btn-warning' : 'btn-primary']" @click="generateCodeAction" data-bs-toggle="tooltip" data-bs-placement="top" :title="MODULE.texts.form.generateCodeTooltip">
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
                            <InputSlot
                                hasDiv
                                :title="MODULE.texts.form.category"
                                :titleClass="[config.forms.classes.title]"
                                hasTextBottom
                                :textBottomInfo="forms[entity].createUpdate.errors?.asset_category_id"
                                xl="6"
                                lg="6">
                                <template v-slot:defaultAppend>
                                    <button
                                        type="button"
                                        class="br-quick-create-trigger br-quick-create-trigger--link"
                                        @click="openAssetCategoryModal">
                                        <i class="fa-solid fa-circle-plus" aria-hidden="true"></i>
                                        <span>Agregar</span>
                                    </button>
                                </template>
                                <template v-slot:input>
                                    <v-select
                                        v-model="forms[entity].createUpdate.data.asset_category"
                                        :options="assetCategories"
                                        :class="config.forms.classes.select2"
                                        :clearable="true"
                                        :searchable="true"/>
                                </template>
                            </InputSlot>
                            <InputText
                                v-model="forms[entity].createUpdate.data.description"
                                hasDiv
                                :title="MODULE.texts.form.description"
                                :titleClass="[config.forms.classes.title]"
                                maxlength="100"
                                showCharCounter
                                hasTextBottom
                                :textBottomInfo="forms[entity].createUpdate.errors?.description"
                                xl="6"
                                lg="6"/>
                            <InputSlot
                                hasDiv
                                :title="MODULE.texts.form.status"
                                :titleClass="[config.forms.classes.title]"
                                isRequired
                                hasTextBottom
                                :textBottomInfo="forms[entity].createUpdate.errors?.status"
                                xl="3"
                                lg="3">
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
                <div class="modal-footer">
                    <button
                        type="button"
                        class="btn btn-secondary waves-effect"
                        data-bs-dismiss="modal"
                        v-text="MODULE.texts.modal.close">
                    </button>
                    <button
                        type="button"
                        :class="['btn waves-effect', isUpdate ? 'btn-warning' : 'btn-primary']"
                        @click="saveEntity"
                        :disabled="isSaving">
                        <i class="fa fa-save"></i>
                        <span class="ms-2" v-text="MODULE.texts.modal.save"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade br-entity-modal" id="assetCategoryQuickModal" data-bs-backdrop="static" tabindex="-1" role="dialog" aria-modal="true">
        <div class="modal-dialog modal-dialog-centered modal-md" role="document">
            <div class="modal-content">
                <div class="modal-header br-entity-modal__header">
                    <div>
                        <p class="br-entity-modal__eyebrow mb-1">Activos</p>
                        <h2 class="modal-title br-entity-modal__title">Agregar categoría</h2>
                    </div>
                    <button type="button" class="br-modal-close" data-bs-dismiss="modal" aria-label="Cerrar">
                        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                    </button>
                </div>
                <div class="modal-body br-entity-modal__body">
                    <div class="row g-3">
                        <InputText
                            v-model="quickCategory.name"
                            hasDiv
                            title="Nombre"
                            :titleClass="[config.forms.classes.title]"
                            isRequired
                            maxlength="150"
                            hasTextBottom
                            :textBottomInfo="quickCategory.errors?.name"
                            xl="12"
                            lg="12"/>
                        <InputText
                            v-model="quickCategory.description"
                            hasDiv
                            title="Descripción"
                            :titleClass="[config.forms.classes.title]"
                            maxlength="500"
                            hasTextBottom
                            :textBottomInfo="quickCategory.errors?.description"
                            xl="12"
                            lg="12"/>
                    </div>
                </div>
                <div class="modal-footer br-entity-modal__footer">
                    <button type="button" class="br-btn br-btn-cancel" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="br-btn br-btn-action-create" :disabled="quickCategory.saving" @click="saveAssetCategory">
                        Agregar categoría
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
    entity: "assets",
    menuId: "menu-configuration-assets",
    pageTitle: "Activos",
    pageTitleSingular: "Activo",
    internalCodeEntity: "asset",
    breadcrumbParent: "Infraestructura",
    perPage: 10
};

const FORM_FIELDS = {
    internal_code: "",
    asset_category: null,
    name: "",
    description: "",
    status: null
};

const FORM_FIELD_CONFIG = {
    internal_code: {trim: true},
    asset_category: {mapToField: "asset_category_id"},
    name: {trim: true},
    description: {normalize: true},
    status: {getCode: true}
};

const VALIDATION_RULES = {
    internal_code: {required: true},
    asset_category: {required: false},
    name: {required: true},
    description: {required: false},
    status: {required: true}
};

const ERROR_LABELS = {
    internal_code: "Código interno",
    asset_category: "Categoría",
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
        add: "Agregar",
        edit: "Editar"
    },
    form: {
        internalCode: "Código interno",
        category: "Categoría",
        name: "Nombre",
        description: "Descripción",
        status: "Estado",
        generateCodeTooltip: "Generar aleatoriamente"
    },
    modal: {
        close: "Cerrar",
        save: "Guardar activo"
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
    name: "AssetsMain",
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
            quickCategory: {
                name: "",
                description: "",
                errors: {},
                saving: false
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
                this.options.categories = response.data.config.categories || [];
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
                entityForms.data.asset_category = this.assetCategories.find(e => Number(e.code) === Number(record.asset_category_id)) ?? null;
                entityForms.data.name          = record.name;
                entityForms.data.description   = record.description;
                entityForms.data.status        = statusOption;

            }else {

                // Set defaults for new record
                entityForms.data.internal_code = this.generateCode({length: 7});
                entityForms.data.asset_category = null;
                entityForms.data.status         = this.statuses.length > 0 ? this.statuses[0] : null;

            }

            Alerts.modals({type: "show", id: entityForms.extras.modals.default.id});
            Alerts.tooltips({show: true, time: 500});

        },
        generateCodeAction() {

            this.forms[this.entity].createUpdate.data.internal_code = this.generateCode({length: 7});

            Alerts.toastrs({type: "success", subtitle: "Código interno generado correctamente."});
            Alerts.tooltips({show: false});

        },
        openAssetCategoryModal() {

            this.quickCategory = {name: "", description: "", errors: {}, saving: false};
            Alerts.modals({type: "show", id: "assetCategoryQuickModal"});

        },
        async saveAssetCategory() {

            if(this.quickCategory.saving) return;

            this.quickCategory.errors = {};

            if(!this.quickCategory.name?.trim()) {

                this.quickCategory.errors = {name: ["Campo obligatorio."]};
                return;

            }

            this.quickCategory.saving = true;

            const result = await Requests.post({
                route: `${this.routeActions.store}/categories`,
                data: {
                    name: this.quickCategory.name,
                    description: this.quickCategory.description,
                    status: "active"
                }
            });

            this.quickCategory.saving = false;

            if(Requests.valid({result})) {

                const record = result.data.data;
                this.options.categories = [...(this.options.categories || []), record]
                    .sort((first, second) => String(first.name).localeCompare(String(second.name), "es", {sensitivity: "base"}));
                this.forms[this.entity].createUpdate.data.asset_category = {code: record.id, label: record.name, data: record};
                Alerts.modals({type: "hide", id: "assetCategoryQuickModal"});
                Alerts.generateAlert({type: "success", msgContent: result.data.msg});

            }else {

                this.quickCategory.errors = result.errors || {};

            }

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
        assetCategories() {

            return (this.options?.categories ?? []).map(e => ({code: e.id, label: e.name, data: e}));

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

<style scoped>
</style>
