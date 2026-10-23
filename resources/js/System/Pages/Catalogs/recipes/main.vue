<template>
    <Breadcrumb :list="breadcrumbTitles"/>

    <FiltersSection
        :filter-by-value="filterBy"
        @update:filterByValue="filterBy = $event"
        :filter-word-value="word"
        @update:filterWordValue="word = $event"
        :filter-by-options="filterOptions"
        search-placeholder="Buscar platillo, código o nota"
        :loading="loading"
        filter-by-title="Filtrar por"
        search-title="Búsqueda"
        search-button-text="Buscar"
        add-button-text="Agregar receta"
        :show-add-button="true"
        @search="listRecipes"
        @add="openModal()"/>

    <div class="list-section mb-2 table-responsive">
        <table class="table table-hover br-entity-table br-entity-table--directory mb-0">
            <thead class="br-table-header-surface">
                <tr>
                    <th style="width: 23%;">Identificación</th>
                    <th style="width: 25%;">Platillo</th>
                    <th style="width: 20%;">Fórmula</th>
                    <th style="width: 16%;">Operación</th>
                    <th class="text-center" style="width: 8%;">Estado</th>
                    <th class="text-center" style="width: 8%;">
                        <span class="visually-hidden">Acciones</span>
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr v-if="loading">
                    <td colspan="6"><Loader/></td>
                </tr>
                <template v-else-if="records.total > 0">
                    <tr v-for="record in records.data" :key="record.id">
                        <td class="br-entity-table__identification-cell">
                            <div class="br-entity-identifiers br-entity-identifiers--plain">
                                <div class="br-entity-identifier">
                                    <span class="br-entity-identifier__label colon-at-end">Cód. interno</span>
                                    <span class="br-entity-identifier__value">
                                        <span class="br-entity-code" v-text="record.item?.internal_code || '—'"></span>
                                        <span v-if="record.item?.internal_code" class="br-entity-identifier__actions">
                                            <CopyButton :value="record.item.internal_code" label="Código interno"/>
                                        </span>
                                    </span>
                                </div>
                                <div v-if="record.item?.barcode" class="br-entity-identifier">
                                    <span class="br-entity-identifier__label colon-at-end">Cód. barras</span>
                                    <span class="br-entity-identifier__value">
                                        <span class="br-entity-barcode" v-text="record.item.barcode"></span>
                                        <span class="br-entity-identifier__actions">
                                            <CopyButton :value="record.item.barcode" label="Código de barras"/>
                                        </span>
                                    </span>
                                </div>
                            </div>
                        </td>
                        <td>
                            <strong class="d-block">{{ record.item?.name }}</strong>
                        </td>
                        <td>
                            <span class="br-recipe-pill">{{ record.components_count }} insumos</span>
                            <span class="br-recipe-pill">{{ record.toppings_count }} extras</span>
                            <span class="br-recipe-pill">{{ record.options_count }} sabores</span>
                        </td>
                        <td>
                            <small class="text-muted d-block">Rendimiento</small>
                            <strong>{{ record.yield_quantity }}</strong>
                            <small class="text-muted ms-2">Merma {{ record.waste_percentage }}%</small>
                        </td>
                        <td class="text-center">
                            <StatusBadge :status="record.status" :formatted-status="record.formatted_status"/>
                        </td>
                        <td class="text-center">
                            <button
                                type="button"
                                class="br-icon-action br-icon-action-edit"
                                data-bs-toggle="tooltip"
                                data-bs-placement="top"
                                title="Editar receta"
                                @click="openModal(record)">
                                <i class="fa-solid fa-pen" aria-hidden="true"></i>
                            </button>
                        </td>
                    </tr>
                </template>
                <tr v-else>
                    <td colspan="6"><WithoutData type="image"/></td>
                </tr>
            </tbody>
        </table>
    </div>

    <nav v-if="!loading && records.total > 0" class="d-flex justify-content-center">
        <Paginator :links="records.links" @clickPage="listRecipes"/>
    </nav>

    <div class="modal fade" id="recipeModal" data-bs-backdrop="static" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
            <div class="modal-content br-entity-modal">
                <div class="modal-header br-entity-modal__header">
                    <div>
                        <p class="br-entity-modal__eyebrow mb-1">Catálogo comercial</p>
                        <h5 class="modal-title text-uppercase fw-bold mb-0">
                            {{ isUpdate ? "Editar receta o platillo" : "Agregar receta o platillo" }}
                        </h5>
                    </div>
                    <button type="button" class="btn-header-modal" data-bs-dismiss="modal" aria-label="Cerrar">
                        <i class="fa fa-times icon-close-modal" aria-hidden="true"></i>
                    </button>
                </div>
                <div class="modal-body br-entity-modal__body">
                    <div class="br-entity-tabs-shell">
                        <nav class="nav nav-pills nav-fill br-entity-tabs" aria-label="Secciones de receta">
                            <button
                                v-for="tab in tabs"
                                :key="tab.code"
                                type="button"
                                :class="['nav-link br-entity-tab', {'active is-active': activeTab === tab.code}]"
                                @click="activeTab = tab.code">
                                <span class="br-entity-tab__step">{{ tab.step }}</span>
                                <span class="br-entity-tab__content">
                                    <strong>{{ tab.title }}</strong>
                                    <small>{{ tab.subtitle }}</small>
                                </span>
                            </button>
                        </nav>
                    </div>

                    <form class="br-recipe-form" @submit.prevent="saveRecipe">
                        <section v-show="activeTab === 'general'" class="br-entity-form-section">
                            <div class="row g-3">
                                <InputSlot
                                    hasDiv
                                    title="Platillo o producto vendible"
                                    :titleClass="formTitleClass"
                                    isRequired
                                    hasTextBottom
                                    :textBottomInfo="errors.item_id"
                                    xl="6"
                                    lg="6">
                                    <template #input>
                                        <v-select
                                            v-model="form.item"
                                            :options="items"
                                            label="name"
                                            :clearable="false"
                                            :searchable="true"
                                            class="bg-white"
                                            placeholder="Selecciona el item que venderás"/>
                                    </template>
                                </InputSlot>

                                <InputSlot
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
                                            v-model="form.status"
                                            :options="statuses"
                                            label="label"
                                            :clearable="false"
                                            :searchable="false"
                                            class="bg-white"/>
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
                                    minValue="0.01"
                                    xl="3"
                                    lg="3"/>

                                <InputNumber
                                    v-model="form.waste_percentage"
                                    hasDiv
                                    title="Merma esperada (%)"
                                    :titleClass="formTitleClass"
                                    hasTextBottom
                                    :textBottomInfo="errors.waste_percentage"
                                    :hasNegative="false"
                                    minValue="0"
                                    maxValue="100"
                                    xl="3"
                                    lg="3"/>

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
                        </section>

                        <section v-show="activeTab === 'components'" class="br-entity-form-section">
                            <div class="br-recipe-section-toolbar">
                                <div>
                                    <h3>Insumos base</h3>
                                    <p>Condimentos, materias primas y productos consumidos al preparar el platillo.</p>
                                </div>
                                <button type="button" class="br-btn br-btn-sm br-btn-action-create" @click="addComponent">
                                    <i class="fa-solid fa-plus" aria-hidden="true"></i>
                                    <span>Agregar insumo</span>
                                </button>
                            </div>
                            <div class="br-recipe-lines">
                                <div v-for="(row, index) in form.components" :key="row.uid" class="br-recipe-line">
                                    <v-select v-model="row.item" :options="ingredients" label="name" class="bg-white" placeholder="Insumo"/>
                                    <InputNumber v-model="row.quantity" :hasNegative="false" minValue="0.0001" placeholder="Cantidad"/>
                                    <InputNumber v-model="row.waste_percentage" :hasNegative="false" minValue="0" maxValue="100" placeholder="Merma %"/>
                                    <button type="button" class="br-icon-action br-btn-danger" @click="removeRow(form.components, index)">
                                        <i class="fa-solid fa-minus" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </div>
                        </section>

                        <section v-show="activeTab === 'toppings'" class="br-entity-form-section">
                            <div class="br-recipe-section-toolbar">
                                <div>
                                    <h3>Toppings y extras</h3>
                                    <p>Adicionales vendibles que pueden aumentar el total y consumir insumos propios.</p>
                                </div>
                                <button type="button" class="br-btn br-btn-sm br-btn-action-create" @click="addTopping">
                                    <i class="fa-solid fa-plus" aria-hidden="true"></i>
                                    <span>Agregar extra</span>
                                </button>
                            </div>
                            <div v-for="(topping, index) in form.toppings" :key="topping.uid" class="br-recipe-card">
                                <div class="br-recipe-card__grid">
                                    <InputText v-model="topping.name" placeholder="Nombre del extra"/>
                                    <InputNumber v-model="topping.price" :hasNegative="false" minValue="0">
                                        <template #inputGroupPrepend>
                                            <span class="input-group-text br-currency-prefix">{{ currencySign }}</span>
                                        </template>
                                    </InputNumber>
                                    <InputNumber v-model="topping.max_quantity" :hasNegative="false" minValue="1" :decimals="0" placeholder="Máx."/>
                                    <button type="button" class="br-icon-action br-btn-danger" @click="removeRow(form.toppings, index)">
                                        <i class="fa-solid fa-minus" aria-hidden="true"></i>
                                    </button>
                                </div>
                                <button type="button" class="br-quick-create-trigger br-quick-create-trigger--link mt-2" @click="addToppingComponent(topping)">
                                    <i class="fa-solid fa-circle-plus" aria-hidden="true"></i>
                                    <span>Agregar insumo del extra</span>
                                </button>
                                <div v-for="(component, componentIndex) in topping.components" :key="component.uid" class="br-recipe-line mt-2">
                                    <v-select v-model="component.item" :options="ingredients" label="name" class="bg-white" placeholder="Insumo"/>
                                    <InputNumber v-model="component.quantity" :hasNegative="false" minValue="0.0001" placeholder="Cantidad"/>
                                    <InputNumber v-model="component.waste_percentage" :hasNegative="false" minValue="0" maxValue="100" placeholder="Merma %"/>
                                    <button type="button" class="br-icon-action br-btn-danger" @click="removeRow(topping.components, componentIndex)">
                                        <i class="fa-solid fa-minus" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </div>
                        </section>

                        <section v-show="activeTab === 'options'" class="br-entity-form-section mb-0">
                            <div class="br-recipe-section-toolbar">
                                <div>
                                    <h3>Sabores y variantes</h3>
                                    <p>Útil para pizzas mixtas, combinaciones por porción o variantes con insumos propios.</p>
                                </div>
                                <button type="button" class="br-btn br-btn-sm br-btn-action-create" @click="addOption">
                                    <i class="fa-solid fa-plus" aria-hidden="true"></i>
                                    <span>Agregar sabor</span>
                                </button>
                            </div>
                            <div v-for="(option, index) in form.options" :key="option.uid" class="br-recipe-card">
                                <div class="br-recipe-card__grid">
                                    <InputText v-model="option.name" placeholder="Nombre del sabor"/>
                                    <InputText v-model="option.description" placeholder="Descripción breve"/>
                                    <InputNumber v-model="option.max_portions" :hasNegative="false" minValue="1" :decimals="0" placeholder="Porciones máx."/>
                                    <button type="button" class="br-icon-action br-btn-danger" @click="removeRow(form.options, index)">
                                        <i class="fa-solid fa-minus" aria-hidden="true"></i>
                                    </button>
                                </div>
                                <button type="button" class="br-quick-create-trigger br-quick-create-trigger--link mt-2" @click="addOptionComponent(option)">
                                    <i class="fa-solid fa-circle-plus" aria-hidden="true"></i>
                                    <span>Agregar insumo del sabor</span>
                                </button>
                                <div v-for="(component, componentIndex) in option.components" :key="component.uid" class="br-recipe-line mt-2">
                                    <v-select v-model="component.item" :options="ingredients" label="name" class="bg-white" placeholder="Insumo"/>
                                    <InputNumber v-model="component.quantity" :hasNegative="false" minValue="0.0001" placeholder="Cantidad"/>
                                    <InputNumber v-model="component.waste_percentage" :hasNegative="false" minValue="0" maxValue="100" placeholder="Merma %"/>
                                    <button type="button" class="br-icon-action br-btn-danger" @click="removeRow(option.components, componentIndex)">
                                        <i class="fa-solid fa-minus" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </div>
                        </section>
                    </form>
                </div>
                <div class="modal-footer br-entity-modal__footer">
                    <button type="button" class="br-btn br-btn-cancel" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="br-btn br-btn-action-create" :disabled="saving" @click="saveRecipe">
                        <span>{{ saving ? "Guardando" : (isUpdate ? "Editar receta" : "Agregar receta") }}</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import * as Alerts from "@System/Helpers/Alerts.js";
