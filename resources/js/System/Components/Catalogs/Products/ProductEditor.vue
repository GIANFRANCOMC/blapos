<template>
    <Teleport to="body">
    <div
        class="modal fade br-entity-modal br-product-editor"
        :id="productForm.extras.modals.default.id"
        data-bs-backdrop="static"
        tabindex="-1"
        role="dialog"
        aria-modal="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header br-entity-modal__header">
                    <div>
                        <p class="br-entity-modal__eyebrow mb-1">Catálogo comercial</p>
                        <h2 class="modal-title br-entity-modal__title">
                            {{ modalTitles.createUpdate[isUpdate ? "update" : "store"] }}
                        </h2>
                    </div>
                    <button
                        type="button"
                        class="br-modal-close"
                        data-bs-dismiss="modal"
                        aria-label="Cerrar">
                        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                    </button>
                </div>

                <div class="modal-body br-entity-modal__body">
                    <div class="br-entity-tabs-shell">
                        <button
                            type="button"
                            class="br-entity-tabs-nav br-entity-tabs-nav--previous"
                            :disabled="!hasPreviousFormTab"
                            :aria-label="previousFormTabLabel"
                            :title="previousFormTabLabel"
                            @click="moveFormTab(-1)">
                            <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
                        </button>

                        <nav class="nav nav-pills nav-fill br-entity-tabs" aria-label="Secciones del formulario">
                            <button
                                v-for="(tab, index) in formTabs"
                                :key="tab.id"
                                type="button"
                                :class="['nav-link', 'br-entity-tab', {'active is-active': activeFormTab === tab.id}]"
                                :aria-selected="activeFormTab === tab.id"
                                :aria-controls="`${modalId}-tab-${tab.id}`"
                                role="tab"
                                @click="activeFormTab = tab.id">
                                <span class="br-entity-tab__step" v-text="index + 1"></span>
                                <span class="br-entity-tab__content">
                                    <strong v-text="tab.label"></strong>
                                    <small v-text="tab.description"></small>
                                </span>
                                <span v-if="tabHasErrors(tab.id)" class="br-entity-tab__error" aria-label="Contiene errores">
                                    <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
                                </span>
                            </button>
                        </nav>

                        <button
                            type="button"
                            class="br-entity-tabs-nav br-entity-tabs-nav--next"
                            :disabled="!hasNextFormTab"
                            :aria-label="nextFormTabLabel"
                            :title="nextFormTabLabel"
                            @click="moveFormTab(1)">
                            <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                        </button>
                    </div>

                    <form @submit.prevent="saveEntity">
                        <section
                            v-show="activeFormTab === 'general'"
                            :id="`${modalId}-tab-general`"
                            class="br-entity-form-section"
                            role="tabpanel">
                            <div class="row g-3">
                                <InputText
                                    v-model="productForm.data.internal_code"
                                    hasDiv
                                    :title="MODULE.texts.form.internalCode"
                                    :titleClass="[config.forms.classes.title]"
                                    isRequired
                                    :maxlength="internalCodeEditableMaxlength"
                                    :showCharCounter="false"
                                    hasTextBottom
                                    :textBottomInfo="productForm.errors?.internal_code"
                                    xl="4"
                                    lg="4">
                                    <template v-if="internalCodePrefixLabel" v-slot:inputGroupPrepend>
                                        <span class="input-group-text br-internal-code-prefix" v-text="internalCodePrefixLabel"></span>
                                    </template>
                                    <template v-slot:defaultAppend>
                                        <button
                                            type="button"
                                            class="br-field-help"
                                            data-bs-toggle="tooltip"
                                            data-bs-placement="top"
                                            :title="MODULE.texts.form.internalCodeHelp"
                                            aria-label="¿Para qué sirve el código interno?">
                                            <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                                        </button>
                                    </template>
                                    <template v-slot:inputGroupAppend>
                                        <button
                                            type="button"
                                            class="br-input-action"
                                            data-bs-toggle="tooltip"
                                            data-bs-placement="top"
                                            :title="MODULE.texts.form.generateInternalCodeTooltip"
                                            :aria-label="MODULE.texts.form.generateInternalCodeTooltip"
                                            @click="generateInternalCode($event)">
                                            <i class="fa-solid fa-rotate" aria-hidden="true"></i>
                                        </button>
                                    </template>
                                </InputText>

                                <InputText
                                    v-model="productForm.data.barcode"
                                    hasDiv
                                    :title="MODULE.texts.form.barcode"
                                    :titleClass="[config.forms.classes.title]"
                                    isRequired
                                    maxlength="13"
                                    :showCharCounter="false"
                                    hasTextBottom
                                    :textBottomInfo="productForm.errors?.barcode"
                                    xl="4"
                                    lg="4">
                                    <template v-slot:defaultAppend>
                                        <button
                                            type="button"
                                            class="br-field-help"
                                            data-bs-toggle="tooltip"
                                            data-bs-placement="top"
                                            :title="MODULE.texts.form.barcodeHelp"
                                            aria-label="¿Para qué sirve el código de barras?">
                                            <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                                        </button>
                                    </template>
                                    <template v-slot:inputGroupAppend>
                                        <button
                                            type="button"
                                            class="br-input-action"
                                            data-bs-toggle="tooltip"
                                            data-bs-placement="top"
                                            :title="MODULE.texts.form.generateBarcodeTooltip"
                                            :aria-label="MODULE.texts.form.generateBarcodeTooltip"
                                            @click="generateBarcode($event)">
                                            <i class="fa-solid fa-rotate" aria-hidden="true"></i>
                                        </button>
                                    </template>
                                </InputText>

                                <InputText
                                    v-model="productForm.data.name"
                                    hasDiv
                                    :title="MODULE.texts.form.name"
                                    :titleClass="[config.forms.classes.title]"
                                    isRequired
                                    maxlength="50"
                                    showCharCounter
                                    hasTextBottom
                                    :textBottomInfo="productForm.errors?.name"
                                    xl="4"
                                    lg="4"/>

                                <InputNumber
                                    v-model="productForm.data.price"
                                    hasDiv
                                    :title="MODULE.texts.form.price"
                                    :titleClass="[config.forms.classes.title]"
                                    isRequired
                                    hasTextBottom
                                    :textBottomInfo="productForm.errors?.price"
                                    xl="4"
                                    lg="4">
                                    <template v-slot:inputGroupPrepend>
                                        <span class="input-group-text br-currency-prefix">
                                            <span class="br-currency-prefix__symbol" v-text="currencySign"></span>
                                        </span>
                                    </template>
                                </InputNumber>

                                <InputNumber
                                    v-model="productForm.data.min_price"
                                    hasDiv
                                    :title="MODULE.texts.form.minPrice"
                                    :titleClass="[config.forms.classes.title]"
                                    hasTextBottom
                                    :textBottomInfo="productForm.errors?.min_price"
                                    xl="4"
                                    lg="4">
                                    <template v-slot:inputGroupPrepend>
                                        <span class="input-group-text br-currency-prefix">
                                            <span class="br-currency-prefix__symbol" v-text="currencySign"></span>
                                        </span>
                                    </template>
                                </InputNumber>

                                <InputNumber
                                    v-model="productForm.data.max_price"
                                    hasDiv
                                    :title="MODULE.texts.form.maxPrice"
                                    :titleClass="[config.forms.classes.title]"
                                    hasTextBottom
                                    :textBottomInfo="productForm.errors?.max_price"
                                    xl="4"
                                    lg="4">
                                    <template v-slot:inputGroupPrepend>
                                        <span class="input-group-text br-currency-prefix">
                                            <span class="br-currency-prefix__symbol" v-text="currencySign"></span>
                                        </span>
                                    </template>
                                </InputNumber>

                                <InputSlot
                                    hasDiv
                                    :title="MODULE.texts.form.status"
                                    :titleClass="[config.forms.classes.title]"
                                    isRequired
                                    hasTextBottom
                                    :textBottomInfo="productForm.errors?.status"
                                    xl="4"
                                    lg="4">
                                    <template v-slot:input>
                                        <v-select
                                            v-model="productForm.data.status"
                                            :options="statuses"
                                            :class="config.forms.classes.select2"
                                            :clearable="false"
                                            :searchable="false"
                                            append-to-body>
                                            <template #selected-option="option">
                                                <span class="br-select-selected-text" :title="getSelectOptionLabel(option)" v-text="getSelectOptionLabel(option)"></span>
                                            </template>
                                            <template #option="option">
                                                <span class="br-select-option-text" :title="getSelectOptionLabel(option)" v-text="getSelectOptionLabel(option)"></span>
                                            </template>
                                            <template #no-options><SelectNoOptions/></template>
                                        </v-select>
                                    </template>
                                </InputSlot>
                            </div>
                        </section>

                        <section
                            v-show="activeFormTab === 'commercial'"
                            :id="`${modalId}-tab-commercial`"
                            class="br-entity-form-section"
                            role="tabpanel">
                            <div class="row g-3">

                                <InputText
                                    v-model="productForm.data.description"
                                    hasDiv
                                    :title="MODULE.texts.form.commercialDescription"
                                    :titleClass="[config.forms.classes.title]"
                                    maxlength="100"
                                    showCharCounter
                                    hasTextBottom
                                    :textBottomInfo="productForm.errors?.description"
                                    xl="12"
                                    lg="12"/>

                                <InputSlot
                                    hasDiv
                                    :title="MODULE.texts.form.brand"
                                    :titleClass="[config.forms.classes.title]"
                                    hasTextBottom
                                    :textBottomInfo="productForm.errors?.brand_id"
                                    xl="4"
                                    lg="4">
                                    <template #defaultAppend>
                                        <AddBrand
                                            trigger-mode="link"
                                            trigger-text="Agregar"
                                            trigger-title="Agregar una nueva marca"
                                            :internal-code-prefix="internalCodePrefixes.brand"
                                            :disabled="isSaving"
                                            @created="handleBrandCreated"/>
                                    </template>
                                    <template v-slot:input>
                                        <v-select
                                            v-model="productForm.data.brand"
                                            :options="brands"
                                            :class="config.forms.classes.select2"
                                            :clearable="true"
                                            :searchable="true"
                                            append-to-body>
                                            <template #selected-option="option">
                                                <span
                                                    class="br-select-selected-text"
                                                    :title="getSelectOptionLabel(option)"
                                                    v-text="getSelectOptionLabel(option)">
                                                </span>
                                            </template>
                                            <template #option="option">
                                                <span
                                                    class="br-select-option-text"
                                                    :title="getSelectOptionLabel(option)"
                                                    v-text="getSelectOptionLabel(option)">
                                                </span>
                                            </template>
                                            <template #no-options>
                                                <SelectNoOptions/>
                                            </template>
                                        </v-select>
                                    </template>
                                </InputSlot>

                                <InputSlot
                                    hasDiv
                                    :title="MODULE.texts.form.categories"
                                    :titleClass="[config.forms.classes.title]"
                                    hasTextBottom
                                    :textBottomInfo="productForm.errors?.categories"
                                    xl="8"
                                    lg="8">
                                    <template #defaultAppend>
                                        <AddCategory
                                            trigger-mode="link"
                                            trigger-text="Agregar"
                                            trigger-title="Agregar una nueva categoría"
                                            :internal-code-prefix="internalCodePrefixes.category"
                                            :disabled="isSaving"
                                            @created="handleCategoryCreated"/>
                                    </template>
                                    <template v-slot:input>
                                        <v-select
                                            v-model="productForm.data.categories"
                                            :options="categories"
                                            :class="config.forms.classes.select2"
                                            :clearable="true"
                                            :searchable="true"
                                            :multiple="true"
                                            append-to-body>
                                            <template #selected-option="option">
                                                <span
                                                    class="br-select-selected-text"
                                                    :title="getSelectOptionLabel(option)"
                                                    v-text="getSelectOptionLabel(option)">
                                                </span>
                                            </template>
                                            <template #option="option">
                                                <span
                                                    class="br-select-option-text"
                                                    :title="getSelectOptionLabel(option)"
                                                    v-text="getSelectOptionLabel(option)">
                                                </span>
                                            </template>
                                            <template #no-options>
                                                <SelectNoOptions/>
                                            </template>
                                        </v-select>
                                    </template>
                                </InputSlot>

                                <InputDate
                                    v-model="productForm.data.expires_at"
                                    hasDiv
                                    :title="MODULE.texts.form.expiresAt"
                                    :titleClass="[config.forms.classes.title]"
                                    hasTextBottom
                                    :textBottomInfo="productForm.errors?.expires_at"
                                    xl="4"
                                    lg="4"/>

                                <InputSlot
                                    hasDiv
                                    title="Comisión"
                                    :titleClass="[config.forms.classes.title]"
                                    hasTextBottom
                                    :textBottomInfo="productForm.errors?.commission_type"
                                    xl="4"
                                    lg="4">
                                    <template v-slot:input>
                                        <v-select
                                            v-model="productForm.data.commission_type"
                                            :options="commissionTypeOptions"
                                            :reduce="option => option.code"
                                            :class="config.forms.classes.select2"
                                            :clearable="false"
                                            :searchable="false"
                                            append-to-body
                                            @update:modelValue="resetCommissionValue"/>
                                    </template>
                                </InputSlot>

                                <InputNumber
                                    v-model="productForm.data.commission_value"
                                    hasDiv
                                    title="Valor de comisión"
                                    :titleClass="[config.forms.classes.title]"
                                    :isRequired="productForm.data.commission_type !== 'none'"
                                    :disabled="productForm.data.commission_type === 'none'"
                                    :minValue="0"
                                    :maxValue="productForm.data.commission_type === 'percentage' ? 100 : null"
                                    hasTextBottom
                                    :textBottomInfo="productForm.errors?.commission_value"
                                    xl="4"
                                    lg="4">
                                    <template v-slot:inputGroupPrepend>
                                        <span class="input-group-text br-currency-prefix">
                                            <span class="br-currency-prefix__symbol" v-text="productForm.data.commission_type === 'percentage' ? '%' : currencySign"></span>
                                        </span>
                                    </template>
                                </InputNumber>

                                <div class="col-12">
                                    <div class="br-entity-publication-intro">
                                        <strong>Impuestos</strong>
                                    </div>
                                    <div class="br-entity-publication-settings">
                                        <label class="br-entity-switch" :for="`${modalId}-igv-exempt`">
                                            <input
                                                :id="`${modalId}-igv-exempt`"
                                                v-model="productForm.data.igv_exempt"
                                                class="form-check-input"
                                                type="checkbox"
                                                role="switch"
                                                @change="syncTaxExemption(productForm.data)">
                                            <span>
                                                <strong>IGV exonerado</strong>
                                                <small>Si está activo, el IGV no se calcula para este producto al vender.</small>
                                            </span>
                                        </label>

                                        <label class="br-entity-switch" :for="`${modalId}-price-includes-tax`">
                                            <input
                                                :id="`${modalId}-price-includes-tax`"
                                                v-model="productForm.data.price_includes_tax"
                                                class="form-check-input"
                                                type="checkbox"
                                                :disabled="productForm.data.igv_exempt"
                                                role="switch">
                                            <span>
                                                <strong>Precio incluye IGV</strong>
                                                <small>Si está activo, el precio de venta ya contiene el impuesto y no incrementará el total al vender.</small>
                                            </span>
                                        </label>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="br-entity-publication-intro">
                                        <strong>Visibilidad para clientes</strong>
                                        <small>
                                            Define qué información se mostrará fuera de la plataforma. Esta configuración es independiente del estado Activo o Inactivo del producto.
                                        </small>
                                    </div>
                                    <div class="br-entity-publication-settings">
                                        <label class="br-entity-switch" :for="`${modalId}-publish`">
                                            <input
                                                :id="`${modalId}-publish`"
                                                v-model="productForm.data.see_my_web"
                                                class="form-check-input"
                                                type="checkbox"
                                                role="switch"
                                                :aria-describedby="`${modalId}-publish-help`"
                                                @change="syncPublicationSettings">
                                            <span>
                                                <strong>Publicar producto</strong>
                                                <small :id="`${modalId}-publish-help`">
                                                    Permite que los clientes vean este producto en el catálogo. No cambia su estado Activo o Inactivo dentro de la plataforma.
                                                </small>
                                            </span>
                                        </label>

                                        <label
                                            class="br-entity-switch"
                                            :class="{'is-disabled': !productForm.data.see_my_web}"
                                            :for="`${modalId}-show-price`">
                                            <input
                                                :id="`${modalId}-show-price`"
                                                v-model="productForm.data.see_my_web_price"
                                                class="form-check-input"
                                                type="checkbox"
                                                role="switch"
                                                :aria-describedby="`${modalId}-show-price-help`"
                                                :disabled="!productForm.data.see_my_web">
                                            <span>
                                                <strong>Mostrar precio</strong>
                                                <small :id="`${modalId}-show-price-help`">
                                                    Permite que los clientes vean el precio en el catálogo. Solo funciona cuando Publicar producto está activado.
                                                </small>
                                            </span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <section
                            v-show="activeFormTab === 'inventory'"
                            :id="`${modalId}-tab-inventory`"
                            class="br-entity-form-section mb-0"
                            role="tabpanel">
                            <div v-if="productForm.errors?.inventory" class="alert alert-danger py-2" v-text="firstError(productForm.errors.inventory)"></div>

                            <div v-if="productForm.data.inventory.length" class="br-entity-inventory">
                                <div class="br-entity-inventory__head br-table-header-surface">
                                    <span>Almacén</span>
                                    <span class="br-label-with-help">
                                        <span>{{ isUpdate ? "Stock actual" : "Stock inicial" }}</span>
                                        <button
                                            type="button"
                                            class="br-field-help"
                                            data-bs-toggle="tooltip"
                                            data-bs-placement="top"
                                            :title="isUpdate
                                                ? 'Cantidad disponible actualmente en el almacén. Se modifica desde Inventario.'
                                                : 'Cantidad disponible al registrar el producto en este almacén.'"
                                            :aria-label="isUpdate
                                                ? 'Ayuda sobre stock actual'
                                                : 'Ayuda sobre stock inicial'">
                                            <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                                        </button>
                                    </span>
                                    <span class="br-label-with-help">
                                        <span>Stock mínimo</span>
                                        <button
                                            type="button"
                                            class="br-field-help"
                                            data-bs-toggle="tooltip"
                                            data-bs-placement="top"
                                            title="Cantidad mínima requerida en el almacén. Al alcanzarla, el sistema muestra una alerta de stock."
                                            aria-label="Ayuda sobre stock mínimo">
                                            <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                                        </button>
                                    </span>
                                </div>

                                <div
                                    v-for="(inventory, index) in productForm.data.inventory"
                                    :key="inventory.warehouse_id"
                                    :class="['br-entity-inventory__row', inventoryStockStatus(inventory).className]">
                                    <div class="br-entity-inventory__warehouse">
                                        <strong v-text="inventory.branch_name"></strong>
                                        <span v-text="inventory.warehouse_name"></span>
                                        <small
                                            :class="['br-entity-inventory-status', inventoryStockStatus(inventory).className]">
                                            <i :class="inventoryStockStatus(inventory).icon" aria-hidden="true"></i>
                                            <span v-text="inventoryStockStatus(inventory).text"></span>
                                        </small>
                                    </div>

                                    <div class="br-entity-inventory__field">
                                        <span
                                            v-if="isUpdate"
                                            class="br-entity-readonly-metric"
                                            aria-label="Stock actual">
                                            <span class="br-entity-inventory__mobile-label">Stock actual</span>
                                            <strong v-text="separatorNumber(inventory.initial_stock)"></strong>
                                            <small>unidades</small>
                                        </span>
                                        <InputNumber
                                            v-else
                                            v-model="inventory.initial_stock"
                                            title="Stock inicial"
                                            :titleClass="['br-entity-inventory__mobile-label']"
                                            :minValue="0"
                                            :decimals="4"
                                            hasTextBottom
                                            :textBottomInfo="inventoryFieldErrors(index, 'initial_stock')"/>
                                    </div>

                                    <div class="br-entity-inventory__field">
                                        <InputNumber
                                            v-model="inventory.minimum_stock"
                                            title="Stock mínimo"
                                            :titleClass="['br-entity-inventory__mobile-label']"
                                            :minValue="0"
                                            :decimals="4"
                                            hasTextBottom
                                            :textBottomInfo="inventoryFieldErrors(index, 'minimum_stock')"/>
                                    </div>
                                </div>
                            </div>

                            <div v-else class="br-entity-inventory-empty">
                                <i class="fa-solid fa-warehouse" aria-hidden="true"></i>
                                <span>No hay almacenes activos. Crea una sucursal con almacén antes de registrar productos.</span>
                            </div>
                        </section>
                    </form>
                </div>

                <div class="modal-footer br-entity-modal__footer">
                    <button
                        type="button"
                        class="br-btn br-btn-cancel"
                        data-bs-dismiss="modal">
                        Cancelar
                    </button>
                    <button
                        type="button"
                        :class="['br-btn', isUpdate ? 'br-btn-action-update' : 'br-btn-action-create']"
                        :disabled="isSaving || productForm.data.inventory.length === 0"
                        @click="saveEntity">
                        <span v-text="submitButtonText"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    </Teleport>
