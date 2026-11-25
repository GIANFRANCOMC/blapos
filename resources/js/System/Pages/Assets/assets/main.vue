<template>
    <Breadcrumb :list="breadcrumbTitles"/>
    <FgPageHeader title="Activos" description="Consulta y administra el catálogo de bienes de tu empresa."/>

    <!-- Filters -->
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
                :show-add-button="true"
                :title-class="[config.forms.classes.title]"
                :select-class="config.forms.classes.select2"
                @search="handleSearch"
                @add="openModal()"/>

            <!-- Records -->
            <div class="fg-table-scroll mb-2" tabindex="0" role="region" aria-label="Activos registrados">
                <table class="table fg-entity-table fg-entity-table--directory mb-0">
                    <thead class="fg-table-header-surface">
                        <tr>
                            <th scope="col" style="width: 20%;" class="text-center">Código interno</th>
                            <th scope="col" style="width: 56%;">Activo</th>
                            <th scope="col" style="width: 12%;" class="text-center">Estado</th>
                            <th scope="col" style="width: 12%;" class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="table-border-bottom-0 bg-white">
                        <tr class="fg-table__state" v-if="entityList.extras.loading">
                            <td colspan="4">
                                <Loader label="Buscando activos…"/>
                            </td>
                        </tr>
                        <template v-else-if="entityList.records.total > 0">
                            <tr v-for="record in entityList.records.data" :key="record.id">
                                <td class="text-center">
                                    <span v-text="record.internal_code" class="fw-medium d-block"></span>
                                </td>
                                <td>
                                    <span v-text="record.name" class="fw-medium d-block"></span>
                                    <small v-if="record.description" v-text="record.description" class="text-muted"></small>
                                </td>
                                <td class="text-center">
                                    <StatusBadge class="flex-shrink-none" :status="record.status" :formatted-status="record.formatted_status"/>
                                </td>
                                <td class="text-center">
                                    <button type="button" class="fg-button fg-button--secondary fg-button--small" @click="openModal(record)">
                                        <span v-text="MODULE.texts.actions.edit"></span>
                                    </button>
                                </td>
                            </tr>
                        </template>
                        <tr class="fg-table__state" v-else>
                            <td colspan="4">
                                <FgEmptyState v-if="entityList.extras.loadFailed" title="No se pudo completar la búsqueda" description="Reintenta con los mismos filtros." role="alert">
                                    <button type="button" class="fg-button fg-button--secondary" @click="listEntity({})">Reintentar</button>
                                </FgEmptyState>
                                <FgEmptyState v-else :title="entityList.appliedFilters?.word ? 'No hay coincidencias' : 'Aún no hay activos registrados'" :description="entityList.appliedFilters?.word ? 'Prueba con otros filtros.' : undefined"/>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <Paginator :links="entityList.records.links" :pagination="entityList.records" :loading="entityList.extras.loading" @clickPage="listEntity"/>

            <!-- Modal: Create/Update -->

        </div>
    </InitialLoadTransition>
    <div class="modal fade fg-modal fg-modal--compact" aria-labelledby="asset-form-title" :id="forms[entity].createUpdate.extras.modals.default.id" data-bs-backdrop="static" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
                <div class="fg-modal__header">
                    <h2 id="asset-form-title" class="fg-modal__title mb-0" v-text="modalTitles.createUpdate[isUpdate ? 'update' : 'store']"></h2>
                    <button type="button" class="fg-button fg-button--icon" data-bs-dismiss="modal" aria-label="Cerrar">
                        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                    </button>
                </div>
                <div class="fg-modal__body">
                    <form @submit.prevent="saveEntity">
                        <div class="row g-3">
                            <div class="col-12 col-sm-6">
                                <InternalCodeInput
                                    v-model="forms[entity].createUpdate.data.internal_code"
                                    v-bind="internalCodeInputProps(forms[entity].createUpdate.data)"
                                    hasDiv
                                    :title="MODULE.texts.form.internalCode"
                                    :titleClass="[config.forms.classes.title]"
                                    isRequired
                                    :showCharCounter="false"
                                    hasTextBottom
                                    :textBottomInfo="forms[entity].createUpdate.errors?.internal_code">
                                    <template v-slot:inputGroupAppend v-if="!isUpdate">
                                        <button type="button" class="fg-button fg-button--icon fg-field__action" @click="generateCodeAction" data-bs-toggle="tooltip" data-bs-placement="top" :title="MODULE.texts.form.generateCodeTooltip" :aria-label="MODULE.texts.form.generateCodeTooltip">
                                            <i class="fa fa-rotate"></i>
                                        </button>
                                    </template>
                                </InternalCodeInput>
                            </div>
                            <div class="col-12 col-sm-6">
                                <InputText
                                    v-model="forms[entity].createUpdate.data.name"
                                    hasDiv
                                    :title="MODULE.texts.form.name"
                                    :titleClass="[config.forms.classes.title]"
                                    isRequired
                                    maxlength="50"
                                    showCharCounter
                                    hasTextBottom
                                    :textBottomInfo="forms[entity].createUpdate.errors?.name"/>
                            </div>
                            <div class="col-12 col-sm-6">
                                <InputSlot
                                    hasDiv
                                    :title="MODULE.texts.form.category"
                                    :titleClass="[config.forms.classes.title]"
                                    hasTextBottom
                                    :textBottomInfo="forms[entity].createUpdate.errors?.asset_category_id">
                                    <template v-slot:defaultAppend>
                                        <button
                                            type="button"
                                            class="fg-quick-create-trigger fg-quick-create-trigger--link"
                                            @click="openAssetCategoryModal">
                                            <i class="fa-solid fa-circle-plus" aria-hidden="true"></i>
                                            <span>Agregar</span>
                                        </button>
                                    </template>
                                    <template #input="{id}">
                                        <v-select :input-id="id"
                                            v-model="forms[entity].createUpdate.data.asset_category"
                                            :options="assetCategories"
                                            :class="config.forms.classes.select2"
                                            :clearable="true"
                                            :searchable="true"/>
                                    </template>
                                </InputSlot>
                            </div>
                            <div class="col-12 col-sm-6">
                                <InputText
                                    v-model="forms[entity].createUpdate.data.description"
                                    hasDiv
                                    :title="MODULE.texts.form.description"
                                    :titleClass="[config.forms.classes.title]"
                                    maxlength="100"
                                    showCharCounter
                                    hasTextBottom
                                    :textBottomInfo="forms[entity].createUpdate.errors?.description"/>
                            </div>
                            <div class="col-12 col-sm-6">
                                <InputSlot
                                    hasDiv
                                    :title="MODULE.texts.form.status"
                                    :titleClass="[config.forms.classes.title]"
                                    isRequired
                                    hasTextBottom
                                    :textBottomInfo="forms[entity].createUpdate.errors?.status">
                                    <template #input="{id}">
                                        <v-select :input-id="id"
                                            v-model="forms[entity].createUpdate.data.status"
                                            :options="statuses"
                                            :class="config.forms.classes.select2"
                                            :clearable="false"
                                            :searchable="false"/>
                                    </template>
                                </InputSlot>
                            </div>
                            <div v-if="!isUpdate" class="col-12">
                                <p class="fg-notice mb-0">Después de guardar, puedes asignar el bien a una sucursal desde Gestión de activos.</p>
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
    <div class="modal fade fg-modal" id="assetCategoryQuickModal" data-bs-backdrop="static" tabindex="-1" role="dialog" aria-modal="true">
        <div class="modal-dialog modal-dialog-centered modal-md" role="document">
            <div class="modal-content">
                <div class="modal-header fg-modal__header">
                    <div>
                        <p class="fg-entity-modal__eyebrow mb-1">Activos</p>
                        <h2 class="modal-title fg-modal__title">Agregar categoría</h2>
                    </div>
                    <button type="button" class="fg-modal-close" data-bs-dismiss="modal" aria-label="Cerrar">
                        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                    </button>
                </div>
                <div class="modal-body fg-modal__body">
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
                <div class="modal-footer fg-modal__footer">
                    <button type="button" class="fg-button fg-button--secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="button" class="fg-button" :disabled="quickCategory.saving" @click="saveAssetCategory">
                        Agregar categoría
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import FgEmptyState from "@System/Components/Figma/FgEmptyState.vue";
import FgPageHeader from "@System/Components/Figma/FgPageHeader.vue";
import {navbarItem} from "@System/Layout/Menu.js";
import InitialPageMixin from "@System/Helpers/InitialPageMixin.js";
import * as Alerts from "@System/Helpers/Alerts.js";
import { initCrudModule } from "@System/Helpers/ModuleFactory.js";
import * as Forms from "@System/Helpers/Forms.js";
import * as Requests from "@System/Helpers/Requests.js";
import * as Utils from "@System/Helpers/Utils.js";
import InternalCodePrefixMixin from "@System/Mixins/InternalCodePrefixMixin.js";
import InternalCodeInput from "@System/Components/Catalogs/InternalCodeInput.vue";