import * as Requests from "@System/Helpers/Requests.js";
import CopyButton from "@System/Components/CopyButton.vue";

const CONFIG = {
    entity: "recipes",
    routes: Requests.config({entity: "recipes"}).routes
};

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
    components: {CopyButton},
    data() {
        return {
            breadcrumbTitles: [{title: "Catálogo comercial"}, {title: "Recetas y platillos"}],
            filterBy: "all",
            word: "",
            records: {data: [], links: [], total: 0},
            loading: false,
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
                {code: "general", step: 1, title: "Datos", subtitle: "Platillo y rendimiento"},
                {code: "components", step: 2, title: "Insumos", subtitle: "Fórmula base"},
                {code: "toppings", step: 3, title: "Extras", subtitle: "Toppings y adicionales"},
                {code: "options", step: 4, title: "Sabores", subtitle: "Variantes flexibles"}
            ],
            filterOptions: [
                {code: "all", label: "Todos los filtros"},
                {code: "name", label: "Platillo"},
                {code: "internal_code", label: "Código interno"},
                {code: "barcode", label: "Código de barras"},
                {code: "preparation_notes", label: "Notas operativas"}
            ]
        };
    },
    computed: {
        currencySign() {
            return this.currencies?.[0]?.sign || "S/";
        }
    },
    mounted() {
        this.initParams();
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
        async listRecipes(page = "") {
            this.loading = true;
            const response = await Requests.get({
                route: page || CONFIG.routes.list,
                data: {
                    filter_by: this.filterBy,
                    word: this.word
                },
                showAlert: true
            });
            this.records = response?.data || {data: [], links: [], total: 0};
            this.loading = false;
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
        async saveRecipe() {
            this.saving = true;
            this.errors = {};

            const payload = this.buildPayload();
            const response = this.isUpdate
                ? await Requests.patch({route: CONFIG.routes.update, id: this.form.id, data: payload})
                : await Requests.post({route: CONFIG.routes.store, data: payload});

            this.saving = false;

            if(!response.bool || response.data?.bool === false) {
                this.errors = this.normalizeErrors(response.data?.errors || {});
                Alerts.toastrs({type: "error", subtitle: response.data?.msg || "Revisa la información ingresada."});
                return;
            }

            Alerts.toastrs({type: "success", subtitle: response.data?.msg || "Registro guardado correctamente."});
            bootstrap.Modal.getInstance(document.getElementById("recipeModal"))?.hide();
            this.listRecipes();
        }
    }
};
</script>