</template>

<script>
import * as Alerts from "@System/Helpers/Alerts.js";
import {initCrudModule} from "@System/Helpers/ModuleFactory.js";
import * as Forms from "@System/Helpers/Forms.js";
import * as Requests from "@System/Helpers/Requests.js";
import * as Utils from "@System/Helpers/Utils.js";
import AddBrand from "@System/Components/Catalogs/AddBrand.vue";
import AddCategory from "@System/Components/Catalogs/AddCategory.vue";
import SelectNoOptions from "@System/Components/Generics/SelectNoOptions.vue";
import InputText from "@System/Components/InputText.vue";
import InputNumber from "@System/Components/InputNumber.vue";
import InputDate from "@System/Components/InputDate.vue";
import InputSlot from "@System/Components/InputSlot.vue";
import InternalCodePrefixMixin from "@System/Mixins/InternalCodePrefixMixin.js";
import VueSelect from "vue-select";
import "vue-select/dist/vue-select.css";
import {FORM_TABS, FORM_FIELDS, FORM_FIELD_CONFIG, VALIDATION_RULES, ERROR_LABELS, PRODUCT_TEXTS, generateEan13, isValidEan13} from "./productFormConfig.js";

const MODULE = {
    config: {entity: "products", internalCodeEntity: "product"},
    formFields: FORM_FIELDS,
    formFieldConfig: FORM_FIELD_CONFIG,
    validationRules: VALIDATION_RULES,
    errorLabels: ERROR_LABELS,
    texts: PRODUCT_TEXTS
};

