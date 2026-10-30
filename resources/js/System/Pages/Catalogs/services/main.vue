<template>
    <Breadcrumb :list="breadcrumbTitles"/>

    <CatalogListSkeleton v-if="initialLoading && skeletonEnabled" kind="services" label="servicios"/>

    <main v-else class="br-entity">
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
            :import-button-text="MODULE.texts.actions.import"
            :import-button-tooltip="MODULE.texts.actions.import"
            :download-button-text="MODULE.texts.actions.download"
            :download-button-tooltip="MODULE.texts.actions.download"
            :download-icon-only-on-desktop="true"
            :downloading="isExporting"
            :title-class="[config.forms.classes.title]"
            :select-class="config.forms.classes.select2"
            @search="handleSearch"
            @add="openModal()"
            @import="openImportModal"
            @download="downloadRecords"/>

        <OfferingImportModal ref="importModal" type="service" :import-route="routeActions.import" @imported="listEntity({})"/>

        <section class="br-entity-list" aria-label="Listado de servicios">
            <div class="table-responsive">
        <table class="table br-entity-table br-entity-table--offering mb-0">
            <colgroup>
                <col class="br-entity-table__col-code">
                <col class="br-entity-table__col-offering">
                <col class="br-entity-table__col-detail">
                <col class="br-entity-table__col-offering-price">
                <col class="br-entity-table__col-status">
                <col class="br-entity-table__col-actions">
            </colgroup>
            <thead class="br-table-header-surface">
                <tr>
                    <th>Identificación</th>
                    <th>Servicio</th>
                    <th>Detalles</th>
                    <th class="text-end">Precio</th>
                    <th class="text-center">Estado</th>
                    <th><span class="visually-hidden">Acciones</span></th>
                </tr>
            </thead>
            <tbody>
                <tr v-if="entityList.extras.loading">
                    <td colspan="6" class="py-4">
                        <Loader/>
                    </td>
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
                            <span v-text="record.name" class="br-entity-table__name"></span>
                            <span v-if="record.description" v-text="record.description" class="br-entity-table__description"></span>
                        </td>
                        <td>
                            <div class="br-entity-offering-details">
                                <span><strong class="colon-at-end">Duración</strong> {{ serviceDurationLabel(record) }}</span>
                                <span><strong class="colon-at-end">Cupos</strong> {{ capacityLabel(record) }}</span>
                                <span><strong class="colon-at-end">Comisión</strong> {{ commissionLabel(record) }}</span>
                            </div>
                        </td>
                        <td class="text-end">
                            <div class="br-entity-prices">
                                <span class="br-entity-price-row is-sale"><span>Venta</span><strong>{{ record.currency?.sign }} {{ separatorNumber(record.price) }}</strong></span>
                                <span v-if="isDefined(record.min_price)" class="br-entity-price-row is-minimum"><span>Mín.</span><strong>{{ record.currency?.sign }} {{ separatorNumber(record.min_price) }}</strong></span>
                                <span v-if="isDefined(record.max_price)" class="br-entity-price-row is-maximum"><span>Máx.</span><strong>{{ record.currency?.sign }} {{ separatorNumber(record.max_price) }}</strong></span>
                            </div>
                        </td>
                        <td class="text-center">
                            <StatusBadge class="flex-shrink-none" :status="record.status" :formatted-status="record.formatted_status"/>
                        </td>
                        <td class="text-center">
                            <TableActionsDropdown :label="`Acciones de ${record.name}`">
                                <li><button type="button" class="dropdown-item" @click="openModal(record)"><i class="fa-solid fa-pen" aria-hidden="true"></i><span v-text="MODULE.texts.actions.edit"></span></button></li>
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

    <!-- Pagination -->
        <nav v-if="!entityList.extras.loading && entityList.records.total > 0" class="d-flex justify-content-center mt-3" aria-label="Paginación de servicios">
            <Paginator :links="entityList.records.links" @clickPage="listEntity"/>
        </nav>
    </main>

    <FormModal
        :modal-id="forms[entity].createUpdate.extras.modals.default.id"
        :title="MODULE.texts.modal[isUpdate ? 'update' : 'store']"
        modal-class="br-entity-modal--tabbed"
        modal-size="modal-lg"
        :show-submit="false">
        <template #body>
            <CatalogFormTabs
                v-model="activeFormTab"
                :tabs="formTabs"
                :has-errors="tabHasErrors"
                id-prefix="service"
                shared-controls-id="service-form-panel"
                variant="descriptive"
                label="Secciones del servicio"/>
            <form class="br-entity-form-section" @submit.prevent="saveEntity">
                        <div id="service-form-panel" class="row g-3" role="tabpanel" :aria-labelledby="`service-tab-trigger-${activeFormTab}`">
                            <InputText
                                v-show="activeFormTab === 'general'"
                                v-model="forms[entity].createUpdate.data.internal_code"
                                hasDiv
                                :title="MODULE.texts.form.internalCode"
                                :titleClass="[config.forms.classes.title]"
                                isRequired
                                :maxlength="internalCodeEditableMaxlength"
                                :showCharCounter="false"
                                hasTextBottom
                                :textBottomInfo="forms[entity].createUpdate.errors?.internal_code"
                                xl="3"
                                lg="3">
                                <template v-if="internalCodePrefixLabel" v-slot:inputGroupPrepend>
                                    <span class="input-group-text br-internal-code-prefix" v-text="internalCodePrefixLabel"></span>
                                </template>
                                <template v-slot:inputGroupAppend>
                                    <button type="button" class="br-input-action" @click="generateCodeAction($event)" data-bs-toggle="tooltip" data-bs-placement="top" :title="MODULE.texts.form.generateCodeTooltip" :aria-label="MODULE.texts.form.generateCodeTooltip">
                                        <i class="fa fa-rotate"></i>
                                    </button>
                                </template>
                            </InputText>
                            <InputText
                                v-show="activeFormTab === 'general'"
                                v-model="forms[entity].createUpdate.data.name"
                                hasDiv
                                :title="MODULE.texts.form.name"
                                :titleClass="[config.forms.classes.title]"
                                isRequired
                                maxlength="100"
                                showCharCounter
                                hasTextBottom
                                :textBottomInfo="forms[entity].createUpdate.errors?.name"
                                xl="6"
                                lg="6"/>
                            <InputNumber
                                v-show="activeFormTab === 'general'"
                                v-model="forms[entity].createUpdate.data.estimated_duration_minutes"
                                hasDiv
                                :title="MODULE.texts.form.estimatedDuration"
                                :titleClass="[config.forms.classes.title]"
                                :decimals="0"
                                :minValue="1"
                                hasTextBottom
                                :textBottomInfo="forms[entity].createUpdate.errors?.estimated_duration_minutes"
                                xl="3"
                                lg="3">
                                <template #inputGroupAppend>
                                    <span class="input-group-text br-input-unit-suffix">min</span>
                                </template>
                            </InputNumber>
                            <InputText
                                v-show="activeFormTab === 'attributes'"
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
                            <InputDate
                                v-show="activeFormTab === 'configuration'"
                                v-model="forms[entity].createUpdate.data.expires_at"
                                hasDiv
                                :title="MODULE.texts.form.expiresAt"
                                :titleClass="[config.forms.classes.title]"
                                hasTextBottom
                                :textBottomInfo="forms[entity].createUpdate.errors?.expires_at"
                                xl="4"
                                lg="4"/>
                            <InputSlot
                                v-show="activeFormTab === 'configuration'"
                                hasDiv
                                :title="MODULE.texts.form.commissionType"
                                :titleClass="[config.forms.classes.title]"
                                hasTextBottom
                                :textBottomInfo="forms[entity].createUpdate.errors?.commission_type"
                                xl="4"
                                lg="4">
                                <template v-slot:input>
                                    <v-select
                                        v-model="forms[entity].createUpdate.data.commission_type"
                                        :options="commissionTypeOptions"
                                        :class="config.forms.classes.select2"
                                        :clearable="false"
                                        :searchable="false"
                                        @update:modelValue="forms[entity].createUpdate.data.commission_value = ''"/>
                                </template>
                            </InputSlot>
                            <InputNumber
                                v-show="activeFormTab === 'configuration'"
                                v-model="forms[entity].createUpdate.data.commission_value"
                                hasDiv
                                :title="MODULE.texts.form.commissionValue"
                                :titleClass="[config.forms.classes.title]"
                                :disabled="forms[entity].createUpdate.data.commission_type?.code === 'none'"
                                :minValue="0"
                                :maxValue="forms[entity].createUpdate.data.commission_type?.code === 'percentage' ? 100 : undefined"
                                hasTextBottom
                                :textBottomInfo="forms[entity].createUpdate.errors?.commission_value"
                                xl="4"
                                lg="4"/>
                            <div v-show="activeFormTab === 'configuration'" class="form-group col-xl-4 col-lg-4 col-md-12 col-sm-12">
                                <label class="form-label fw-bold fs-6" v-text="MODULE.texts.form.capacityControl"></label>
                                <label class="br-entity-switch br-entity-switch--field" for="service_capacity_control_enabled">
                                    <input
                                        id="service_capacity_control_enabled"
                                        v-model="forms[entity].createUpdate.data.capacity_control_enabled"
                                        class="form-check-input"
                                        type="checkbox"
                                        role="switch">
                                    <span>Activar <small>· Límite de cupos</small></span>
                                </label>
                            </div>
                            <InputNumber
                                v-show="activeFormTab === 'configuration'"
                                v-model="forms[entity].createUpdate.data.capacity_limit"
                                hasDiv
                                :title="MODULE.texts.form.capacityLimit"
                                :titleClass="[config.forms.classes.title]"
                                :disabled="!forms[entity].createUpdate.data.capacity_control_enabled"
                                :decimals="0"
                                :minValue="1"
                                hasTextBottom
                                :textBottomInfo="forms[entity].createUpdate.errors?.capacity_limit"
                                xl="4"
                                lg="4"/>
                            <InputNumber
                                v-show="activeFormTab === 'general'"
                                v-model="forms[entity].createUpdate.data.price"
                                hasDiv
                                :title="MODULE.texts.form.price"
                                :titleClass="[config.forms.classes.title]"
                                isRequired
                                hasTextBottom
                                :textBottomInfo="forms[entity].createUpdate.errors?.price"
                                xl="3"
                                lg="3">
                                <template v-slot:inputGroupPrepend>
                                    <span class="input-group-text br-currency-prefix">
                                        <span class="br-currency-prefix__symbol" v-text="forms[entity].createUpdate.data.currency?.data?.sign"></span>
                                    </span>
                                </template>
                            </InputNumber>
                            <InputNumber
                                v-show="activeFormTab === 'general'"
                                v-model="forms[entity].createUpdate.data.min_price"
                                hasDiv
                                :title="MODULE.texts.form.minPrice"
                                :titleClass="[config.forms.classes.title]"
                                hasTextBottom
                                :textBottomInfo="forms[entity].createUpdate.errors?.min_price"
                                xl="3"
                                lg="3"
                                md="6"
                                sm="6">
                                <template v-slot:inputGroupPrepend>
                                    <span class="input-group-text br-currency-prefix">
                                        <span class="br-currency-prefix__symbol" v-text="forms[entity].createUpdate.data.currency?.data?.sign"></span>
                                    </span>
                                </template>
                            </InputNumber>
                            <InputNumber
                                v-show="activeFormTab === 'general'"
                                v-model="forms[entity].createUpdate.data.max_price"
                                hasDiv
                                :title="MODULE.texts.form.maxPrice"
                                :titleClass="[config.forms.classes.title]"
                                hasTextBottom
                                :textBottomInfo="forms[entity].createUpdate.errors?.max_price"
                                xl="3"
                                lg="3"
                                md="6"
                                sm="6">
                                <template v-slot:inputGroupPrepend>
                                    <span class="input-group-text br-currency-prefix">
                                        <span class="br-currency-prefix__symbol" v-text="forms[entity].createUpdate.data.currency?.data?.sign"></span>
                                    </span>
                                </template>
                            </InputNumber>
                            <div v-show="activeFormTab === 'taxes'" class="col-12">
                                <div class="br-entity-publication-settings">
                                    <label class="br-entity-switch" for="service_igv_exempt">
                                        <input
                                            id="service_igv_exempt"
                                            v-model="forms[entity].createUpdate.data.igv_exempt"
                                            class="form-check-input"
                                            type="checkbox"
                                            role="switch"
                                            @change="syncTaxExemption(forms[entity].createUpdate.data)">
                                        <span>
                                            <strong>IGV exonerado</strong>
                                            <small>Si está activo, el IGV no se calcula para este servicio al vender.</small>
                                        </span>
                                    </label>
                                    <label class="br-entity-switch" :class="{'is-disabled': forms[entity].createUpdate.data.igv_exempt}" for="service_price_includes_tax">
                                        <input id="service_price_includes_tax"
                                            v-model="forms[entity].createUpdate.data.price_includes_tax"
                                            class="form-check-input" type="checkbox" role="switch"
                                            :disabled="forms[entity].createUpdate.data.igv_exempt">
                                        <span><strong>Precio incluye IGV</strong><small>Si está activo, el precio de venta ya contiene el impuesto y no incrementará el total al vender.</small></span>
                                    </label>
                                </div>
                            </div>
                            <InputSlot
                                v-show="activeFormTab === 'attributes'"
                                hasDiv
                                :title="MODULE.texts.form.categories"
                                :titleClass="[config.forms.classes.title]"
                                hasTextBottom
                                :textBottomInfo="forms[entity].createUpdate.errors?.categories"
                                xl="4"
                                lg="4">
                                <template #defaultAppend>
                                    <AddCategory
                                        trigger-mode="link"
                                        trigger-text="Agregar"
                                        trigger-icon=""
                                        :trigger-tooltip="false"
                                        :internal-code-prefix="internalCodePrefixes.category"
                                        :disabled="isSaving"
                                        @created="handleCategoryCreated"/>
                                </template>
                                <template v-slot:input>
                                    <v-select
                                        v-model="forms[entity].createUpdate.data.categories"
                                        :options="categories"
                                        :class="config.forms.classes.select2"
                                        :clearable="true"
                                        :searchable="true"
                                        :multiple="true"
                                        append-to-body>
                                        <template #selected-option="option"><span class="br-select-selected-text" v-text="option.label"></span></template>
                                        <template #option="option"><span class="br-select-option-text" v-text="option.label"></span></template>
                                        <template #no-options><SelectNoOptions/></template>
                                    </v-select>
                                </template>
                            </InputSlot>
                            <InputSlot
                                v-show="activeFormTab === 'general'"
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
                            <div v-show="activeFormTab === 'attributes'" class="col-12">
                                <div class="mb-2"><span class="form-label colon-at-end">Visibilidad para clientes</span></div>
                                <div class="br-entity-publication-settings">
                                    <label class="br-entity-switch" for="service-publish">
                                        <input id="service-publish" v-model="forms[entity].createUpdate.data.see_my_web"
                                            class="form-check-input" type="checkbox" role="switch"
                                            @change="syncPublicationSettings(forms[entity].createUpdate.data)">
                                        <span><strong>Publicar servicio</strong><small>Permite que los clientes vean este servicio en el catálogo.</small></span>
                                    </label>
                                    <label class="br-entity-switch" :class="{'is-disabled': !forms[entity].createUpdate.data.see_my_web}" for="service-show-price">
                                        <input id="service-show-price" v-model="forms[entity].createUpdate.data.see_my_web_price"
                                            class="form-check-input" type="checkbox" role="switch"
                                            :disabled="!forms[entity].createUpdate.data.see_my_web">
                                        <span><strong>Mostrar precio</strong><small>Permite que los clientes vean el precio en el catálogo.</small></span>
                                    </label>
                                </div>
                            </div>
                        </div>
            </form>
        </template>
        <template #footer>
                    <button
                        type="button"
                        :class="['br-btn', isUpdate ? 'br-btn-action-edit' : 'br-btn-action-create']"
                        @click="saveEntity"
                        :disabled="isSaving">
                        <span v-text="MODULE.texts.modal[isSaving ? 'saving' : 'save']"></span>
                    </button>
        </template>
    </FormModal>
