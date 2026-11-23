<template>
    <FgCatalogList
        kind="categories"
        title="Categorías"
        singular="categoría"
        plural="categorías"
        list-title="Categorías registradas"
        description="Organiza los artículos de tu catálogo por categoría."
        v-model:filter-by="filterByValue"
        v-model:word="filterWordValue"
        :filter-options="filterByOptions"
        :placeholder="searchPlaceholder"
        :applied-word="entityList.appliedFilters?.word?.trim()"
        :records="entityList.records"
        :initial-loading="initialLoading"
        :loading="entityList.extras.loading"
        :exporting="isExporting"
        :failed="listFailed"
        @search="handleSearch"
        @page="listEntity"
        @retry="listEntity({})"
        @add="openModal()"
        @import="openImportModal"
        @export="downloadRecords">
        <template #row="{record}">
            <td class="fg-align-center"><InternalCodeValue :value="record.internal_code"/></td>
            <td>
                <span class="fg-product-name">{{ record.name }}</span>
                <ExpandableText v-if="record.description" :text="record.description"/>
            </td>
            <td class="fg-align-center"><CatalogUsageBreakdown :record="record" :enabled-types="options.usage_types"/></td>
            <td class="fg-align-center"><StatusBadge :status="record.status" :formatted-status="record.formatted_status"/></td>
            <td class="fg-align-center">
                <TableActionsDropdown :label="`Acciones de ${record.name}`">
                    <li><button type="button" class="fg-menu__item" @click="openModal(record)">Editar categoría</button></li>
                </TableActionsDropdown>
            </td>
        </template>
    </FgCatalogList>

    <CatalogReferenceImportModal
        ref="categoryImportModal"
        modal-id="categoryImportModal"
        resource="categorías"
        template-file-name="plantilla_categorias.xlsx"
        :import-route="routeActions.import"
        :internal-code-prefix="internalCodePrefixes.category"
        @imported="listEntity({})"/>

    <FormModal
        :modal-id="forms[entity].createUpdate.extras.modals.default.id"
        :title="modalTitles.createUpdate[isUpdate ? 'update' : 'store']"
        modal-size="modal-lg"
        :close-disabled="isSaving"
        :submit-disabled="isSaving"
        :submit-button-text="isSaving ? 'Guardando…' : isUpdate ? 'Guardar cambios' : 'Guardar categoría'"
        @submit="saveEntity">
        <template #body>
            <form class="fg-stack" @submit.prevent="saveEntity">
                <div class="fg-section-heading"><h3 class="fg-section-title">Datos de la categoría</h3><span class="fg-help"><span class="fg-required">*</span> Obligatorios</span></div>
                <div class="fg-grid">
                    <InternalCodeInput v-model="forms[entity].createUpdate.data.internal_code" v-bind="internalCodeInputProps(forms[entity].createUpdate.data)" title="Código interno" is-required show-char-counter has-text-bottom :text-bottom-info="forms[entity].createUpdate.errors?.internal_code" :disabled="isSaving">
                        <template v-if="!isUpdate" #action><button type="button" class="fg-link" :disabled="isSaving" :title="MODULE.texts.form.generateCodeTooltip" @click="generateCodeAction($event)">Generar</button></template>
                    </InternalCodeInput>
                    <InputText v-model="forms[entity].createUpdate.data.name" title="Nombre de la categoría" :placeholder="placeholders.names.category" has-text-bottom :text-bottom-info="forms[entity].createUpdate.errors?.name" :disabled="isSaving" is-required show-char-counter maxlength="50"/>
                </div>
                <div class="fg-grid">
                    <InputText v-model="forms[entity].createUpdate.data.description" title="Descripción" :placeholder="placeholders.optional" has-text-bottom :text-bottom-info="forms[entity].createUpdate.errors?.description" :disabled="isSaving" show-char-counter maxlength="100"/>
                    <FgField label="Estado" required :error="forms[entity].createUpdate.errors?.status">
                        <FgSelect v-model="forms[entity].createUpdate.data.status" :options="statuses" :disabled="isSaving" :clearable="false" :searchable="false"/>
                    </FgField>
                </div>
            </form>
        </template>
    </FormModal>
</template>