let productEditorInstance = 0;

export default {
    name: "ProductEditor",
    mixins: [InternalCodePrefixMixin],
    components: {
        AddBrand,
        AddCategory,
        SelectNoOptions,
        InputText,
        InputNumber,
        InputDate,
        InputSlot,
        "v-select": VueSelect
    },
    emits: ["saved"],
    props: {
        initialOptions: {
            type: Object,
            default: () => ({})
        }
    },
    data() {

        const state = initCrudModule({
            entity: "products",
            pageTitle: "Productos",
            pageTitleSingular: "Producto"
        });

        state.forms.products.createUpdate.data = Forms.initFormData(Utils.cloneJson(FORM_FIELDS));
        state.forms.products.createUpdate.extras.modals.default.id += `-${++productEditorInstance}`;

        return {
            ...state,
            MODULE,
            activeFormTab: FORM_TABS[0].id,
            isSaving: false,
            optionsLoaded: false
        };

    },
    mounted() {

        document.getElementById(this.productForm.extras.modals.default.id)?.addEventListener("hidden.bs.modal", this.resetProductForm);

    },
    beforeUnmount() {

        document.getElementById(this.productForm.extras.modals.default.id)?.removeEventListener("hidden.bs.modal", this.resetProductForm);
        Alerts.tooltips({show: false});

    },
    methods: {
        async ensureOptions() {

            if(this.optionsLoaded) {

                return true;

            }

            const available = this.initialOptions;

            if(available?.brands && available?.categories && available?.currencies && available?.warehouses && available?.statuses) {

                this.options = Utils.cloneJson(available);
                this.optionsLoaded = true;

                return true;

            }

            const response = await Requests.get({
                route: this.routeActions.initParams,
                data: {page: "main"},
                showAlert: true
            });

            if(!Requests.valid({result: response})) {

                return false;

            }

            this.options = response.data?.config ?? {};
            this.optionsLoaded = true;

            return true;

        },
        getSelectOptionLabel(option) {

            return option?.label ?? option?.name ?? option?.value ?? "";

        },
        upsertReferenceOption(reference, record) {

            if(!record?.id) {

                return null;

            }

            if(!this.options[reference]) {
                this.options[reference] = {records: []};
            }

            if(!Array.isArray(this.options[reference].records)) {
                this.options[reference].records = [];
            }

            const records = this.options[reference].records;
            const recordIndex = records.findIndex(item => Number(item.id) === Number(record.id));

            if(recordIndex >= 0) {
                records.splice(recordIndex, 1, record);
            }else {
                records.push(record);
            }

            records.sort((first, second) =>
                String(first.name ?? "").localeCompare(String(second.name ?? ""), "es", {sensitivity: "base"})
            );

            return {
                code: record.id,
                label: record.name,
                data: record
            };

        },
        handleBrandCreated({record}) {

            this.upsertReferenceOption("brands", record);

        },
        handleCategoryCreated({record}) {

            this.upsertReferenceOption("categories", record);

        },
        async openModal(record = null) {

            if(!await this.ensureOptions()) {

                return;

            }

            Alerts.tooltips({show: false});
            this.resetProductForm();

            if(this.isDefined(record)) {

                const categoryIds = (record.category_items ?? []).map(category => category.category_id);

                Object.assign(this.productForm.data, {
                    id: record.id,
                    internal_code: this.stripInternalCodePrefix(record.internal_code),
                    barcode: record.barcode,
                    name: record.name,
                    description: record.description,
                    price: record.price,
                    price_includes_tax: Boolean(record.price_includes_tax ?? true) && !Boolean(record.igv_exempt ?? false),
                    igv_exempt: Boolean(record.igv_exempt ?? false),
                    expires_at: record.expires_at ? String(record.expires_at).slice(0, 10) : "",
                    commission_type: record.commission_type ?? (Number(record.commission_rate || 0) > 0 ? "percentage" : "none"),
                    commission_value: Number(record.commission_value ?? record.commission_rate ?? 0),
                    min_price: record.min_price,
                    max_price: record.max_price,
                    currency: this.currencies.find(currency => currency.code === record.currency_id) ?? null,
                    categories: this.categories.filter(category => categoryIds.includes(category.code)),
                    brand: this.resolveBrandOption(record),
                    see_my_web: Boolean(record.see_my_web),
                    see_my_web_price: Boolean(record.see_my_web && record.see_my_web_price),
                    inventory: this.buildInventory(record),
                    status: this.statuses.find(status => status.code === record.status) ?? null
                });

            }else {

                Object.assign(this.productForm.data, {
                    internal_code: this.generateRandomCode(7),
                    barcode: generateEan13(),
                    currency: this.currencies[0] ?? null,
                    categories: [],
                    brand: null,
                    price_includes_tax: true,
                    igv_exempt: false,
                    expires_at: "",
                    commission_type: "none",
                    commission_value: "",
                    see_my_web: true,
                    see_my_web_price: false,
                    inventory: this.buildInventory(),
                    status: this.statuses[0] ?? null
                });

            }

            if(this.productForm.data.commission_type === "none") {

                this.productForm.data.commission_value = "";

            }

            Alerts.modals({type: "show", id: this.productForm.extras.modals.default.id});
            this.$nextTick(() => Alerts.tooltips({}));

        },
        resetProductForm() {

            const form = this.forms[this.entity].createUpdate;

            form.data = Forms.initFormData(Utils.cloneJson(this.MODULE.formFields));
            form.errors = {};
            this.activeFormTab = FORM_TABS[0].id;

        },
        buildInventory(record = null) {

            const warehouseItems = record?.warehouse_items ?? [];

            return this.warehouses.map(warehouse => {

                const warehouseItem = warehouseItems.find(item => Number(item.warehouse_id) === Number(warehouse.id));

                return {
                    warehouse_id: Number(warehouse.id),
                    branch_name: warehouse.branch?.name ?? "Sucursal",
                    warehouse_name: warehouse.name,
                    initial_stock: record ? Number(warehouseItem?.quantity ?? 0) : "",
                    minimum_stock: record && warehouseItem?.minimum_stock != null
                        ? Number(warehouseItem.minimum_stock)
                        : ""
                };

            });

        },
        resolveBrandOption(record) {

            const activeBrand = this.brands.find(brand => Number(brand.code) === Number(record?.brand_id));

            if(activeBrand) {

                return activeBrand;

            }

            if(!record?.brand) {

                return null;

            }

            return {
                code: record.brand.id,
                label: `${record.brand.name}${record.brand.status === "inactive" ? " (Inactiva)" : ""}`,
                data: record.brand
            };

        },
        generateInternalCode(event) {

            this.productForm.data.internal_code = this.generateRandomCode(7);
            Alerts.dismissTooltip(event?.currentTarget);

        },
        generateBarcode(event) {

            this.productForm.data.barcode = generateEan13();
            Alerts.dismissTooltip(event?.currentTarget);

        },
        resetCommissionValue() {

            this.productForm.data.commission_value = "";
            delete this.productForm.errors.commission_value;

        },
        syncPublicationSettings() {

            if(!this.productForm.data.see_my_web) {

                this.productForm.data.see_my_web_price = false;

            }

        },
        syncTaxExemption(form = {}) {

            if(form.igv_exempt) {

                form.price_includes_tax = false;

            }

        },
        async saveEntity() {

            if(this.isSaving) {

                return;

            }

            this.productForm.errors = {};

            try {

                const formData = Utils.cloneJson(this.productForm.data);
                const validation = this.validateFormData(formData);

                if(!validation.bool) {

                    this.productForm.errors = validation.errors;
                    await Alerts.generateAlert({
                        type: "error",
                        messages: Forms.getDescriptiveErrors(validation.errors, this.MODULE.errorLabels),
                        msgContent: this.config.messages.errorValidate
                    });
                    this.focusFirstTabWithErrors(validation.errors);
                    return;

                }

                const preparedData = Forms.prepareFormData(formData, this.MODULE.formFieldConfig);
                if(preparedData.igv_exempt) {

                    preparedData.price_includes_tax = false;

                }

                if(!preparedData.see_my_web) {

                    preparedData.see_my_web_price = false;

                }

                if(preparedData.commission_type === "none") {

                    preparedData.commission_value = 0;

                }

                const id = preparedData.id;
                const isUpdate = this.isDefined(id);
                preparedData.inventory = preparedData.inventory.map(inventory => ({
                    warehouse_id: Number(inventory.warehouse_id),
                    ...(isUpdate ? {} : {
                        initial_stock: Number(inventory.initial_stock ?? 0)
                    }),
                    minimum_stock: Number(inventory.minimum_stock ?? 0)
                }));
                const requestMethod = isUpdate ? "patch" : "post";
                const route = this.routeActions[isUpdate ? "update" : "store"];

                this.isSaving = true;
                Alerts.swals({
                    type: isUpdate ? "update" : "create",
                    entity: "producto"
                });

                const result = await Requests[requestMethod]({route, data: preparedData, id});

                if(Requests.valid({result})) {

                    Alerts.modals({type: "hide", id: this.productForm.extras.modals.default.id});
                    Alerts.generateAlert({type: "success", msgContent: result.data.msg});

                    this.$emit("saved", result.data?.item);

                }else {

                    Forms.handleFormResponseErrors({
                        result,
                        formErrorsObject: this.productForm.errors,
                        config: this.config,
                        errorLabels: this.MODULE.errorLabels
                    });

                    this.focusFirstTabWithErrors(this.productForm.errors);

                }

            }catch(error) {

                Alerts.generateAlert({type: "error", messages: [error], msgContent: this.config.messages.catchError});

            }finally {

                this.isSaving = false;

            }

        },
        validateFormData(formData) {

            const result = Forms.validateFormData(formData, this.validationRules, {isDescriptive: true, errorLabels: this.MODULE.errorLabels});

            if(formData.commission_type !== "none" && !result.errors.commission_value) {

                const commissionValue = Number(formData.commission_value);

                if(formData.commission_value === "" || !Number.isFinite(commissionValue) || commissionValue <= 0) {

                    result.errors.commission_value = ["Debe ser mayor que 0 cuando el producto tiene comisión."];
                    result.bool = false;

                }else if(formData.commission_type === "percentage" && commissionValue > 100) {

                    result.errors.commission_value = ["No puede superar el 100%."];
                    result.bool = false;

                }

            }

            if(!isValidEan13(formData.barcode)) {

                result.errors.barcode = ["Ingrese un código válido o genere uno automáticamente."];
                result.bool = false;

            }

            if(!Array.isArray(formData.inventory) || formData.inventory.length === 0) {

                result.errors.inventory = ["Se requiere al menos un almacén activo."];
                result.bool = false;

            }else {

                formData.inventory.forEach((inventory, index) => {

                    const inventoryFields = this.isUpdate
                        ? ["minimum_stock"]
                        : ["initial_stock", "minimum_stock"];

                    inventoryFields.forEach(field => {

                        const value = Number(inventory[field]);

                        if(!Number.isFinite(value) || value < 0) {

                            result.errors[`inventory.${index}.${field}`] = [
                                "Debe ser mayor o igual a 0."
                            ];
                            result.bool = false;

                        }

                    });

                });

            }

            if(!result.errors.price) {

                const minPrice = parseFloat(formData.min_price) || 0;
                const maxPrice = parseFloat(formData.max_price) || 0;
                const price    = parseFloat(formData.price) || 0;

                if(minPrice > 0 && maxPrice > 0 && maxPrice < minPrice) {

                    result.errors.max_price = ["Debe ser mayor o igual al precio mínimo."];
                    result.bool = false;

                }else if(minPrice > 0 && price < minPrice) {

                    result.errors.price = ["Debe ser mayor o igual al precio mínimo."];
                    result.bool = false;

                }else if(maxPrice > 0 && price > maxPrice) {

                    result.errors.price = ["Debe ser menor o igual al precio máximo."];
                    result.bool = false;

                }

            }

            return result;

        },
        inventoryFieldErrors(index, field) {

            const error = this.productForm.errors?.[`inventory.${index}.${field}`];

            return Array.isArray(error) ? error : (error ? [error] : []);

        },
        tabHasErrors(tabId) {

            const tab = FORM_TABS.find(item => item.id === tabId);
            const errorFields = Object.keys(this.productForm.errors ?? {});

            return tab?.fields.some(field => errorFields.some(errorField => errorField === field || errorField.startsWith(`${field}.`))) ?? false;

        },
        moveFormTab(direction) {

            const targetTab = this.formTabs[this.activeFormTabIndex + direction];

            if (targetTab) {
                this.activeFormTab = targetTab.id;
            }

        },
        focusFirstTabWithErrors(errors) {

            const errorFields = Object.keys(errors ?? {});
            const tab = FORM_TABS.find(item =>
                item.fields.some(field =>
                    errorFields.some(errorField => errorField === field || errorField.startsWith(`${field}.`))
                )
            );

            this.activeFormTab = tab?.id ?? FORM_TABS[0].id;

        },
        firstError(error) {

            return Array.isArray(error) ? error[0] : error ?? "";

        },
        inventoryStockStatus(inventory) {

            const hasCurrentStock = inventory?.initial_stock !== "" && inventory?.initial_stock !== null;
            const hasMinimumStock = inventory?.minimum_stock !== "" && inventory?.minimum_stock !== null;

            if(!hasCurrentStock && !hasMinimumStock) {

                return {
                    className: "is-pending",
                    icon: "fa-regular fa-circle",
                    text: "Pendiente de registrar"
                };

            }

            const currentStock = Number(inventory?.initial_stock ?? 0);
            const minimumStock = Number(inventory?.minimum_stock ?? 0);
            const isLowStock = currentStock <= minimumStock;

            if(isLowStock) {

                return {
                    className: "is-alert",
                    icon: "fa-solid fa-triangle-exclamation",
                    text: "Stock bajo o en el mínimo"
                };

            }

            return {
                className: "is-healthy",
                icon: "fa-solid fa-circle-check",
                text: "Inventario saludable"
            };

        },
        isDefined(value) {

            return Utils.isDefined({value});

        },
        generateRandomCode(length) {

            return Utils.generateCode({length});

        },
        separatorNumber(value) {

            return Utils.separatorNumber(value);

        }
    },
    computed: {
        entity() {

            return this.MODULE.config.entity;

        },
        productForm() {

            return this.forms[this.entity].createUpdate;

        },
        modalId() {

            return this.productForm.extras.modals.default.id;

        },
        formTabs() {

            return FORM_TABS;

        },
        activeFormTabIndex() {

            return this.formTabs.findIndex(tab => tab.id === this.activeFormTab);

        },
        hasPreviousFormTab() {

            return this.activeFormTabIndex > 0;

        },
        hasNextFormTab() {

            return this.activeFormTabIndex < this.formTabs.length - 1;

        },
        previousFormTabLabel() {

            const previousTab = this.formTabs[this.activeFormTabIndex - 1];

            return previousTab ? `Ir a ${previousTab.label}` : "No hay una pestaña anterior";

        },
        nextFormTabLabel() {

            const nextTab = this.formTabs[this.activeFormTabIndex + 1];

            return nextTab ? `Ir a ${nextTab.label}` : "No hay una pestaña siguiente";

        },
        routeActions() {

            return this.config.entity.routes;

        },
        categories() {

            return (this.options?.categories?.records ?? []).map(category => ({
                code: category.id,
                label: category.name,
                data: category
            }));

        },
        brands() {

            return (this.options?.brands?.records ?? []).map(brand => ({
                code: brand.id,
                label: brand.name,
                data: brand
            }));

        },
        currencies() {

            return (this.options?.currencies?.records ?? []).map(currency => ({
                code: currency.id,
                label: currency.plural_name,
                data: currency
            }));

        },
        statuses() {

            return (this.options?.statuses ?? []).map(status => ({
                code: status.code,
                label: status.label
            }));

        },
        commissionTypeOptions() {

            return [
                {code: "none", label: "Sin comisión"},
                {code: "percentage", label: "Porcentaje"},
                {code: "fixed", label: "Monto fijo por unidad"}
            ];

        },
        submitButtonText() {

            if(this.isSaving) {

                return this.MODULE.texts.modal[this.isUpdate ? "updating" : "storing"];

            }

            return this.MODULE.texts.modal[this.isUpdate ? "update" : "store"];

        },
        warehouses() {

            return this.options?.warehouses?.records ?? [];

        },
        currencySign() {

            return this.productForm.data.currency?.data?.sign ?? "";

        },
        isUpdate() {

            return this.isDefined(this.productForm.data.id);

        },
        modalTitles() {

            return {
                createUpdate: this.productForm.extras.modals.default.titles
            };

        },
        validationRules() {

            return Utils.cloneJson(this.MODULE.validationRules);

        }
    }
};
</script>
