<template>
    <FgCatalogList
        kind="subscriptions"
        title="Membresías"
        singular="membresía"
        plural="membresías"
        list-title="Membresías registradas"
        description="Administra tus planes, sus precios y sus condiciones de acceso."
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
            <td><dl class="fg-catalog-details">
                <div><dt>Duración:</dt><dd>{{ record.formatted_duration }}</dd></div>
                <div v-if="record.capacity_control_enabled"><dt>Cupos:</dt><dd>{{ capacityLabel(record) }}</dd></div>
                <div v-if="commissionLabel(record)"><dt>Comisión:</dt><dd>{{ commissionLabel(record) }}</dd></div>
            </dl></td>
            <td class="fg-align-end"><CatalogPriceCell :record="record"/></td>
            <td class="fg-align-center"><StatusBadge :status="record.status" :formatted-status="record.formatted_status"/></td>
            <td class="fg-align-center">
                <TableActionsDropdown :label="`Acciones de ${record.name}`">
                    <li><button type="button" class="fg-menu__item" @click="openModal(record)">Editar membresía</button></li>
                </TableActionsDropdown>
            </td>
        </template>
    </FgCatalogList>

    <OfferingImportModal ref="importModal" type="subscription" :import-route="routeActions.import" :internal-code-prefix="internalCodePrefixes.subscription" @imported="listEntity({})"/>

    <FormModal
        :modal-id="forms[entity].createUpdate.extras.modals.default.id"
        :title="MODULE.texts.modal[isUpdate ? 'update' : 'store']"
        modal-class="fg-modal--editor"
        modal-size="modal-lg"
        :close-disabled="isSaving"
        :submit-disabled="isSaving"
        :submit-button-text="isSaving ? 'Guardando…' : isUpdate ? 'Guardar cambios' : 'Guardar membresía'"
        @submit="saveEntity">
        <template #body>
            <div class="fg-editor">
                <AppTabs v-model="activeFormTab" :tabs="formTabs" :has-errors="tabHasErrors" id-prefix="subscription" controls-prefix="subscription" variant="descriptive" layout="content" label="Secciones de la membresía"/>
                <form class="fg-editor__panel" @submit.prevent="saveEntity">
                    <section v-show="activeFormTab === 'general'" id="subscription-tab-general" aria-labelledby="subscription-tab-trigger-general" role="tabpanel" class="fg-stack">
                        <div class="fg-section-heading"><h3 class="fg-section-title">Datos de la membresía</h3><span class="fg-help"><span class="fg-required">*</span> Obligatorios</span></div>
                        <div class="fg-grid">
                            <InternalCodeInput v-model="forms[entity].createUpdate.data.internal_code" v-bind="internalCodeInputProps(forms[entity].createUpdate.data)" title="Código interno" is-required show-char-counter has-text-bottom :text-bottom-info="forms[entity].createUpdate.errors?.internal_code" :disabled="isSaving">
                                <template #action><button type="button" class="fg-link" :disabled="isSaving" :title="MODULE.texts.form.generateCodeTooltip" @click="generateCodeAction($event)">Generar</button></template>
                            </InternalCodeInput>
                            <InputNumber v-model="forms[entity].createUpdate.data.duration_value" :title="MODULE.texts.form.durationValue" is-required :decimals="0" :min-value="1" has-text-bottom :text-bottom-info="forms[entity].createUpdate.errors?.duration_value || forms[entity].createUpdate.errors?.duration_type" :disabled="isSaving">
                                <template #inputGroupAppend>
                                    <FgSelect input-id="subscription-duration-unit" class="fg-select--unit" v-model="forms[entity].createUpdate.data.duration_type" :options="durationTypes" :aria-label="MODULE.texts.form.durationType" :clearable="false" :searchable="false" :disabled="isSaving"/>
                                </template>
                            </InputNumber>
                        </div>
                        <InputText v-model="forms[entity].createUpdate.data.name" :title="MODULE.texts.form.name" :placeholder="placeholders.names.subscription" has-text-bottom :text-bottom-info="forms[entity].createUpdate.errors?.name" :disabled="isSaving" is-required maxlength="100" show-char-counter/>
                        <div class="fg-grid">
                            <InputNumber v-model="forms[entity].createUpdate.data.price" :title="MODULE.texts.form.price" is-required :placeholder="placeholders.price" has-text-bottom :text-bottom-info="forms[entity].createUpdate.errors?.price" :disabled="isSaving">
                                <template #inputGroupPrepend><span class="fg-field__prefix">{{ forms[entity].createUpdate.data.currency?.data?.sign }}</span></template>
                            </InputNumber>
                            <FgField label="Estado" required :error="forms[entity].createUpdate.errors?.status">
                                <FgSelect v-model="forms[entity].createUpdate.data.status" :options="statuses" :disabled="isSaving" :clearable="false" :searchable="false"/>
                            </FgField>
                        </div>
                        <CatalogTaxFields v-model:price-includes-tax="forms[entity].createUpdate.data.price_includes_tax" v-model:igv-exempt="forms[entity].createUpdate.data.igv_exempt" :disabled="isSaving"/>
                        <FgField label="Categorías" :error="forms[entity].createUpdate.errors?.categories">
                            <template #action><AddCategory :internal-code-prefix="internalCodePrefixes.category" :disabled="isSaving" @created="handleCategoryCreated"/></template>
                            <FgSelect v-model="forms[entity].createUpdate.data.categories" :options="categories" :disabled="isSaving" multiple :placeholder="placeholders.categories">
                                <template #no-options><span>No hay categorías disponibles.</span></template>
                            </FgSelect>
                        </FgField>
                        <details class="fg-disclosure" :open="Boolean(forms[entity].createUpdate.errors?.description || forms[entity].createUpdate.errors?.min_price || forms[entity].createUpdate.errors?.max_price || forms[entity].createUpdate.errors?.benefits || forms[entity].createUpdate.errors?.restrictions)">
                            <summary><span>Descripción, condiciones y límites de precio</span><span class="fg-chevron" aria-hidden="true"/></summary>
                            <div class="fg-stack">
                                <InputTextArea v-model="forms[entity].createUpdate.data.description" :title="MODULE.texts.form.description" :placeholder="placeholders.optional" has-text-bottom :text-bottom-info="forms[entity].createUpdate.errors?.description" :disabled="isSaving" maxlength="100"/>
                                <div class="fg-grid">
                                    <InputText v-model="forms[entity].createUpdate.data.benefits_text" :title="MODULE.texts.form.benefits" has-text-bottom :text-bottom-info="forms[entity].createUpdate.errors?.benefits" :disabled="isSaving" maxlength="100" show-char-counter/>
                                    <InputText v-model="forms[entity].createUpdate.data.restrictions_text" :title="MODULE.texts.form.restrictions" has-text-bottom :text-bottom-info="forms[entity].createUpdate.errors?.restrictions" :disabled="isSaving" maxlength="100" show-char-counter/>
                                </div>
                                <div class="fg-grid">
                                    <InputNumber v-model="forms[entity].createUpdate.data.min_price" :title="MODULE.texts.form.minPrice" :placeholder="placeholders.minPrice" has-text-bottom :text-bottom-info="forms[entity].createUpdate.errors?.min_price" :disabled="isSaving">
                                        <template #inputGroupPrepend><span class="fg-field__prefix">{{ forms[entity].createUpdate.data.currency?.data?.sign }}</span></template>
                                    </InputNumber>
                                    <InputNumber v-model="forms[entity].createUpdate.data.max_price" :title="MODULE.texts.form.maxPrice" :placeholder="placeholders.maxPrice" has-text-bottom :text-bottom-info="forms[entity].createUpdate.errors?.max_price" :disabled="isSaving">
                                        <template #inputGroupPrepend><span class="fg-field__prefix">{{ forms[entity].createUpdate.data.currency?.data?.sign }}</span></template>
                                    </InputNumber>
                                </div>
                            </div>
                        </details>
                    </section>
                    <section v-show="activeFormTab === 'configuration'" id="subscription-tab-configuration" aria-labelledby="subscription-tab-trigger-configuration" role="tabpanel" class="fg-stack">
                        <CatalogVisibilityFields id-prefix="subscription" item-label="membresía" v-model:published="forms[entity].createUpdate.data.see_my_web" v-model:show-price="forms[entity].createUpdate.data.see_my_web_price" :disabled="isSaving"/>
                        <div class="fg-section-divider"/>
                        <p>Vigencia y comisión</p>
                        <InputDate v-model="forms[entity].createUpdate.data.expires_at" :title="MODULE.texts.form.expiresAt" has-text-bottom :text-bottom-info="forms[entity].createUpdate.errors?.expires_at" :disabled="isSaving"/>
                        <div class="fg-grid">
                            <FgField :label="MODULE.texts.form.commissionType" :error="forms[entity].createUpdate.errors?.commission_type" help="La comisión es interna y no cambia el precio que paga el cliente.">
                                <FgSelect v-model="forms[entity].createUpdate.data.commission_type" :options="commissionTypeOptions" :disabled="isSaving" :clearable="false" :searchable="false" @update:modelValue="resetCatalogCommission(forms[entity].createUpdate)"/>
                            </FgField>
                            <CommissionValueInput v-model="forms[entity].createUpdate.data.commission_value" :commission-type="forms[entity].createUpdate.data.commission_type?.code" :currency-sign="forms[entity].createUpdate.data.currency?.data?.sign" :title="MODULE.texts.form.commissionValue" has-text-bottom :text-bottom-info="forms[entity].createUpdate.errors?.commission_value" :disabled="isSaving"/>
                        </div>
                        <div class="fg-section-divider"/>
                        <p>Cupos y asistencia</p>
                        <label class="fg-check"><input v-model="forms[entity].createUpdate.data.capacity_control_enabled" type="checkbox" :disabled="isSaving" @change="delete forms[entity].createUpdate.errors.capacity_limit"/><span>Limitar cupos</span></label>
                        <div class="fg-grid">
                            <InputNumber v-model="forms[entity].createUpdate.data.capacity_limit" :title="MODULE.texts.form.capacityLimit" :is-required="forms[entity].createUpdate.data.capacity_control_enabled" :disabled="isSaving || !forms[entity].createUpdate.data.capacity_control_enabled" :decimals="0" :min-value="1" :max-value="validationRules.capacity_limit.max" has-text-bottom :text-bottom-info="forms[entity].createUpdate.errors?.capacity_limit"/>
                            <InputNumber v-model="forms[entity].createUpdate.data.attendance_limit_per_day" :title="MODULE.texts.form.attendanceLimit" has-text-bottom :text-bottom-info="forms[entity].createUpdate.errors?.attendance_limit_per_day" :disabled="isSaving" :decimals="0" :min-value="1"/>
                        </div>
                    </section>
                </form>
            </div>
        </template>
    </FormModal>