<script>
import FgSelect from "@System/Components/Figma/FgSelect.vue";
import FgField from "@System/Components/Figma/FgField.vue";
import FgCatalogList from "@System/Components/Catalogs/FgCatalogList.vue";
import {downloadCatalogList} from "@System/Components/Catalogs/downloadCatalogList.js";
import {navbarItem} from "@System/Layout/Menu.js";
import ExpandableText from "@System/Components/Generics/ExpandableText.vue";
import * as Alerts from "@System/Helpers/Alerts.js";
import {initCrudModule} from "@System/Helpers/ModuleFactory.js";
import * as Forms from "@System/Helpers/Forms.js";
import * as Requests from "@System/Helpers/Requests.js";
import * as Utils from "@System/Helpers/Utils.js";
import InternalCodePrefixMixin from "@System/Mixins/InternalCodePrefixMixin.js";
import InternalCodeInput from "@System/Components/Catalogs/InternalCodeInput.vue";
import {catalogFormPlaceholders} from "@System/Components/Catalogs/catalogFormPlaceholders.js";
import CatalogUsageBreakdown from "@System/Components/Catalogs/CatalogUsageBreakdown.vue";
import CatalogReferenceImportModal from "@System/Components/Catalogs/References/CatalogReferenceImportModal.vue";
import InternalCodeValue from "@System/Components/Catalogs/InternalCodeValue.vue";
import FormModal from "@System/Components/Generics/FormModal.vue";
import TableActionsDropdown from "@System/Components/Generics/TableActionsDropdown.vue";

const MODULE_CONFIG = {
    entity: "categories",
    menuId: "menu-items-categories",
    pageTitle: "Categorías",
    pageTitleSingular: "Categoría",
    internalCodeEntity: "category",
    breadcrumbParent: "Catálogo e inventario",
    perPage: 10
};

const FORM_FIELDS = {
    internal_code: "",
    name: "",
    description: "",
    status: null
};

const FORM_FIELD_CONFIG = {
    internal_code: {trim: false},
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
        edit: "Editar categoría"
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
        save: "Guardar categoría"
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
    components: {
        FgSelect,
        FgField,
        FgCatalogList,
        InternalCodeInput,
        ExpandableText,
        CatalogReferenceImportModal,
        CatalogUsageBreakdown,
        InternalCodeValue,
        FormModal,
        TableActionsDropdown,
    },
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
            placeholders: catalogFormPlaceholders,
            isExporting: false,
            listFailed: false,
            initialLoading: true
        };

    },
    mounted: async function() {

        navbarItem("menu-parent-items", {addClass: "open"});
        navbarItem(this.config.entity.page.menu.id, {});

        let initialized;

        try {
            initialized = await this.initParams();
            this.isInitialized = true;
        } finally {
            this.initialLoading = false;
        }

        if(initialized) await this.listEntity({});
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
                this.options.usage_types = response.data.config.usage_types ?? [];

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
            this.listFailed = false;
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

                const response = await Requests.get({route: requestUrl, data: requestData, showAlert: true});
                this.listFailed = !response?.bool;

                entityList.records = response?.bool ? (response.data || Requests.emptyPagination()) : Requests.emptyPagination();

            }catch(error) {
                this.listFailed = true;

                entityList.records = emptyRecords;

            }finally {

                entityList.extras.loading = false;

            }

        },
        handleSearch() {
            this.entityList.appliedFilters = {...this.entityList.filters};
            return this.listEntity({});
        },
        async downloadRecords() {
            if(this.isExporting) return;
            this.isExporting = true;
            try {
                await downloadCatalogList({
                    resource: "categorías",
                    route: this.routeActions.export,
                    filters: {
                        filter_by: this.entityList.appliedFilters?.filter_by?.code,
                        word: this.entityList.appliedFilters?.word || ""
                    },
                    fileName: "categorias.xlsx"
                });
            } finally {
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
                entityForms.data.internal_code = this.setEditableInternalCode(record.internal_code);
                entityForms.data.name          = record.name;
                entityForms.data.description   = record.description;
                entityForms.data.status        = statusOption;

            }else {

                // Set defaults for new record
                entityForms.data.internal_code = this.setEditableInternalCode(this.generateCode({length: 7}), true);
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

            entityForms.errors = {};
            try {

                const formData   = Utils.cloneJson(entityForms.data);
                const validation = this.validateFormData(formData);

                if(!validation.bool) {

                    entityForms.errors = validation.errors;
                    Alerts.generateAlert({
                        type: "error",
                        headerTitle: "Revisa los campos indicados",
                        messages: Forms.getDescriptiveErrors(validation.errors, this.MODULE.errorLabels)
                    });
                    return;

                }

                const preparedData  = Forms.prepareFormData(formData, this.MODULE.formFieldConfig);
                Object.assign(preparedData, this.internalCodePayload(preparedData.internal_code));
                const id            = preparedData.id;
                const isUpdate      = this.isDefined(id);
                const requestMethod = isUpdate ? "patch" : "post";
                const route         = this.routeActions[isUpdate ? "update" : "store"];
                this.isSaving = true;
                Alerts.loading({title: isUpdate ? "Guardando cambios" : "Guardando categoría"});
                const result        = await Requests[requestMethod]({route, data: preparedData, id}).finally(Alerts.close);

                if(Requests.valid({result})) {

                    Alerts.showMessage({
                        type: "success",
                        title: isUpdate ? "Cambios guardados" : "Categoría guardada",
                        text: result.data.msg
                    });

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
