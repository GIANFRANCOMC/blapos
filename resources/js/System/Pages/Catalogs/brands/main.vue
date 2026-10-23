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
            filter-by-title="Filtrar por"
            search-title="Búsqueda"
            search-button-text="Buscar"
            add-button-text="Agregar marca"
            :show-add-button="true"
            :show-download-button="true"
            :show-import-button="true"
            :group-secondary-actions="true"
            import-button-text="Cargar masivamente"
            download-button-text="Descargar Excel"
            :downloading="isExporting"
            :title-class="[config.forms.classes.title]"
            :select-class="config.forms.classes.select2"
            @search="listEntity({})"
            @add="openModal()"
            @import="openImportModal"
            @download="downloadRecords"/>

        <CatalogReferenceImportModal
            ref="brandImportModal"
            modal-id="brandImportModal"
            resource="marcas"
            template-file-name="plantilla_marcas.xlsx"
            :import-route="routeActions.import"
            @imported="listEntity({})"/>

        <section class="br-entity-list" aria-label="Listado de marcas">
            <div class="table-responsive">
                <table class="table br-entity-table mb-0">
                    <colgroup>
                        <col style="width: 20%;">
                        <col style="width: 26%;">
                        <col style="width: 19%;">
                        <col style="width: 16%;">
                        <col style="width: 11%;">
                        <col style="width: 8%;">
                    </colgroup>
                    <thead class="br-table-header-surface">
                        <tr>
                            <th>Identificación</th>
                            <th>Marca</th>
                            <th>Sitio oficial</th>
                            <th class="text-center">Uso actual</th>
                            <th class="text-center">Estado</th>
                            <th><span class="visually-hidden">Acciones</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="entityList.extras.loading">
                            <td colspan="6" class="py-4"><Loader/></td>
                        </tr>
                        <template v-else-if="entityList.records.total > 0">
                            <tr v-for="record in entityList.records.data" :key="record.id">
                                <td class="br-entity-table__identification-cell">
                                    <div class="br-entity-identifiers br-entity-identifiers--plain">
                                        <div class="br-entity-identifier">
                                            <span class="br-entity-identifier__label colon-at-end">Cód. interno</span>
                                            <span class="br-entity-identifier__value">
                                                <span class="br-entity-code" v-text="record.internal_code"></span>
                                                <span class="br-entity-identifier__actions">
                                                    <CopyButton :value="record.internal_code" label="Código interno"/>
                                                </span>
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="br-entity-table__name" v-text="record.name"></span>
                                    <span
                                        v-if="record.description"
                                        class="br-entity-table__description"
                                        v-text="record.description">
                                    </span>
                                </td>
                                <td>
                                    <a
                                        v-if="record.website_url"
                                        class="br-entity-table__meta"
                                        :href="record.website_url"
                                        target="_blank"
                                        rel="noopener noreferrer">
                                        Sitio oficial
                                    </a>
                                    <span v-else class="br-entity-table__meta">
                                        Sin sitio oficial
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="br-entity-table__meta">
                                        {{ record.active_items_count || 0 }}
                                        {{ Number(record.active_items_count || 0) === 1 ? "producto activo" : "productos activos" }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <StatusBadge
                                        :status="record.status"
                                        :formatted-status="record.formatted_status"/>
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
                            <td colspan="6" class="py-4">
                                <WithoutData class="br-entity-empty-state" type="image"/>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <nav
            v-if="!entityList.extras.loading && entityList.records.total > 0"
            class="d-flex justify-content-center mt-3"
            aria-label="Paginación de marcas">
            <Paginator :links="entityList.records.links" @clickPage="listEntity"/>
        </nav>
    </main>

    <FormModal
        :modal-id="brandForm.extras.modals.default.id"
        :title="modalTitles.createUpdate[isUpdate ? 'update' : 'store']"
        modal-size="modal-lg"
        :submit-button-class="isUpdate ? 'br-btn-action-update' : 'br-btn-action-create'"
        :submit-disabled="isSaving"
        @submit="saveEntity">
        <template #body>
                    <form @submit.prevent="saveEntity">
                        <section class="br-entity-form-section">
                            <div class="row g-3">
                                <InputText
                                    v-model="brandForm.data.internal_code"
                                    hasDiv
                                    title="Código interno"
                                    :titleClass="[config.forms.classes.title]"
                                    isRequired
                                    :maxlength="internalCodeEditableMaxlength"
                                    hasTextBottom
                                    :textBottomInfo="brandForm.errors?.internal_code"
                                    xl="4"
                                    lg="4">
                                    <template v-if="internalCodePrefixLabel" #inputGroupPrepend>
                                        <span class="input-group-text br-internal-code-prefix" v-text="internalCodePrefixLabel"></span>
                                    </template>
                                    <template v-if="!isUpdate" #inputGroupAppend>
                                        <button
                                            type="button"
                                            class="br-input-action"
                                            data-bs-toggle="tooltip"
                                            data-bs-placement="top"
                                            title="Generar un nuevo código interno para la marca"
                                            aria-label="Generar un nuevo código interno para la marca"
                                            @click="generateInternalCode($event)">
                                            <i class="fa-solid fa-rotate" aria-hidden="true"></i>
                                        </button>
                                    </template>
                                </InputText>

                                <InputText
                                    v-model="brandForm.data.name"
                                    hasDiv
                                    title="Nombre"
                                    :titleClass="[config.forms.classes.title]"
                                    isRequired
                                    maxlength="100"
                                    showCharCounter
                                    hasTextBottom
                                    :textBottomInfo="brandForm.errors?.name"
                                    xl="8"
                                    lg="8"/>

                                <InputText
                                    v-model="brandForm.data.description"
                                    hasDiv
                                    title="Descripción"
                                    :titleClass="[config.forms.classes.title]"
                                    maxlength="250"
                                    showCharCounter
                                    hasTextBottom
                                    :textBottomInfo="brandForm.errors?.description"
                                    xl="8"
                                    lg="8"/>

                                <InputSlot
                                    hasDiv
                                    title="Estado"
                                    :titleClass="[config.forms.classes.title]"
                                    isRequired
                                    hasTextBottom
                                    :textBottomInfo="brandForm.errors?.status"
                                    xl="4"
                                    lg="4">
                                    <template #input>
                                        <v-select
                                            v-model="brandForm.data.status"
                                            :options="statuses"
                                            :class="config.forms.classes.select2"
                                            :clearable="false"
                                            :searchable="false"
                                            append-to-body>
                                            <template #selected-option="option">
                                                <span
                                                    class="br-select-selected-text"
                                                    :title="option.label"
                                                    v-text="option.label">
                                                </span>
                                            </template>
                                            <template #option="option">
                                                <span
                                                    class="br-select-option-text"
                                                    :title="option.label"
                                                    v-text="option.label">
                                                </span>
                                            </template>
                                        </v-select>
                                    </template>
                                </InputSlot>

                                <InputText
                                    v-model="brandForm.data.website_url"
                                    hasDiv
                                    title="Sitio oficial"
                                    :titleClass="[config.forms.classes.title]"
                                    maxlength="500"
                                    hasTextBottom
                                    :textBottomInfo="brandForm.errors?.website_url"
                                    xl="12"
                                    lg="12"/>
                            </div>
                        </section>
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
        internal_code: {trim: true},
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
    components: {CatalogReferenceImportModal, CopyButton, FormModal, TableActionsDropdown},
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
            isExporting: false
        };

    },
    async mounted() {

        Utils.navbarItem("menu-parent-items", {addClass: "open"});
        Utils.navbarItem(this.config.entity.page.menu.id, {});
        Alerts.swals({type: "initParams"});

        if(await this.initParams()) {

            Alerts.swals({show: false});
            await this.listEntity({});

        }

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
            }

            return Requests.valid({result: response});

        },
        async listEntity(params = null) {

            const emptyRecords = {total: 0, data: [], links: []};
            const filters = Utils.cloneJson(this.entityList.filters);
            const filterData = {
                per_page: MODULE.perPage,
                filter_by: filters.filter_by?.code,
                word: filters.word
            };

            this.entityList.extras.loading = true;

            try {

                const url = this.isDefined(params) && typeof params === "object" ? params.url : params;
                let requestUrl = url || this.entityList.extras.route;
                let requestData = filterData;

                if(this.isDefined(url)) {

                    const urlObject = new URL(url, window.location.origin);

                    Object.entries(filterData).forEach(([key, value]) => {

                        if(this.isDefined(value) && !urlObject.searchParams.has(key)) {

                            urlObject.searchParams.set(key, value);

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

                this.entityList.records = response?.data ?? emptyRecords;

            }catch(error) {

                this.entityList.records = emptyRecords;

            }finally {

                this.entityList.extras.loading = false;
                this.$nextTick(() => Alerts.tooltips({}));

            }

        },
        async downloadRecords() {

            if(this.isExporting) {

                return;

            }

            const confirmed = await Alerts.confirmDownload({resource: "el listado de marcas"});

            if(!confirmed) return;

            this.isExporting = true;
            Alerts.swals({type: "default", title: "Preparando reporte de marcas"});

            try {

                await Requests.download({
                    route: this.routeActions.export,
                    data: {
                        filter_by: this.entityList.filters.filter_by?.code,
                        word: this.entityList.filters.word
                    },
                    fileName: "marcas.xlsx",
                    showAlert: true
                });

            }finally {

                Alerts.swals({show: false});
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
                    internal_code: this.stripInternalCodePrefix(record.internal_code),
                    name: record.name,
                    description: record.description,
                    website_url: record.website_url,
                    status: this.statuses.find(status => status.code === record.status) ?? null
                });

            }else {

                Object.assign(this.brandForm.data, {
                    internal_code: this.generateCode(),
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

            Alerts.swals({});
            this.brandForm.errors = {};
            this.isSaving = true;

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
                        messages: Forms.getDescriptiveErrors(validation.errors, this.MODULE.errorLabels),
                        msgContent: this.config.messages.errorValidate
                    });
                    return;

                }

                const data = Forms.prepareFormData(formData, MODULE.fieldConfig);
                const id = data.id;
                const isUpdate = this.isDefined(id);
                const method = isUpdate ? "patch" : "post";
                const route = this.routeActions[isUpdate ? "update" : "store"];
                const result = await Requests[method]({route, data, id});

                if(Requests.valid({result})) {

                    Alerts.modals({type: "hide", id: this.brandForm.extras.modals.default.id});
                    Alerts.generateAlert({type: "success", msgContent: result.data.msg});

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