</template>

<script>
import FgSelect from "@System/Components/Figma/FgSelect.vue";
import FgField from "@System/Components/Figma/FgField.vue";
import FgCatalogList from "@System/Components/Catalogs/FgCatalogList.vue";
import {downloadCatalogList} from "@System/Components/Catalogs/downloadCatalogList.js";
import CatalogVisibilityFields from "@System/Components/Catalogs/CatalogVisibilityFields.vue";
import CatalogTaxFields from "@System/Components/Catalogs/CatalogTaxFields.vue";
import {catalogFormPlaceholders} from "@System/Components/Catalogs/catalogFormPlaceholders.js";
import CatalogPriceCell from "@System/Components/Catalogs/CatalogPriceCell.vue";
import {navbarItem} from "@System/Layout/Menu.js";
import ExpandableText from "@System/Components/Generics/ExpandableText.vue";
import * as Alerts from "@System/Helpers/Alerts.js";
import { initCrudModule } from "@System/Helpers/ModuleFactory.js";
import * as Forms from "@System/Helpers/Forms.js";
import * as Requests from "@System/Helpers/Requests.js";
import * as Utils from "@System/Helpers/Utils.js";
import InternalCodePrefixMixin from "@System/Mixins/InternalCodePrefixMixin.js";
import InternalCodeInput from "@System/Components/Catalogs/InternalCodeInput.vue";
import InternalCodeValue from "@System/Components/Catalogs/InternalCodeValue.vue";
import CommissionValueInput from "@System/Components/Catalogs/CommissionValueInput.vue";
import {OFFERING_CAPACITY_MAX, resetCatalogCommission, validateCatalogCapacity, validateCatalogCommission} from "@System/Components/Catalogs/catalogFormValidation.js";
import FormModal from "@System/Components/Generics/FormModal.vue";
import AppTabs from "@/Shared/AppTabs.vue";
import TableActionsDropdown from "@System/Components/Generics/TableActionsDropdown.vue";
import AddCategory from "@System/Components/Catalogs/AddCategory.vue";
import OfferingImportModal from "@System/Components/Catalogs/Offerings/OfferingImportModal.vue";
import {capacityLabel} from "@System/Components/Catalogs/Offerings/capacityLabel.js";
import {commissionLabel} from "@System/Components/Catalogs/Offerings/commissionLabel.js";
import {upsertCatalogOption} from "@System/Helpers/CatalogReferenceOptions.js";