</template>

<script>
import * as Alerts from "@System/Helpers/Alerts.js";
import { initCrudModule } from "@System/Helpers/ModuleFactory.js";
import * as Forms from "@System/Helpers/Forms.js";
import * as Requests from "@System/Helpers/Requests.js";
import * as Utils from "@System/Helpers/Utils.js";
import {createInitialLoad} from "@System/Helpers/InitialLoad.js";
import InternalCodePrefixMixin from "@System/Mixins/InternalCodePrefixMixin.js";
import CatalogListSkeleton from "@System/Components/Catalogs/CatalogListSkeleton.vue";
import FormModal from "@System/Components/Generics/FormModal.vue";
import CatalogFormTabs from "@System/Components/Catalogs/CatalogFormTabs.vue";
import TableActionsDropdown from "@System/Components/Generics/TableActionsDropdown.vue";
import AddCategory from "@System/Components/Catalogs/AddCategory.vue";
import OfferingImportModal from "@System/Components/Catalogs/Offerings/OfferingImportModal.vue";
import SelectNoOptions from "@System/Components/Generics/SelectNoOptions.vue";
import {upsertCatalogOption} from "@System/Helpers/CatalogReferenceOptions.js";

const FORM_TABS = [
    {id: "general", label: "SERVICIO", description: "Datos y precios", icon: "fa-solid fa-hand-holding-heart", fields: ["internal_code", "name", "estimated_duration_minutes", "price", "min_price", "max_price", "status"]},
    {id: "attributes", label: "DETALLE", description: "Catálogo y visibilidad", icon: "fa-solid fa-clipboard-list", fields: ["description", "categories", "see_my_web", "see_my_web_price"]},
    {id: "taxes", label: "IMPUESTOS", description: "Aplicación del IGV", icon: "fa-solid fa-receipt", fields: ["igv_exempt", "price_includes_tax"]},
    {id: "configuration", label: "CONFIGURACIÓN", description: "Vigencia y cupos", icon: "fa-solid fa-sliders", fields: ["expires_at", "commission_type", "commission_value", "capacity_control_enabled", "capacity_limit"]}
];