const MODULE_CONFIG = {
    entity: "assets",
    menuId: "menu-configuration-assets",
    pageTitle: "Activos",
    pageTitleSingular: "Activo",
    internalCodeEntity: "asset",
    breadcrumbParent: "Equipo y activos",
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
    internal_code: {trim: false},
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
    mixins: [InitialPageMixin, InternalCodePrefixMixin],
    components: {FgEmptyState, FgPageHeader, InternalCodeInput},
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

        navbarItem("menu-parent-people", {addClass: "open"});
        navbarItem(this.config.entity.page.menu.id, {});

        const initParams = await this.loadInitialParams(() => this.initParams());

        this.isInitialized = true;

        if(initParams) {

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

            const entityForms = this.forms[this.entity].createUpdate;

            entityForms.errors = {};
            Forms.clearFormData(entityForms.data, this.MODULE.formFields);

            if(this.isDefined(record)) {

                // Map record data to form
                const statusOption = this.statuses.find(e => e.code === record?.status);

                entityForms.data.id            = record.id;
                entityForms.data.internal_code = this.setEditableInternalCode(record.internal_code);
                entityForms.data.asset_category = this.assetCategories.find(e => Number(e.code) === Number(record.asset_category_id)) ?? null;
                entityForms.data.name          = record.name;
                entityForms.data.description   = record.description;
                entityForms.data.status        = statusOption;

            }else {

                // Set defaults for new record
                entityForms.data.internal_code = this.setEditableInternalCode(this.generateCode({length: 7}), true);
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
                Object.assign(preparedData, this.internalCodePayload(preparedData.internal_code));
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