const FORM_TABS = [
    {id: "general", label: "Membresía", description: "Datos y precios", icon: "fa-solid fa-id-card", fields: ["internal_code", "name", "duration_value", "duration_type", "price", "min_price", "max_price", "status", "igv_exempt", "price_includes_tax", "description", "categories", "benefits", "restrictions"]},
    {id: "configuration", label: "Configuración", description: "Visibilidad y vigencia", icon: "fa-solid fa-sliders", fields: ["expires_at", "commission_type", "commission_value", "attendance_limit_per_day", "capacity_control_enabled", "capacity_limit", "see_my_web", "see_my_web_price"]}
];

const MODULE_CONFIG = {
    entity: "subscriptions",
    menuId: "menu-items-subscriptions",
    pageTitle: "Membresías",
    pageTitleSingular: "Membresía",
    internalCodeEntity: "subscription",
    breadcrumbParent: "Catálogo e inventario",
    perPage: 10
};

const FORM_FIELDS = {
    internal_code: "",
    name: "",
    description: "",
    duration_value: "",
    duration_type: null,
    price: "",
    price_includes_tax: true,
    igv_exempt: false,
    min_price: "",
    max_price: "",
    attendance_limit_per_day: "",
    capacity_control_enabled: false,
    capacity_limit: "",
    commission_type: null,
    commission_value: "",
    benefits_text: "",
    restrictions_text: "",
    expires_at: "",
    currency: null,
    categories: [],
    see_my_web: false,
    see_my_web_price: false,
    status: null
};

