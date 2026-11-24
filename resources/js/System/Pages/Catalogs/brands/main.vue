<template>
    <FgCatalogList
        kind="brands"
        title="Marcas"
        singular="marca"
        plural="marcas"
        list-title="Marcas registradas"
        description="Identifica las marcas que usas en tu catálogo."
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
            <td class="fg-align-center">
                <a v-if="record.website_url" class="fg-link" :href="record.website_url" target="_blank" rel="noopener noreferrer" :aria-label="`Visitar el sitio oficial de ${record.name} en una nueva pestaña`">Visitar sitio</a>
                <span v-else class="fg-help">Sin sitio oficial</span>
            </td>
            <td class="fg-align-center"><CatalogUsageBreakdown :record="record" :enabled-types="options.usage_types"/></td>
            <td class="fg-align-center"><StatusBadge :status="record.status" :formatted-status="record.formatted_status"/></td>
            <td class="fg-align-center">
                <TableActionsDropdown :label="`Acciones de ${record.name}`">
                    <li><button type="button" class="fg-menu__item" @click="openModal(record)">Editar marca</button></li>
                </TableActionsDropdown>
            </td>
        </template>
    </FgCatalogList>

    <CatalogReferenceImportModal
        ref="brandImportModal"
        modal-id="brandImportModal"
        resource="marcas"
        template-file-name="plantilla_marcas.xlsx"
        :import-route="routeActions.import"
        :internal-code-prefix="internalCodePrefixes.brand"
        @imported="listEntity({})"/>

    <FormModal
        :modal-id="brandForm.extras.modals.default.id"
        :title="modalTitles.createUpdate[isUpdate ? 'update' : 'store']"
        modal-size="modal-lg"
        :close-disabled="isSaving"
        :submit-disabled="isSaving"
        :submit-button-text="isSaving ? 'Guardando…' : isUpdate ? 'Guardar cambios' : 'Guardar marca'"
        @submit="saveEntity">
        <template #body>
            <form class="fg-stack" @submit.prevent="saveEntity">
                <div class="fg-section-heading"><h3 class="fg-section-title">Datos de la marca</h3><span class="fg-help"><span class="fg-required">*</span> Obligatorios</span></div>
                <div class="fg-grid">
                    <InternalCodeInput v-model="brandForm.data.internal_code" v-bind="internalCodeInputProps(brandForm.data)" title="Código interno" is-required show-char-counter has-text-bottom :text-bottom-info="brandForm.errors?.internal_code" :disabled="isSaving">
                        <template v-if="!isUpdate" #action><button type="button" class="fg-link" :disabled="isSaving" title="Generar un nuevo código interno para la marca" @click="generateInternalCode($event)">Generar</button></template>
                    </InternalCodeInput>
                    <InputText v-model="brandForm.data.name" title="Nombre de la marca" :placeholder="placeholders.names.brand" has-text-bottom :text-bottom-info="brandForm.errors?.name" :disabled="isSaving" is-required show-char-counter maxlength="100"/>
                </div>
                <div class="fg-grid">
                    <InputText v-model="brandForm.data.description" title="Descripción" :placeholder="placeholders.optional" has-text-bottom :text-bottom-info="brandForm.errors?.description" :disabled="isSaving" show-char-counter maxlength="250"/>
                    <FgField label="Estado" required :error="brandForm.errors?.status">
                        <FgSelect v-model="brandForm.data.status" :options="statuses" :disabled="isSaving" :clearable="false" :searchable="false"/>
                    </FgField>
                </div>
                <InputText v-model="brandForm.data.website_url" title="Sitio oficial" has-text-bottom :text-bottom-info="brandForm.errors?.website_url" :disabled="isSaving" placeholder="https://" maxlength="500"/>
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

const MODULE = {
    entity: "brands",
    menuId: "menu-items-brands",
    pageTitle: "Marcas",
    pageTitleSingular: "Marca",
    internalCodeEntity: "brand",
    breadcrumbParent: "Catálogo comercial",
    perPage: 10,
    fields: {
        internal_code: "",
        name: "",
        description: "",
        website_url: "",
        status: null
    },
    fieldConfig: {
        internal_code: {trim: false},
        name: {trim: true},
        description: {normalize: true},
        website_url: {normalize: true},
        status: {getCode: true}
    },
    validationRules: {
        internal_code: {required: true},
        name: {required: true},
        description: {required: false},
        website_url: {required: false},
        status: {required: true}
    },
    errorLabels: {
        internal_code: "Código interno",
        name: "Nombre",
        description: "Descripción",
        website_url: "Sitio oficial",
        status: "Estado"
    },
    filters: [
        {code: "all", label: "Todos los filtros"},
        {code: "internal_code", label: "Código interno"},
        {code: "name", label: "Nombre"},
        {code: "description", label: "Descripción"}
    ]
};


