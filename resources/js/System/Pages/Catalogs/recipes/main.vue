<template>
    <FgCatalogList kind="recipes" title="Recetas y platillos"
        description="Define qué consume cada platillo y cuánto rinde su preparación."
        singular="receta" plural="recetas" list-title="Recetas registradas"
        v-model:filter-by="filterBy" v-model:word="word" :filter-options="filterOptions"
        placeholder="Buscar platillo, código o nota"
        :applied-word="appliedFilters.word?.trim()" :records="records" :initial-loading="initialLoading"
        :loading="loading" :failed="listFailed" :allow-import="false" :allow-export="false"
        @search="searchRecipes" @page="listRecipes" @retry="listRecipes()" @add="openModal()">
        <template #row="{record}">
            <td role="cell" class="text-center">
                <span class="fg-record-table__label" aria-hidden="true">Código interno</span>
                <div class="fg-record-table__value">
                    <InternalCodeValue :value="record.item?.internal_code"/>
                </div>
            </td>
            <td role="cell" class="fg-record-table__identity">
                <span class="fg-entity-table__name">{{ record.item?.name }}</span>
                <div v-if="record.item?.barcode" class="fg-entity-identifiers fg-entity-identifiers--plain">
                    <div class="fg-entity-identifier">
                        <span class="fg-entity-identifier__label colon-at-end">Cód. barras</span>
                        <span class="fg-entity-identifier__value" v-text="record.item.barcode"></span>
                    </div>
                </div>
            </td>
            <td role="cell">
                <span class="fg-record-table__label" aria-hidden="true">Fórmula</span>
                <div class="fg-record-table__value">
                    <div class="fg-entity-offering-details fg-detail-tags--wrap">
                        <span><strong class="colon-at-end">Insumos</strong> <span class="fg-entity-offering-details__value">{{ record.components_count }}</span></span>
                        <span><strong class="colon-at-end">Extras</strong> <span class="fg-entity-offering-details__value">{{ record.toppings_count }}</span></span>
                        <span><strong class="colon-at-end">Sabores</strong> <span class="fg-entity-offering-details__value">{{ record.options_count }}</span></span>
                    </div>
                </div>
            </td>
            <td role="cell">
                <span class="fg-record-table__label" aria-hidden="true">Rendimiento y merma</span>
                <div class="fg-record-table__value">
                    <small class="text-muted d-block">Rendimiento</small>
                    <strong>{{ record.yield_quantity }}</strong>
                    <small class="text-muted ms-2">Merma {{ record.waste_percentage }}%</small>
                </div>
            </td>
            <td role="cell" class="fg-record-table__state text-center">
                <StatusBadge :status="record.status" :formatted-status="record.formatted_status"/>
            </td>
            <td role="cell" class="fg-record-table__actions text-center">
                <TableActionsDropdown :label="`Acciones de ${record.item?.name || 'la receta'}`">
                    <li><button type="button" class="fg-menu__item" @click="openModal(record)">Editar receta</button></li>
                </TableActionsDropdown>
            </td>
        </template>
    </FgCatalogList>

    <FormModal
        modal-id="recipeModal"
        :title="isUpdate ? 'Editar receta' : 'Agregar receta'"
        modal-class="fg-record-editor fg-entity-modal--tabbed"
        modal-size="modal-xl"
        dialog-class="modal-dialog-scrollable"
        :close-disabled="saving"
        :keyboard="!saving"
        :submit-disabled="saving"
        :submit-button-class="isUpdate ? 'fg-button--edit' : ''"
        :submit-button-text="saving ? 'Guardando…' : isUpdate ? 'Guardar cambios' : 'Guardar receta'"
        @submit="saveRecipe">
        <template #body>
            <AppTabs
                v-model="activeTab"
                :tabs="tabs"
                :has-errors="tabHasErrors"
                id-prefix="recipe"
                controls-prefix="recipe"
                variant="descriptive" layout="content"
                label="Secciones de receta"/>
            <form @submit.prevent="saveRecipe">
                <section v-show="activeTab === 'general'" id="recipe-tab-general" class="fg-entity-form-section fg-tab-panel" role="tabpanel" aria-labelledby="recipe-tab-trigger-general">
                    <FormGroup title="Platillo y rendimiento" icon="fa-solid fa-utensils" primary>
                        <div class="row g-3">
                            <InputSlot
                                input-id="recipe-catalog-item"
                                hasDiv
                                title="Producto o platillo"
                                :titleClass="formTitleClass"
                                isRequired
                                hasTextBottom
                                :textBottomInfo="errors.item_id"
                                xl="6"
                                lg="6">
                                <template #input>
                                    <v-select
                                        input-id="recipe-catalog-item"
                                        v-model="form.item"
                                        :options="items"
                                        label="name"
                                        :clearable="false"
                                        :searchable="true"
                                        append-to-body
                                        placeholder="Selecciona el producto que venderás"/>
                                </template>
                            </InputSlot>

                            <InputSlot
                                input-id="recipe-catalog-status"
                                hasDiv
                                title="Estado"
                                :titleClass="formTitleClass"
                                isRequired
                                hasTextBottom
                                :textBottomInfo="errors.status"
                                xl="3"
                                lg="3">
                                <template #input>
                                    <v-select
                                        input-id="recipe-catalog-status"
                                        v-model="form.status"
                                        :options="statuses"
                                        label="label"
                                        :clearable="false"
                                        append-to-body
                                        :searchable="false"/>
                                </template>
                            </InputSlot>

                            <InputNumber
                                v-model="form.yield_quantity"
                                hasDiv
                                title="Rendimiento"
                                :titleClass="formTitleClass"
                                isRequired
                                hasTextBottom
                                :textBottomInfo="errors.yield_quantity"
                                :hasNegative="false"
                                :minValue="quantityStep"
                                xl="3"
                                lg="3"/>

                            <InputNumber
                                v-model="form.waste_percentage"
                                hasDiv
                                title="Merma esperada"
                                :titleClass="formTitleClass"
                                hasTextBottom
                                :textBottomInfo="errors.waste_percentage"
                                :hasNegative="false"
                                minValue="0"
                                maxValue="100"
                                xl="3"
                                lg="3">
                                <template #inputGroupAppend><span class="input-group-text fg-input-unit-suffix">%</span></template>
                            </InputNumber>

                            <InputTextArea
                                v-model="form.preparation_notes"
                                hasDiv
                                title="Notas operativas"
                                :titleClass="formTitleClass"
                                hasTextBottom
                                :textBottomInfo="errors.preparation_notes"
                                xl="9"
                                lg="9"/>
                        </div>
                    </FormGroup>
                </section>

                <section v-show="activeTab === 'components'" id="recipe-tab-components" class="fg-entity-form-section fg-tab-panel" role="tabpanel" aria-labelledby="recipe-tab-trigger-components">
                    <FormGroup title="Insumos base" icon="fa-solid fa-basket-shopping" stacked>
                        <template #actions>
                            <button type="button" class="fg-button fg-button--small" @click="addComponent">
                                <i class="fa-solid fa-plus" aria-hidden="true"></i>
                                <span>Agregar insumo</span>
                            </button>
                        </template>
                        <ul v-if="errorsByTab.components?.size" class="text-danger small ps-3" role="alert">
                            <li v-for="message in errorsByTab.components" :key="message">{{ message }}</li>
                        </ul>
                        <p class="small text-muted mb-3">Indica qué insumos y cantidades se usan para preparar el platillo.</p>
                        <div class="fg-recipe-lines">
                            <div v-for="(row, index) in form.components" :key="row.uid" class="fg-recipe-line">
                                <v-select v-model="row.item" :options="ingredients" label="name" placeholder="Buscar insumo" append-to-body/>
                                <InputNumber v-model="row.quantity" :hasNegative="false" :minValue="quantityStep" aria-label="Cantidad de insumo" placeholder="Cantidad"/>
                                <InputNumber v-model="row.waste_percentage" :hasNegative="false" minValue="0" maxValue="100" aria-label="Merma del insumo, en porcentaje" placeholder="Merma %"/>
                                <button type="button" class="fg-icon-action fg-button--danger" aria-label="Quitar insumo" title="Quitar insumo" @click="removeRow(form.components, index)">
                                    <i class="fa-solid fa-minus" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>
                    </FormGroup>
                </section>

                <section v-show="activeTab === 'toppings'" id="recipe-tab-toppings" class="fg-entity-form-section fg-tab-panel" role="tabpanel" aria-labelledby="recipe-tab-trigger-toppings">
                    <FormGroup title="Extras" icon="fa-solid fa-circle-plus" stacked>
                        <template #actions>
                            <button type="button" class="fg-button fg-button--small" @click="addTopping">
                                <i class="fa-solid fa-plus" aria-hidden="true"></i>
                                <span>Agregar extra</span>
                            </button>
                        </template>
                        <ul v-if="errorsByTab.toppings?.size" class="text-danger small ps-3" role="alert">
                            <li v-for="message in errorsByTab.toppings" :key="message">{{ message }}</li>
                        </ul>
                        <p class="small text-muted mb-3">Define el precio de cada extra y los insumos que consume.</p>
                        <div v-for="(topping, index) in form.toppings" :key="topping.uid" class="fg-recipe-card">
                            <div class="fg-recipe-card__grid">
                                <InputText v-model="topping.name" placeholder="Nombre del extra"/>
                                <InputNumber v-model="topping.price" aria-label="Precio del extra" placeholder="Precio" :hasNegative="false" minValue="0">
                                    <template #inputGroupPrepend>
                                        <span class="input-group-text fg-currency-prefix">{{ currencySign }}</span>
                                    </template>
                                </InputNumber>
                                <InputNumber v-model="topping.max_quantity" :hasNegative="false" minValue="1" :decimals="0" aria-label="Cantidad máxima del extra" placeholder="Cantidad máx."/>
                                <button type="button" class="fg-icon-action fg-button--danger" aria-label="Quitar extra" title="Quitar extra" @click="removeRow(form.toppings, index)">
                                    <i class="fa-solid fa-minus" aria-hidden="true"></i>
                                </button>
                            </div>
                            <button type="button" class="fg-quick-create-trigger fg-quick-create-trigger--link mt-2" @click="addToppingComponent(topping)">
                                <i class="fa-solid fa-circle-plus" aria-hidden="true"></i>
                                <span>Agregar insumo del extra</span>
                            </button>
                            <div v-for="(component, componentIndex) in topping.components" :key="component.uid" class="fg-recipe-line mt-2">
                                <v-select v-model="component.item" :options="ingredients" label="name" placeholder="Buscar insumo" append-to-body/>
                                <InputNumber v-model="component.quantity" :hasNegative="false" :minValue="quantityStep" aria-label="Cantidad de insumo" placeholder="Cantidad"/>
                                <InputNumber v-model="component.waste_percentage" :hasNegative="false" minValue="0" maxValue="100" aria-label="Merma del insumo, en porcentaje" placeholder="Merma %"/>
                                <button type="button" class="fg-icon-action fg-button--danger" aria-label="Quitar insumo del extra" title="Quitar insumo del extra" @click="removeRow(topping.components, componentIndex)">
                                    <i class="fa-solid fa-minus" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>
                    </FormGroup>
                </section>

                <section v-show="activeTab === 'options'" id="recipe-tab-options" class="fg-entity-form-section fg-tab-panel mb-0" role="tabpanel" aria-labelledby="recipe-tab-trigger-options">
                    <FormGroup title="Sabores y variantes" icon="fa-solid fa-layer-group" stacked>
                        <template #actions>
                            <button type="button" class="fg-button fg-button--small" @click="addOption">
                                <i class="fa-solid fa-plus" aria-hidden="true"></i>
                                <span>Agregar sabor</span>
                            </button>
                        </template>
                        <ul v-if="errorsByTab.options?.size" class="text-danger small ps-3" role="alert">
                            <li v-for="message in errorsByTab.options" :key="message">{{ message }}</li>
                        </ul>
                        <p class="small text-muted mb-3">Configura las variantes por porción y los insumos de cada una.</p>
                        <div v-for="(option, index) in form.options" :key="option.uid" class="fg-recipe-card">
                            <div class="fg-recipe-card__grid">
                                <InputText v-model="option.name" placeholder="Nombre del sabor"/>
                                <InputText v-model="option.description" placeholder="Descripción breve"/>
                                <InputNumber v-model="option.max_portions" :hasNegative="false" minValue="1" :decimals="0" aria-label="Porciones máximas del sabor" placeholder="Porciones máx."/>
                                <button type="button" class="fg-icon-action fg-button--danger" aria-label="Quitar sabor" title="Quitar sabor" @click="removeRow(form.options, index)">
                                    <i class="fa-solid fa-minus" aria-hidden="true"></i>
                                </button>
                            </div>
                            <button type="button" class="fg-quick-create-trigger fg-quick-create-trigger--link mt-2" @click="addOptionComponent(option)">
                                <i class="fa-solid fa-circle-plus" aria-hidden="true"></i>
                                <span>Agregar insumo del sabor</span>
                            </button>
                            <div v-for="(component, componentIndex) in option.components" :key="component.uid" class="fg-recipe-line mt-2">
                                <v-select v-model="component.item" :options="ingredients" label="name" placeholder="Buscar insumo" append-to-body/>
                                <InputNumber v-model="component.quantity" :hasNegative="false" :minValue="quantityStep" aria-label="Cantidad de insumo" placeholder="Cantidad"/>
                                <InputNumber v-model="component.waste_percentage" :hasNegative="false" minValue="0" maxValue="100" aria-label="Merma del insumo, en porcentaje" placeholder="Merma %"/>
                                <button type="button" class="fg-icon-action fg-button--danger" aria-label="Quitar insumo del sabor" title="Quitar insumo del sabor" @click="removeRow(option.components, componentIndex)">
                                    <i class="fa-solid fa-minus" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>
                    </FormGroup>
                </section>
            </form>
        </template>
    </FormModal>