const FORM_FIELD_CONFIG = {
    internal_code: {trim: false},
    name: {trim: true},
    description: {normalize: true},
    duration_value: {toNumber: true, minValue: 1},
    duration_type: {getCode: true},
    price: {toNumber: true, minValue: 0},
    price_includes_tax: {toBoolean: true},
    igv_exempt: {toBoolean: true},
    min_price: {toNumber: true, minValue: 0},
    max_price: {toNumber: true, minValue: 0},
    attendance_limit_per_day: {normalize: true, toNumber: true, minValue: 1},
    capacity_control_enabled: {toBoolean: true},
    capacity_limit: {toNumber: true, minValue: 1},
    commission_type: {getCode: true},
    commission_value: {toNumber: true, minValue: 0},
    expires_at: {normalize: true},
    currency: {mapToField: "currency_id"},
    categories: {getArray: {mapTo: "category_id"}},
    see_my_web: {toBoolean: true},
    see_my_web_price: {toBoolean: true},
    status: {getCode: true}
};

const VALIDATION_RULES = {
    internal_code: {required: true},
    name: {required: true},
    description: {required: false},
    duration_value: {required: true, number: true, min: 1},
    duration_type: {required: true},
    price: {required: true, number: true, min: 0},
    price_includes_tax: {required: false},
    igv_exempt: {required: false},
    min_price: {required: false, number: true, min: 0},
    max_price: {required: false, number: true, min: 0},
    attendance_limit_per_day: {required: false, number: true, min: 1},
    capacity_control_enabled: {required: false},
    capacity_limit: {required: false, number: true, min: 1, max: OFFERING_CAPACITY_MAX},
    commission_type: {required: false},
    commission_value: {required: false, number: true, min: 0},
    expires_at: {required: false},
    currency: {required: true},
    categories: {required: false},
    see_my_web: {required: false},
    see_my_web_price: {required: false},
    status: {required: true}
};

const ERROR_LABELS = {
    internal_code: "Código interno",
    name: "Nombre",
    description: "Descripción",
    duration_value: "Duración (valor)",
    duration_type: "Duración (tipo)",
    price: "Precio de venta",
    price_includes_tax: "Precio incluye IGV",
    igv_exempt: "IGV exonerado",
    min_price: "Precio mínimo",
    max_price: "Precio máximo",
    attendance_limit_per_day: "Límite diario",
    capacity_control_enabled: "Control de cupos",
    capacity_limit: "Cupos disponibles",
    commission_type: "Tipo de comisión",
    commission_value: "Valor de comisión",
    benefits: "Beneficios",
    restrictions: "Restricciones",
    expires_at: "Disponible hasta",
    currency: "Moneda",
    categories: "Categorías",
    see_my_web: "Visualizar en mi página",
    see_my_web_price: "Visualizar precio",
    status: "Estado"
};

