<template>

<FormModal :modal-id="modalId" :title="isUpdate ? 'Editar producto' : 'Agregar producto'"
        :modal-class="'fg-modal--editor'" modal-size="modal-lg"
        :close-disabled="isSaving" :submit-disabled="isSaving || productForm.data.inventory.length === 0" :submit-button-text="submitButtonText" @submit="saveEntity">
        <template #body>
            <div class="fg-editor">
                <AppTabs v-model="activeFormTab" :tabs="formTabs" :has-errors="tabHasErrors" :id-prefix="modalId" :controls-prefix="modalId" variant="descriptive" layout="content" label="Secciones del producto"/>
                <form class="fg-editor__panel" @submit.prevent="saveEntity">
                    <section v-show="activeFormTab === 'general'" :id="modalId + '-tab-general'" :aria-labelledby="modalId + '-tab-trigger-general'" role="tabpanel" class="fg-stack">
                        <div class="fg-section-heading"><h3 class="fg-section-title">Datos del producto</h3><span class="fg-help"><span class="fg-required">*</span> Obligatorios</span></div>
                        <div class="fg-grid">
                            <InternalCodeInput v-model="productForm.data.internal_code" v-bind="internalCodeInputProps(productForm.data)" title="Código interno" is-required show-char-counter has-text-bottom :text-bottom-info="productForm.errors?.internal_code" :disabled="isSaving">
                                <template #action><button class="fg-link" type="button" :disabled="isSaving" :title="MODULE.texts.form.generateInternalCodeTooltip" @click="generateInternalCode($event)">Generar</button></template>
                            </InternalCodeInput>
                            <InputText v-model="productForm.data.barcode" title="Código de barras" no-whitespace is-required maxlength="13" show-char-counter has-text-bottom :text-bottom-info="productForm.errors?.barcode" :disabled="isSaving">
                                <template #default><button class="fg-link" type="button" :disabled="isSaving" :title="MODULE.texts.form.generateBarcodeTooltip" @click="generateBarcode($event)">Generar</button></template>
                            </InputText>
                        </div>
                        <InputText v-model="productForm.data.name" title="Nombre del producto" is-required maxlength="100" show-char-counter :placeholder="placeholders.names.product" has-text-bottom :text-bottom-info="productForm.errors?.name" :disabled="isSaving"/>
                        <div class="fg-grid fg-grid--price">
                            <InputNumber v-model="productForm.data.price" title="Precio de venta" is-required :placeholder="placeholders.price" has-text-bottom :text-bottom-info="productForm.errors?.price" :disabled="isSaving">
                                <template #inputGroupPrepend><span class="fg-field__prefix">{{ currencySign }}</span></template>
                            </InputNumber>
                            <FgField label="Moneda" required :input-id="modalId + '-currency'" :error="productForm.errors?.currency_id">
                                <FgSelect :input-id="modalId + '-currency'" v-model="productForm.data.currency" :options="currencies" class="fg-select" :clearable="false" :searchable="false" :disabled="isSaving"/>
                            </FgField>
                            <FgField label="Estado" required :input-id="modalId + '-status'" :error="productForm.errors?.status">
                                <FgSelect :input-id="modalId + '-status'" v-model="productForm.data.status" :options="statuses" class="fg-select" :clearable="false" :searchable="false" :disabled="isSaving"/>
                            </FgField>
                        </div>
                        <CatalogTaxFields v-model:price-includes-tax="productForm.data.price_includes_tax" v-model:igv-exempt="productForm.data.igv_exempt" :disabled="isSaving"/>
                        <div class="fg-grid">
                            <FgField label="Categorías" :input-id="modalId + '-categories'" :error="productForm.errors?.categories">
                                <template #action><AddCategory :internal-code-prefix="internalCodePrefixes.category" :disabled="isSaving" @created="handleCategoryCreated"/></template>
                                <FgSelect :input-id="modalId + '-categories'" v-model="productForm.data.categories" :options="categories" class="fg-select" multiple :disabled="isSaving" :placeholder="placeholders.categories">
                                    <template #no-options><span>No hay categorías disponibles.</span></template>
                                </FgSelect>
                            </FgField>
                            <FgField label="Marca" :input-id="modalId + '-brand'" :error="productForm.errors?.brand_id">
                                <template #action><AddBrand :internal-code-prefix="internalCodePrefixes.brand" :disabled="isSaving" @created="handleBrandCreated"/></template>
                                <FgSelect :input-id="modalId + '-brand'" v-model="productForm.data.brand" :options="brands" class="fg-select" :disabled="isSaving" placeholder="Selecciona una marca">
                                    <template #no-options><span>No hay marcas disponibles.</span></template>
                                </FgSelect>
                            </FgField>
                        </div>
                        <details class="fg-disclosure" :open="detailsOpen" @toggle="detailsOpen = $event.target.open">
                            <summary><span><span>Descripción y límites de precio</span><small class="fg-help">{{ productForm.data.description || productForm.data.min_price || productForm.data.max_price ? 'Configurado' : 'Opcional · Sin configurar' }}</small></span><span class="fg-chevron" aria-hidden="true"></span></summary>
                            <div class="fg-stack">
                                <InputTextArea v-model="productForm.data.description" title="Descripción comercial" maxlength="100" :placeholder="placeholders.optional" has-text-bottom :text-bottom-info="productForm.errors?.description" :disabled="isSaving"/>
                                <div class="fg-grid">
                                    <InputNumber v-model="productForm.data.min_price" title="Precio mínimo" :placeholder="placeholders.minPrice" has-text-bottom :text-bottom-info="productForm.errors?.min_price" :disabled="isSaving"><template #inputGroupPrepend><span class="fg-field__prefix">{{ currencySign }}</span></template></InputNumber>
                                    <InputNumber v-model="productForm.data.max_price" title="Precio máximo" :placeholder="placeholders.maxPrice" has-text-bottom :text-bottom-info="productForm.errors?.max_price" :disabled="isSaving"><template #inputGroupPrepend><span class="fg-field__prefix">{{ currencySign }}</span></template></InputNumber>
                                </div>
                            </div>
                        </details>
                    </section>
                    <section v-show="activeFormTab === 'inventory'" :id="modalId + '-tab-inventory'" :aria-labelledby="modalId + '-tab-trigger-inventory'" role="tabpanel" class="fg-stack">
                        <h3 class="fg-section-title">Stock por almacén</h3>
                        <div class="fg-product-summary"><strong class="fg-section-title">{{ productName || 'Producto sin nombre' }}</strong><span class="fg-help">{{ productForm.data.inventory.length }} almacenes</span></div>
                        <div v-if="productForm.errors?.inventory" class="fg-notice fg-notice--danger" role="alert">{{ firstError(productForm.errors.inventory) }}</div>
                        <ProductWarehouseInventory v-if="productForm.data.inventory.length" v-model:rows="productForm.data.inventory" mode="edit" :is-update="isUpdate" :disabled="isSaving" :measurement-unit="measurementUnit" :errors="productForm.errors"/>
                        <FgEmptyState v-else title="No hay almacenes activos" description="Crea una sucursal con almacén antes de registrar productos."/>
                        <p class="fg-help">{{ isUpdate ? 'El stock actual se modifica desde Inventario. Aquí puedes ajustar el stock mínimo.' : 'Indica las existencias iniciales y el stock mínimo de cada almacén.' }}</p>
                    </section>
                    <section v-show="activeFormTab === 'configuration'" :id="modalId + '-tab-configuration'" :aria-labelledby="modalId + '-tab-trigger-configuration'" role="tabpanel" class="fg-stack">
                        <CatalogVisibilityFields :id-prefix="modalId" item-label="producto" v-model:published="productForm.data.see_my_web" v-model:show-price="productForm.data.see_my_web_price" :disabled="isSaving"/>
                        <div class="fg-section-divider"/>
                        <p>Vigencia y comisión</p>
                        <InputDate v-model="productForm.data.expires_at" title="Disponible hasta" :min="minimumExpiryDate || undefined" :help="isUpdate ? 'Sin fecha, el producto no tiene límite de venta.' : 'Sin fecha, el producto no tiene límite de venta. Elige hoy o una fecha futura.'" has-text-bottom :text-bottom-info="productForm.errors?.expires_at" :disabled="isSaving"/>
                        <div class="fg-grid">
                            <FgField label="Comisión" :input-id="modalId + '-commission'" :error="productForm.errors?.commission_type" help="La comisión es interna y no cambia el precio que paga el cliente.">
                                <FgSelect :input-id="modalId + '-commission'" v-model="productForm.data.commission_type" :options="commissionTypeOptions" :reduce="option => option.code" class="fg-select" :clearable="false" :searchable="false" :disabled="isSaving" @update:modelValue="resetCatalogCommission(productForm)"/>
                            </FgField>
                            <CommissionValueInput v-model="productForm.data.commission_value" :commission-type="productForm.data.commission_type" :currency-sign="currencySign" :disabled="isSaving" title="Valor de comisión" has-text-bottom :text-bottom-info="productForm.errors?.commission_value"/>
                        </div>
                    </section>
                </form>
            </div>
        </template>
    </FormModal>