export default {
    name: "BrandsMain",
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
            entity: MODULE.entity,
            menuId: MODULE.menuId,
            pageTitle: MODULE.pageTitle,
            pageTitleSingular: MODULE.pageTitleSingular
        });

        crudModule.lists[MODULE.entity].filters.filter_by = MODULE.filters[0];
        crudModule.forms[MODULE.entity].createUpdate.data = Forms.initFormData(MODULE.fields);

        return {
            ...crudModule,
            MODULE,
            isSaving: false,
            placeholders: catalogFormPlaceholders,
            isExporting: false,
            listFailed: false,
            initialLoading: true
        };

    },
    async mounted() {

        navbarItem("menu-parent-items", {addClass: "open"});
        navbarItem(this.config.entity.page.menu.id, {});
        let initialized;

        try {
            initialized = await this.initParams();
        } finally {
            this.initialLoading = false;
        }

        if(initialized) await this.listEntity({});

        document.getElementById(this.brandForm.extras.modals.default.id)
            ?.addEventListener("hidden.bs.modal", this.resetForm);
    },
    beforeUnmount() {

        Alerts.tooltips({show: false});
        document.getElementById(this.brandForm.extras.modals.default.id)
            ?.removeEventListener("hidden.bs.modal", this.resetForm);

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
        handleSearch() {
            this.entityList.appliedFilters = {...this.entityList.filters};
            return this.listEntity({});
        },
        async listEntity(params = null) {

            const emptyRecords = {total: 0, data: [], links: []};
            const filters = (this.entityList.appliedFilters ??= {...this.entityList.filters});
            const filterData = {
                per_page: MODULE.perPage,
                filter_by: filters.filter_by?.code,
                word: filters.word
            };

            this.entityList.extras.loading = true;
            this.listFailed = false;
            this.entityList.records = Requests.emptyPagination();

            try {

                const url = this.isDefined(params) && typeof params === "object" ? params.url : params;
                let requestUrl = url || this.entityList.extras.route;
                let requestData = filterData;

                if(this.isDefined(url)) {

                    const urlObject = new URL(url, window.location.origin);

                    Object.entries(filterData).forEach(([key, value]) => {

                        if(value != null) {

                            urlObject.searchParams.set(key, value);

                        } else {
                            urlObject.searchParams.delete(key);
                        }

                    });

                    requestUrl = `${urlObject.pathname}${urlObject.search}`;
                    requestData = {};

                }

                const response = await Requests.get({
                    route: requestUrl,
                    data: requestData,
                    showAlert: true
                });

                this.listFailed = !response?.bool;
                this.entityList.records = response?.bool ? (response.data || Requests.emptyPagination()) : Requests.emptyPagination();

            }catch(error) {

                this.listFailed = true;
                this.entityList.records = emptyRecords;

            }finally {

                this.entityList.extras.loading = false;
                this.$nextTick(() => Alerts.tooltips({}));

            }

        },
        async downloadRecords() {
            if(this.isExporting) return;
            this.isExporting = true;
            try {
                await downloadCatalogList({
                    resource: "marcas",
                    route: this.routeActions.export,
                    filters: {
                        filter_by: this.entityList.appliedFilters?.filter_by?.code,
                        word: this.entityList.appliedFilters?.word || ""
                    },
                    fileName: "marcas.xlsx"
                });
            } finally {
                this.isExporting = false;
            }
        },
        openImportModal() {

            Alerts.tooltips({show: false});
            this.$refs.brandImportModal?.open();

        },
        openModal(record = null) {

            Alerts.tooltips({show: false});
            this.resetForm();

            if(this.isDefined(record)) {

                Object.assign(this.brandForm.data, {
                    id: record.id,
                    internal_code: this.setEditableInternalCode(record.internal_code),
                    name: record.name,
                    description: record.description,
                    website_url: record.website_url,
                    status: this.statuses.find(status => status.code === record.status) ?? null
                });

            }else {

                Object.assign(this.brandForm.data, {
                    internal_code: this.setEditableInternalCode(this.generateCode(), true),
                    status: this.statuses[0] ?? null
                });

            }

            Alerts.modals({type: "show", id: this.brandForm.extras.modals.default.id});
            this.$nextTick(() => Alerts.tooltips({time: 350}));

        },
        resetForm() {

            this.brandForm.data = Forms.initFormData(Utils.cloneJson(MODULE.fields));
            this.brandForm.errors = {};

        },
        generateInternalCode(event) {

            this.brandForm.data.internal_code = this.generateCode();
            Alerts.dismissTooltip(event?.currentTarget);

        },
        generateCode() {

            return Utils.generateCode({length: 7});

        },
        async saveEntity() {

            if(this.isSaving) return;

            this.brandForm.errors = {};
            try {

                const formData = Utils.cloneJson(this.brandForm.data);
                const validation = Forms.validateFormData(
                    formData,
                    MODULE.validationRules,
                    {isDescriptive: true, errorLabels: MODULE.errorLabels}
                );

                if(!validation.bool) {

                    this.brandForm.errors = validation.errors;
                    Alerts.generateAlert({
                        type: "error",
                        headerTitle: "Revisa los campos indicados",
                        messages: Forms.getDescriptiveErrors(validation.errors, this.MODULE.errorLabels)
                    });
                    return;

                }

                const data = Forms.prepareFormData(formData, MODULE.fieldConfig);
                Object.assign(data, this.internalCodePayload(data.internal_code));
                const id = data.id;
                const isUpdate = this.isDefined(id);
                const method = isUpdate ? "patch" : "post";
                const route = this.routeActions[isUpdate ? "update" : "store"];
                this.isSaving = true;
                Alerts.loading({title: isUpdate ? "Guardando cambios" : "Guardando marca"});
                const result = await Requests[method]({route, data, id}).finally(Alerts.close);

                if(Requests.valid({result})) {

                    Alerts.showMessage({
                        type: "success",
                        title: isUpdate ? "Cambios guardados" : "Marca guardada",
                        text: result.data.msg
                    });

                    const page = this.entityList?.records?.current_page ?? 1;
                    await this.listEntity({url: `${this.entityList?.extras?.route || ""}?page=${page}`});

                }else {

                    Forms.handleFormResponseErrors({
                        result,
                        formErrorsObject: this.brandForm.errors,
                        config: this.config,
                        errorLabels: this.MODULE.errorLabels
                    });

                }

            }catch(error) {

                Alerts.generateAlert({
                    type: "error",
                    messages: [error],
                    msgContent: this.config.messages.catchError
                });

            }finally {

                this.isSaving = false;

            }

        },
        isDefined(value) {

            return Utils.isDefined({value});

        }
    },
    computed: {
        brandForm() {

            return this.forms[MODULE.entity].createUpdate;

        },
        entityList() {

            return this.lists[MODULE.entity];

        },
        routeActions() {

            return this.config.entity.routes;

        },
        statuses() {

            return (this.options?.statuses ?? []).map(status => ({
                code: status.code,
                label: status.label
            }));

        },
        isUpdate() {

            return this.isDefined(this.brandForm.data.id);

        },
        modalTitles() {

            return {
                createUpdate: this.brandForm.extras.modals.default.titles
            };

        },
        breadcrumbTitles() {

            return [
                {title: MODULE.breadcrumbParent},
                this.config.entity.page
            ];

        },
        filterByOptions() {

            return MODULE.filters;

        },
        filterByValue: {
            get() {

                return this.entityList.filters?.filter_by ?? MODULE.filters[0];

            },
            set(value) {

                this.entityList.filters.filter_by = value;

            }
        },
        filterWordValue: {
            get() {

                return this.entityList.filters.word ?? "";

            },
            set(value) {

                this.entityList.filters.word = value;

            }
        },
        searchPlaceholder() {

            const filter = this.entityList.filters.filter_by;

            return filter
                ? `Buscar por ${(filter.label ?? "").toLowerCase()}`
                : "Buscar marca";

        }
    }
};
</script>