const FILTER_OPTIONS = [
    {code: "all", label: "Todos los filtros"},
    {code: "internal_code", label: "Código interno"},
    {code: "name", label: "Nombre"},
    {code: "description", label: "Descripción"},
    {code: "price", label: "Precio de venta"}
];

const TEXTS = {
    filters: {
        filterBy: "Filtrar por",
        search: "Búsqueda"
    },
    actions: {
        search: "Buscar",
        add: "Agregar membresía",
        edit: "Editar membresía",
        import: "Importar membresías",
        download: "Descargar listado en Excel"
    },
    form: {
        internalCode: "Código interno",
        name: "Nombre",
        description: "Descripción comercial",
        durationValue: "Duración",
        durationType: "Unidad de duración",
        price: "Precio de venta",
        minPrice: "Precio mínimo",
        maxPrice: "Precio máximo",
        attendanceLimit: "Límite diario de asistencias",
        capacityControl: "Control de cupos",
        capacityLimit: "Cantidad de cupos",
        commissionType: "Tipo de comisión",
        commissionValue: "Valor de comisión",
        benefits: "Beneficios (separados por coma)",
        restrictions: "Restricciones (separadas por coma)",
        expiresAt: "Disponible hasta",
        currency: "Moneda",
        categories: "Categorías",
        status: "Estado",
        visibility: "Visibilidad web",
        seeMyWeb: "Visualizar en mi página",
        seeMyWebPrice: "Visualizar precio",
        generateCodeTooltip: "Generar aleatoriamente"
    },
    modal: {
        store: "Agregar membresía",
        update: "Editar membresía"
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
    name: "SubscriptionsMain",
    mixins: [InternalCodePrefixMixin],
    components: {
        FgSelect,
        FgField,
        FgCatalogList,
        CatalogPriceCell,
        CatalogVisibilityFields,
        CatalogTaxFields,
        InternalCodeInput,
        CommissionValueInput,
        ExpandableText,
        FormModal,
        AppTabs,
        TableActionsDropdown,
        AddCategory,
        OfferingImportModal,
        InternalCodeValue
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
            isExporting: false,
            listFailed: false,
            initialLoading: true,
            activeFormTab: FORM_TABS[0].id,
            placeholders: catalogFormPlaceholders
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
        resetCatalogCommission,
        async initParams() {

            const response = await Requests.get({
                route: this.routeActions.initParams,
                data: {page: "main"},
                showAlert: true
            });

            if(response?.data?.config) {

                this.options.categories    = response.data.config.categories;
                this.options.currencies    = response.data.config.currencies;
                this.options.durationTypes = response.data.config.durationTypes;
                this.options.statuses      = response.data.config.statuses;
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
        getListFilters() {

            const filters = this.entityList.appliedFilters;

            return {filter_by: filters.filter_by?.code, word: filters.word};

        },
        async downloadRecords() {
            if(this.isExporting) return;
            this.isExporting = true;
            try {
                await downloadCatalogList({
                    resource: "membresías",
                    route: this.routeActions.export,
                    filters: this.getListFilters(),
                    fileName: "membresias.xlsx"
                });
            } finally {
                this.isExporting = false;
            }
        },
        openImportModal() {

            Alerts.tooltips({show: false});
            this.$refs.importModal?.open();

        },
        // Forms
        openModal(record = null) {

            const entityForms = this.forms[this.entity].createUpdate;

            entityForms.errors = {};
            Forms.clearFormData(entityForms.data, this.MODULE.formFields);
            this.activeFormTab = FORM_TABS[0].id;

            if(this.isDefined(record)) {

                // Map record data to form
                const currencyOption     = this.currencies.find(e => e.code === record?.currency_id),
                      categoryItems      = (record?.category_items ?? []).map(e => e?.category_id),
                      durationTypeOption = this.durationTypes.find(e => e.code === record?.duration_type),
                      statusOption       = this.statuses.find(e => e.code === record?.status);

                entityForms.data.id               = record.id;
                entityForms.data.internal_code    = this.setEditableInternalCode(record.internal_code);
                entityForms.data.name             = record.name;
                entityForms.data.description      = record.description;
                entityForms.data.duration_value   = record.duration_value;
                entityForms.data.duration_type    = durationTypeOption;
                entityForms.data.price            = record.price;
                entityForms.data.price_includes_tax = Boolean(record.price_includes_tax ?? true) && !Boolean(record.igv_exempt ?? false);
                entityForms.data.igv_exempt       = Boolean(record.igv_exempt ?? false);
                entityForms.data.min_price        = record.min_price;
                entityForms.data.max_price        = record.max_price;
                entityForms.data.attendance_limit_per_day = record.attendance_limit_per_day;
                entityForms.data.capacity_control_enabled = Boolean(record.capacity_control_enabled ?? false);
                entityForms.data.capacity_limit = record.capacity_limit ?? "";
                entityForms.data.commission_type  = this.commissionTypeOptions.find(e => e.code === (record.commission_type || "none")) ?? this.commissionTypeOptions[0];
                entityForms.data.commission_value = entityForms.data.commission_type.code === "none" ? "" : record.commission_value;
                entityForms.data.benefits_text    = this.arrayToText(record.benefits);
                entityForms.data.restrictions_text = this.arrayToText(record.restrictions);
                entityForms.data.expires_at       = record.expires_at ? String(record.expires_at).slice(0, 10) : "";
                entityForms.data.currency         = currencyOption;
                entityForms.data.categories       = this.categories.filter(e => categoryItems.includes(e.code));
                entityForms.data.see_my_web       = Boolean(record.see_my_web ?? false);
                entityForms.data.see_my_web_price = Boolean(record.see_my_web_price ?? false);
                entityForms.data.status           = statusOption;

            }else {

                // Set defaults for new record
                entityForms.data.internal_code = this.setEditableInternalCode(this.generateCode({length: 7}), true);
                entityForms.data.duration_value = 1;
                entityForms.data.duration_type  = this.durationTypes.length > 0 ? this.durationTypes[0] : null;
                entityForms.data.currency       = this.currencies.length > 0 ? this.currencies[0] : null;
                entityForms.data.price_includes_tax = true;
                entityForms.data.igv_exempt = false;
                entityForms.data.capacity_control_enabled = false;
                entityForms.data.capacity_limit = "";
                entityForms.data.commission_type = this.commissionTypeOptions[0];
                entityForms.data.commission_value = "";
                entityForms.data.expires_at = "";
                entityForms.data.status         = this.statuses.length > 0 ? this.statuses[0] : null;

            }

            Alerts.modals({type: "show", id: entityForms.extras.modals.default.id});
            this.$nextTick(() => Alerts.tooltips({}));

        },
        generateCodeAction(event) {

            this.forms[this.entity].createUpdate.data.internal_code = this.generateCode({length: 7});

            Alerts.toastrs({type: "success", subtitle: "Código interno generado correctamente."});
            Alerts.dismissTooltip(event?.currentTarget);

        },
        handleCategoryCreated({record}) {

            upsertCatalogOption(this.options, "categories", record);

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
                    this.focusFirstErrorTab(validation.errors);

                    Alerts.generateAlert({
                        type: "error",
                        headerTitle: "Revisa los campos indicados",
                        messages: Forms.getDescriptiveErrors(validation.errors, this.MODULE.errorLabels)
                    });
                    return;

                }

                const preparedData  = Forms.prepareFormData(formData, this.MODULE.formFieldConfig);
                Object.assign(preparedData, this.internalCodePayload(preparedData.internal_code));
                if(preparedData.igv_exempt) preparedData.price_includes_tax = false;
                if(!preparedData.capacity_control_enabled) preparedData.capacity_limit = null;
                if(!preparedData.see_my_web) preparedData.see_my_web_price = false;
                preparedData.benefits = this.textToArray(formData.benefits_text);
                preparedData.restrictions = this.textToArray(formData.restrictions_text);
                delete preparedData.benefits_text;
                delete preparedData.restrictions_text;
                const id            = preparedData.id;
                const isUpdate      = this.isDefined(id);
                const requestMethod = isUpdate ? "patch" : "post";
                const route         = this.routeActions[isUpdate ? "update" : "store"];
                this.isSaving = true;
                Alerts.loading({title: isUpdate ? "Guardando cambios" : "Guardando membresía"});
                const result        = await Requests[requestMethod]({route, data: preparedData, id}).finally(Alerts.close);

                if(Requests.valid({result})) {

                    Alerts.showMessage({
                        type: "success",
                        title: isUpdate ? "Cambios guardados" : "Membresía guardada",
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

                    this.focusFirstErrorTab(entityForms.errors);

                }

            }catch(error) {

                Alerts.generateAlert({type: "error", messages: [error], msgContent: this.config.messages.catchError});

            }finally {

                this.isSaving = false;

            }

        },
        validateFormData(formData) {

            const result = Forms.validateFormData(formData, this.validationRules, {isDescriptive: true, errorLabels: this.MODULE.errorLabels});

            // Custom validation for price ranges (only if price is valid)
            if(!result.errors.price) {

                const minPrice = Utils.normalizeNumber(formData.min_price);
                const maxPrice = Utils.normalizeNumber(formData.max_price);
                const price    = Utils.normalizeNumber(formData.price);

                if(minPrice > 0 && maxPrice > 0) {

                    if(maxPrice < minPrice) {

                        if(!result.errors.max_price) result.errors.max_price = [];

                        result.errors.max_price.push(`${this.MODULE.errorLabels.max_price}: Debe ser mayor o igual al precio mínimo`);
                        result.bool = false;

                    }else if(price < minPrice || price > maxPrice) {

                        if(!result.errors.price) result.errors.price = [];

                        result.errors.price.push(`${this.MODULE.errorLabels.price}: Debe estar entre ${minPrice} y ${maxPrice}`);
                        result.bool = false;

                    }

                }else if(minPrice > 0 && price < minPrice) {

                    if(!result.errors.price) result.errors.price = [];

                    result.errors.price.push(`${this.MODULE.errorLabels.price}: Debe ser mayor o igual al precio mínimo`);
                    result.bool = false;

                }else if(maxPrice > 0 && price > maxPrice) {

                    if(!result.errors.price) result.errors.price = [];

                    result.errors.price.push(`${this.MODULE.errorLabels.price}: Debe ser menor o igual al precio máximo`);
                    result.bool = false;

                }

            }

            validateCatalogCommission(result, formData);
            validateCatalogCapacity(result, formData);

            return result;

        },
        // Others
        isDefined(value) {

            return Utils.isDefined({value});

        },
        generateCode({length}) {

            return Utils.generateCode({length});

        },
        textToArray(value) {

            return String(value ?? "")
                .split(",")
                .map(item => item.trim())
                .filter(Boolean);

        },
        arrayToText(value) {

            return Array.isArray(value) ? value.filter(Boolean).join(", ") : "";

        },
        commissionLabel,
        capacityLabel,
        focusFirstErrorTab(errors = {}) {

            const fields = Object.keys(errors);

            const tab = FORM_TABS.find(item => item.fields.some(field =>
                fields.some(errorField => errorField === field || errorField.startsWith(`${field}.`))
            ));

            if(tab) this.activeFormTab = tab.id;

        },
        tabHasErrors(tabId) {

            const tab = FORM_TABS.find(item => item.id === tabId);
            const errors = this.forms[this.entity].createUpdate.errors ?? {};

            return tab?.fields.some(field =>
                Object.keys(errors).some(errorField => errorField === field || errorField.startsWith(`${field}.`))
            ) ?? false;

        },
    },
    computed: {
        formTabs() {

            return FORM_TABS;

        },
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
        categories() {

            return (this.options?.categories?.records ?? []).map(e => ({code: e.id, label: e.name, data: e}));

        },
        currencies() {

            return (this.options?.currencies?.records ?? []).map(e => ({code: e.id, label: e.plural_name, data: e}));

        },
        durationTypes() {

            return (this.options?.durationTypes ?? []).map(e => ({code: e.code, label: e.label, plural: e.plural}));

        },
        statuses() {

            return (this.options?.statuses ?? []).map(e => ({code: e.code, label: e.label}));

        },
        commissionTypeOptions() {

            return [
                {code: "none", label: "Sin comisión"},
                {code: "percentage", label: "Porcentaje"},
                {code: "fixed", label: "Monto fijo por unidad"}
            ];

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