const MODULE_CONFIG = {
    entity: "services",
    menuId: "menu-items-services",
    pageTitle: "Servicios",
    pageTitleSingular: "Servicio",
    internalCodeEntity: "service",
    breadcrumbParent: "Catálogo e inventario",
    perPage: 10
};

const FORM_FIELDS = {
    internal_code: "",
    name: "",
    description: "",
    price: "",
    price_includes_tax: true,
    igv_exempt: false,
    min_price: "",
    max_price: "",
    estimated_duration_minutes: "",
    capacity_control_enabled: false,
    capacity_limit: "",
    commission_type: null,
    commission_value: "",
    expires_at: "",
    currency: null,
    categories: [],
    see_my_web: false,
    see_my_web_price: false,
    status: null
};

const FORM_FIELD_CONFIG = {
    internal_code: {trim: true},
    name: {trim: true},
    description: {normalize: true},
    price: {toNumber: true, minValue: 0},
    price_includes_tax: {toBoolean: true},
    igv_exempt: {toBoolean: true},
    min_price: {toNumber: true, minValue: 0},
    max_price: {toNumber: true, minValue: 0},
    estimated_duration_minutes: {toNumber: true, minValue: 1},
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
    price: {required: true, number: true, min: 0},
    price_includes_tax: {required: false},
    igv_exempt: {required: false},
    min_price: {required: false, number: true, min: 0},
    max_price: {required: false, number: true, min: 0},
    estimated_duration_minutes: {required: false, number: true, min: 1},
    capacity_control_enabled: {required: false},
    capacity_limit: {required: false, number: true, min: 1},
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
    price: "Precio de venta",
    price_includes_tax: "Precio incluye IGV",
    igv_exempt: "IGV exonerado",
    min_price: "Precio mínimo",
    max_price: "Precio máximo",
    estimated_duration_minutes: "Duración estimada",
    capacity_control_enabled: "Control de cupos",
    capacity_limit: "Cupos disponibles",
    commission_type: "Tipo de comisión",
    commission_value: "Valor de comisión",
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
        add: "Agregar servicio",
        edit: "Editar servicio",
        import: "Cargar masivamente",
        download: "Descargar Excel"
    },
    form: {
        internalCode: "Código interno",
        name: "Nombre",
        description: "Descripción comercial adicional",
        price: "Precio de venta",
        minPrice: "Precio mínimo",
        maxPrice: "Precio máximo",
        estimatedDuration: "Duración estimada",
        capacityControl: "¿Controlar cupos?",
        capacityLimit: "Cantidad de cupos",
        commissionType: "Tipo de comisión",
        commissionValue: "Valor de comisión",
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
        store: "Agregar servicio",
        update: "Editar servicio",
        save: "Guardar servicio",
        saving: "Guardando..."
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

const initialLoad = createInitialLoad({skeleton: true});

export default {
    name: "ServicesMain",
    mixins: [InternalCodePrefixMixin],
    components: {FormModal, CatalogFormTabs, TableActionsDropdown, AddCategory, OfferingImportModal, SelectNoOptions, CatalogListSkeleton},
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
            skeletonEnabled: initialLoad.skeleton,
            initialLoading: true,
            activeFormTab: FORM_TABS[0].id
        };

    },
    mounted: async function() {

        Utils.navbarItem("menu-parent-items", {addClass: "open"});
        Utils.navbarItem(this.config.entity.page.menu.id, {});

        initialLoad.start();

        try {

            const initParams = await this.initParams();
            this.isInitialized = true;

            if(initParams) await this.listEntity({});

        }finally {

            initialLoad.finish();
            this.initialLoading = false;

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

                this.options.categories = response.data.config.categories;
                this.options.currencies = response.data.config.currencies;
                this.options.statuses   = response.data.config.statuses;
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
        getListFilters() {

            const filters = this.entityList.filters;

            return {filter_by: filters.filter_by?.code, word: filters.word};

        },
        async downloadRecords() {

            if(this.isExporting || !await Alerts.confirmDownload({resource: "el listado de servicios"})) {

                return;

            }

            this.isExporting = true;
            Alerts.swals({type: "default", title: "Preparando reporte de servicios"});

            try {

                await Requests.download({
                    route: this.routeActions.export,
                    data: this.getListFilters(),
                    fileName: "servicios.xlsx",
                    showAlert: true
                });

            }finally {

                Alerts.swals({show: false});
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
                const currencyOption = this.currencies.find(e => e.code === record?.currency_id),
                      categoryItems  = (record?.category_items ?? []).map(e => e?.category_id),
                      statusOption   = this.statuses.find(e => e.code === record?.status);

                entityForms.data.id               = record.id;
                entityForms.data.internal_code    = this.stripInternalCodePrefix(record.internal_code);
                entityForms.data.name             = record.name;
                entityForms.data.description      = record.description;
                entityForms.data.price            = record.price;
                entityForms.data.price_includes_tax = Boolean(record.price_includes_tax ?? true) && !Boolean(record.igv_exempt ?? false);
                entityForms.data.igv_exempt       = Boolean(record.igv_exempt ?? false);
                entityForms.data.min_price        = record.min_price;
                entityForms.data.max_price        = record.max_price;
                entityForms.data.estimated_duration_minutes = record.estimated_duration_minutes;
                entityForms.data.capacity_control_enabled = Boolean(record.capacity_control_enabled ?? false);
                entityForms.data.capacity_limit = record.capacity_limit ?? "";
                entityForms.data.commission_type  = this.commissionTypeOptions.find(e => e.code === (record.commission_type || "none")) ?? this.commissionTypeOptions[0];
                entityForms.data.commission_value = record.commission_value;
                entityForms.data.expires_at       = record.expires_at ? String(record.expires_at).slice(0, 10) : "";
                entityForms.data.currency         = currencyOption;
                entityForms.data.categories       = this.categories.filter(e => categoryItems.includes(e.code));
                entityForms.data.see_my_web       = Boolean(record.see_my_web ?? false);
                entityForms.data.see_my_web_price = Boolean(record.see_my_web_price ?? false);
                entityForms.data.status           = statusOption;

            }else {

                // Set defaults for new record
                entityForms.data.internal_code = this.generateCode({length: 7});
                entityForms.data.currency      = this.currencies.length > 0 ? this.currencies[0] : null;
                entityForms.data.price_includes_tax = true;
                entityForms.data.igv_exempt = false;
                entityForms.data.capacity_control_enabled = false;
                entityForms.data.capacity_limit = "";
                entityForms.data.commission_type = this.commissionTypeOptions[0];
                entityForms.data.commission_value = "";
                entityForms.data.expires_at = "";
                entityForms.data.status        = this.statuses.length > 0 ? this.statuses[0] : null;

            }

            Alerts.modals({type: "show", id: entityForms.extras.modals.default.id});
            this.$nextTick(() => Alerts.tooltips({}));

        },
        generateCodeAction(event) {

            this.forms[this.entity].createUpdate.data.internal_code = this.generateCode({length: 7});

            Alerts.toastrs({type: "success", subtitle: "Código interno generado correctamente."});
            Alerts.dismissTooltip(event?.currentTarget);

        },
        syncTaxExemption(form = {}) {

            if(form.igv_exempt) form.price_includes_tax = false;

        },
        syncPublicationSettings(form = {}) {

            if(!form.see_my_web) form.see_my_web_price = false;

        },
        handleCategoryCreated({record}) {

            upsertCatalogOption(this.options, "categories", record);

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
                    this.focusFirstErrorTab(validation.errors);

                    Alerts.generateAlert({
                        type: "error",
                        messages: Forms.getDescriptiveErrors(validation.errors, this.MODULE.errorLabels),
                        msgContent: this.config.messages.errorValidate
                    });
                    this.isSaving = false;
                    return;

                }

                const preparedData  = Forms.prepareFormData(formData, this.MODULE.formFieldConfig);
                if(preparedData.igv_exempt) preparedData.price_includes_tax = false;
                if(!preparedData.capacity_control_enabled) preparedData.capacity_limit = null;
                if(!preparedData.see_my_web) preparedData.see_my_web_price = false;
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

                const minPrice = parseFloat(formData.min_price) || 0;
                const maxPrice = parseFloat(formData.max_price) || 0;
                const price    = parseFloat(formData.price) || 0;

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

            if(formData.capacity_control_enabled && !Number(formData.capacity_limit || 0)) {

                result.errors.capacity_limit = ["Indica cuántos cupos estarán disponibles."];
                result.bool = false;

            }

            return result;

        },
        // Others
        isDefined(value) {

            return Utils.isDefined({value});

        },
        generateCode({length}) {

            return Utils.generateCode({length});

        },
        separatorNumber(value) {

            return Utils.separatorNumber(value);

        },
        serviceDurationLabel(record) {

            const minutes = Number(record?.estimated_duration_minutes || 0);

            if(minutes <= 0) return "No definida";
            if(minutes < 60) return `${minutes} min`;

            const hours = Math.floor(minutes / 60);
            const rest = minutes % 60;

            return rest > 0 ? `${hours} h ${rest} min` : `${hours} h`;

        },
        commissionLabel(record) {

            const type = record?.commission_type || "none";
            const value = Number(record?.commission_value || 0);

            if(type === "percentage") return `${this.separatorNumber(value)}%`;
            if(type === "fixed") return `${record?.currency?.sign || ""} ${this.separatorNumber(value)}`.trim();

            return "Sin comisión";

        },
        capacityLabel(record) {

            if(!record?.capacity_control_enabled) return "Cupos ilimitados";

            const available = Number(record?.available_capacity ?? 0);
            const limit = Number(record?.capacity_limit ?? 0);

            return `${available} disponibles de ${limit}`;

        },
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