</template>

<script>
import FormGroup from "@/Shared/FormGroup.vue";
import FormModal from "@System/Components/Generics/FormModal.vue";
import AppTabs from "@/Shared/AppTabs.vue";
import FgCatalogList from "@System/Components/Catalogs/FgCatalogList.vue";
import TableActionsDropdown from "@System/Components/Generics/TableActionsDropdown.vue";
import InitialPageMixin from "@System/Helpers/InitialPageMixin.js";
import * as Alerts from "@System/Helpers/Alerts.js";
import * as Requests from "@System/Helpers/Requests.js";
import {decimalStep} from "../../../../Shared/Numbers.js";
import InternalCodeValue from "@System/Components/Catalogs/InternalCodeValue.vue";

const CONFIG = {
    entity: "recipes",
    routes: Requests.config({entity: "recipes"}).routes
};

const ALL_RECIPES_FILTER = {code: "all", label: "Todos los filtros"};

const emptyForm = () => ({
    id: null,
    item: null,
    yield_quantity: "",
    waste_percentage: "",
    preparation_notes: "",
    status: {code: "active", label: "Activo"},
    components: [],
    toppings: [],
    options: []
});

const uid = () => `${Date.now()}-${Math.random().toString(16).slice(2)}`;

export default {
    mixins: [InitialPageMixin],
    components: {
        FormGroup,
        FormModal,
        AppTabs,
        FgCatalogList,
        TableActionsDropdown,
        InternalCodeValue
    },
    data() {
        return {
            filterBy: ALL_RECIPES_FILTER,
            word: "",
            appliedFilters: {filter_by: "all", word: ""},
            records: {data: [], links: [], total: 0},
            loading: false,
            listFailed: false,
            saving: false,
            isUpdate: false,
            form: emptyForm(),
            errors: {},
            items: [],
            ingredients: [],
            currencies: [],
            statuses: [],
            activeTab: "general",
            formTitleClass: ["form-label", "colon-at-end"],
            tabs: [
                {id: "general", label: "Datos", description: "Platillo y rendimiento", icon: "fa-solid fa-utensils"},
                {id: "components", label: "Insumos", description: "Fórmula base", icon: "fa-solid fa-basket-shopping"},
                {id: "toppings", label: "Extras", description: "Precio e insumos", icon: "fa-solid fa-circle-plus"},
                {id: "options", label: "Sabores", description: "Variantes del platillo", icon: "fa-solid fa-layer-group"}
            ],
            filterOptions: [
                ALL_RECIPES_FILTER,
                {code: "name", label: "Platillo"},
                {code: "internal_code", label: "Código interno"},
                {code: "barcode", label: "Código de barras"},
                {code: "preparation_notes", label: "Notas operativas"}
            ]
        };
    },
    computed: {
        quantityStep() {
            return decimalStep();
        },
        currencySign() {
            return this.currencies?.[0]?.sign || "S/";
        },
        errorsByTab() {
            const sections = {};
            for(const [field, message] of Object.entries(this.errors)) {
                const section = field.split(".")[0];
                const tab = ["components", "toppings", "options"].includes(section) ? section : "general";
                (sections[tab] ??= new Set()).add(message);
            }
            return sections;
        }
    },
    async mounted() {
        await this.loadInitialParams(() => this.initParams());
        this.listRecipes();
    },
    methods: {
        async initParams() {
            const response = await Requests.get({route: CONFIG.routes.initParams, data: {page: "main"}});
            const config = response?.data?.config || {};

            this.items = config.items?.records || [];
            this.ingredients = config.ingredients?.records || [];
            this.currencies = config.currencies?.records || [];
            this.statuses = config.statuses || [];
            this.form.status = this.statuses.find(status => status.code === "active") || this.form.status;
        },
        searchRecipes() {
            this.appliedFilters = {filter_by: this.filterBy?.code || "all", word: this.word};
            return this.listRecipes();
        },
        async listRecipes(page = "") {
            page = typeof page === "object" ? page?.url : page;
            this.loading = true;
            this.listFailed = false;

            try {
                this.records = Requests.emptyPagination();
                const response = await Requests.get({
                    route: page || CONFIG.routes.list,
                    data: this.appliedFilters,
                    showAlert: false
                });
                this.records = response?.bool ? (response.data || Requests.emptyPagination()) : Requests.emptyPagination();
                this.listFailed = !response?.bool;
            } catch {
                this.listFailed = true;
            } finally {
                this.loading = false;
            }
        },
        openModal(record = null) {
            this.errors = {};
            this.activeTab = "general";
            this.isUpdate = !!record;
            this.form = record ? this.mapRecordToForm(record) : emptyForm();
            this.form.status = this.statuses.find(status => status.code === (this.form.status?.code || this.form.status)) || this.statuses[0] || this.form.status;

            bootstrap.Modal.getOrCreateInstance(document.getElementById("recipeModal")).show();
        },
        mapRecordToForm(record) {
            return {
                id: record.id,
                item: record.item,
                yield_quantity: record.yield_quantity,
                waste_percentage: record.waste_percentage,
                preparation_notes: record.preparation_notes || "",
                status: record.status,
                components: (record.components || []).map(component => ({
                    uid: uid(),
                    item: component.item,
                    quantity: component.quantity,
                    waste_percentage: component.waste_percentage
                })),
                toppings: (record.dish_toppings || []).map(link => ({
                    uid: uid(),
                    name: link.topping?.name || "",
                    description: link.topping?.description || "",
                    price: link.topping?.price || "",
                    currency_id: link.topping?.currency_id || this.currencies?.[0]?.id,
                    max_quantity: link.max_quantity || link.topping?.max_quantity || "",
                    is_default: !!link.is_default,
                    components: (link.topping?.components || []).map(component => ({
                        uid: uid(),
                        item: component.item,
                        quantity: component.quantity,
                        waste_percentage: component.waste_percentage
                    }))
                })),
                options: (record.options || []).map(option => ({
                    uid: uid(),
                    name: option.name,
                    description: option.description || "",
                    max_portions: option.max_portions || "",
                    components: (option.components || []).map(component => ({
                        uid: uid(),
                        item: component.item,
                        quantity: component.quantity,
                        waste_percentage: component.waste_percentage
                    }))
                }))
            };
        },
        addComponent() {
            this.form.components.push({uid: uid(), item: null, quantity: "", waste_percentage: ""});
        },
        addTopping() {
            this.form.toppings.push({
                uid: uid(),
                name: "",
                description: "",
                price: "",
                currency_id: this.currencies?.[0]?.id || null,
                max_quantity: "",
                components: []
            });
        },
        addToppingComponent(topping) {
            topping.components.push({uid: uid(), item: null, quantity: "", waste_percentage: ""});
        },
        addOption() {
            this.form.options.push({uid: uid(), name: "", description: "", max_portions: "", components: []});
        },
        addOptionComponent(option) {
            option.components.push({uid: uid(), item: null, quantity: "", waste_percentage: ""});
        },
        removeRow(collection, index) {
            collection.splice(index, 1);
        },
        buildPayload() {
            const componentPayload = row => ({
                item_id: row.item?.id || row.item_id || null,
                quantity: row.quantity,
                waste_percentage: row.waste_percentage || 0
            });

            return {
                item_id: this.form.item?.id || null,
                yield_quantity: this.form.yield_quantity,
                waste_percentage: this.form.waste_percentage || 0,
                preparation_notes: this.form.preparation_notes,
                status: this.form.status?.code || this.form.status || "active",
                components: this.form.components.map(componentPayload),
                toppings: this.form.toppings.map(topping => ({
                    name: topping.name,
                    description: topping.description,
                    price: topping.price || 0,
                    currency_id: topping.currency_id || this.currencies?.[0]?.id,
                    max_quantity: topping.max_quantity || null,
                    is_default: !!topping.is_default,
                    components: (topping.components || []).map(componentPayload)
                })),
                options: this.form.options.map(option => ({
                    name: option.name,
                    description: option.description,
                    max_portions: option.max_portions || null,
                    components: (option.components || []).map(componentPayload)
                }))
            };
        },
        normalizeErrors(errorBag = {}) {
            return Object.keys(errorBag).reduce((carry, key) => {
                carry[key] = Array.isArray(errorBag[key]) ? errorBag[key][0] : errorBag[key];
                return carry;
            }, {});
        },
        tabHasErrors(tab) {
            return Boolean(this.errorsByTab[tab]?.size);
        },
        async saveRecipe() {
            if(this.saving) return;
            this.saving = true;
            this.errors = {};

            let response;
            try {
                const payload = this.buildPayload();
                response = this.isUpdate
                    ? await Requests.patch({route: CONFIG.routes.update, id: this.form.id, data: payload})
                    : await Requests.post({route: CONFIG.routes.store, data: payload});
            } catch {
                response = null;
            } finally {
                this.saving = false;
            }

            if(!response?.bool || response.data?.bool === false) {
                this.errors = this.normalizeErrors(response?.errors ?? response?.data?.errors ?? {});
                this.activeTab = this.tabs.find(tab => this.tabHasErrors(tab.id))?.id || this.activeTab;
                const hasFieldErrors = Object.keys(this.errors).length > 0;
                const rejected = hasFieldErrors || (response?.code >= 400 && response.code < 500);
                Alerts.toastrs({
                    type: "error",
                    title: rejected ? "Revisa los datos de la receta" : "No pudimos confirmar el guardado",
                    subtitle: rejected
                        ? (hasFieldErrors ? "Corrige los campos indicados y vuelve a guardar." : response?.data?.msg || "No se pudo guardar la receta.")
                        : "Consulta el listado antes de volver a guardar la receta."
                });
                return;
            }

            bootstrap.Modal.getInstance(document.getElementById("recipeModal"))?.hide();
            Alerts.toastrs({type: "success", subtitle: response.data?.msg || "Se guardó la receta."});
            this.listRecipes();
        }
    }
};
</script>