</template>
<script>
import {productToday, validateProductExpiry} from "./productDate.js";
import FgSelect from "@System/Components/Figma/FgSelect.vue";
import FgField from "@System/Components/Figma/FgField.vue";
import FgEmptyState from "@System/Components/Figma/FgEmptyState.vue";
import InputTextArea from "@System/Components/InputTextArea.vue";
import * as Alerts from "@System/Helpers/Alerts.js";
import {initCrudModule} from "@System/Helpers/ModuleFactory.js";
import * as Forms from "@System/Helpers/Forms.js";
import * as Requests from "@System/Helpers/Requests.js";
import * as Utils from "@System/Helpers/Utils.js";
import AddBrand from "@System/Components/Catalogs/AddBrand.vue";
import AddCategory from "@System/Components/Catalogs/AddCategory.vue";
import AppTabs from "@/Shared/AppTabs.vue";
import ProductWarehouseInventory from "./ProductWarehouseInventory.vue";
import FormModal from "@System/Components/Generics/FormModal.vue";
import InputText from "@System/Components/InputText.vue";
import InputNumber from "@System/Components/InputNumber.vue";
import InputDate from "@System/Components/InputDate.vue";
import InternalCodePrefixMixin from "@System/Mixins/InternalCodePrefixMixin.js";
import InternalCodeInput from "@System/Components/Catalogs/InternalCodeInput.vue";
import CatalogTaxFields from "@System/Components/Catalogs/CatalogTaxFields.vue";
import CatalogVisibilityFields from "@System/Components/Catalogs/CatalogVisibilityFields.vue";
import {catalogFormPlaceholders} from "@System/Components/Catalogs/catalogFormPlaceholders.js";
import CommissionValueInput from "@System/Components/Catalogs/CommissionValueInput.vue";
import {resetCatalogCommission, validateCatalogCommission} from "@System/Components/Catalogs/catalogFormValidation.js";
import {upsertCatalogOption} from "@System/Helpers/CatalogReferenceOptions.js";
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
    components: {FgSelect,FgField, FgEmptyState, InputTextArea,
        InternalCodeInput,
        CatalogTaxFields,
        CatalogVisibilityFields,
        CommissionValueInput,
        AddBrand,
        AddCategory,
        AppTabs,
        ProductWarehouseInventory,
        FormModal,
        InputText,
        InputNumber,
        InputDate
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
            placeholders: catalogFormPlaceholders,
            activeFormTab: FORM_TABS[0].id,
            minimumExpiryDate: "",
            isSaving: false,
            detailsOpen: false,
            originalInventoryRows: [],
            measurementUnit: null,
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

            return upsertCatalogOption(this.options, reference, record);

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
            this.minimumExpiryDate = this.isDefined(record) ? "" : productToday(this.options.timezone || "America/Lima");

            if(this.isDefined(record)) {

                this.measurementUnit = record.measurement_unit ?? null;

                const categoryIds = (record.category_items ?? []).map(category => category.category_id);

                Object.assign(this.productForm.data, {
                    id: record.id,
                    internal_code: this.setEditableInternalCode(record.internal_code),
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
                    status: this.statuses.find(status => status.code === record.status) ?? null
                });

            }else {

                Object.assign(this.productForm.data, {
                    internal_code: this.setEditableInternalCode(this.generateRandomCode(7), true),
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
                    status: this.statuses[0] ?? null
                });

            }

            this.originalInventoryRows = this.buildInventory(record);
            this.productForm.data.inventory = [...this.originalInventoryRows];

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
            this.originalInventoryRows = [];
            this.measurementUnit = null;
            this.activeFormTab = FORM_TABS[0].id;
            this.detailsOpen = false;

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
        resetCatalogCommission,
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
                        headerTitle: "Revisa los campos indicados",
                        messages: Forms.getDescriptiveErrors(validation.errors, this.MODULE.errorLabels)
                    });
                    this.focusFirstTabWithErrors(validation.errors);
                    return;

                }

                const preparedData = Forms.prepareFormData(formData, this.MODULE.formFieldConfig);
                Object.assign(preparedData, this.internalCodePayload(preparedData.internal_code));
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
                Alerts.loading({title: isUpdate ? "Guardando cambios" : "Guardando producto"});
                const result = await Requests[requestMethod]({route, data: preparedData, id}).finally(Alerts.close);

                if(Requests.valid({result})) {

                    Alerts.showMessage({
                        type: "success",
                        title: isUpdate ? "Cambios guardados" : "Producto guardado",
                        text: result.data.msg
                    });

                    Alerts.modals({type: "hide", id: this.modalId});
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

            validateCatalogCommission(result, formData);
            validateProductExpiry(result, formData, {isUpdate: this.isUpdate, today: productToday(this.options.timezone || "America/Lima")});

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

                const minPrice = Utils.normalizeNumber(formData.min_price);
                const maxPrice = Utils.normalizeNumber(formData.max_price);
                const price    = Utils.normalizeNumber(formData.price);

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
        tabHasErrors(tabId) {

            const tab = FORM_TABS.find(item => item.id === tabId);
            const errorFields = Object.keys(this.productForm.errors ?? {});

            return tab?.fields.some(field => errorFields.some(errorField => errorField === field || errorField.startsWith(`${field}.`))) ?? false;

        },
        focusFirstTabWithErrors(errors) {

            const errorFields = Object.keys(errors ?? {});
            const tab = FORM_TABS.find(item =>
                item.fields.some(field =>
                    errorFields.some(errorField => errorField === field || errorField.startsWith(`${field}.`))
                )
            );

            this.activeFormTab = tab?.id ?? FORM_TABS[0].id;
            if(errorFields.some(field => ["description", "min_price", "max_price"].includes(field))) this.detailsOpen = true;

        },
        firstError(error) {

            return Array.isArray(error) ? error[0] : error ?? "";

        },
        isDefined(value) {

            return Utils.isDefined({value});

        },
        generateRandomCode(length) {

            return Utils.generateCode({length});

        }
    },
    computed: {
        entity() {

            return this.MODULE.config.entity;

        },
        productForm() {

            return this.forms[this.entity].createUpdate;

        },
        productName() {

            return this.productForm.data.name?.trim() || "";

        },
        modalId() {

            return this.productForm.extras.modals.default.id;

        },
        formTabs() {

            return FORM_TABS.map(tab => ({...tab, label: {general: "Producto", inventory: "Inventario", configuration: "Configuración"}[tab.id]}));

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

            return this.isSaving ? "Guardando…" : this.isUpdate ? "Guardar cambios" : this.MODULE.texts.modal.save;

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
