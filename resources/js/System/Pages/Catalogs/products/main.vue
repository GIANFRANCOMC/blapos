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
            :filter-by-title="MODULE.texts.filters.filterBy"
            :search-title="MODULE.texts.filters.search"
            :search-button-text="MODULE.texts.actions.search"
            :add-button-text="MODULE.texts.actions.add"
            :show-add-button="true"
            :show-download-button="MODULE.config.hasDownloadRecords"
            :show-import-button="MODULE.config.hasImportRecords"
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

        <ProductImportModal
            ref="productImportModal"
            :title="MODULE.texts.actions.import"
            :import-route="routeActions.import"
            :warehouses="warehouses"
            @imported="handleProductsImported"/>

        <ProductInventoryModal ref="productInventoryModal"/>

        <section class="br-entity-list" :aria-label="MODULE.texts.table.ariaLabel">
            <div class="table-responsive">
                <table class="table br-entity-table mb-0">
                    <colgroup>
                        <col class="br-entity-table__col-product">
                        <col class="br-entity-table__col-identification">
                        <col class="br-entity-table__col-price">
                        <col class="br-entity-table__col-inventory">
                        <col class="br-entity-table__col-status">
                        <col class="br-entity-table__col-actions">
                    </colgroup>
                    <thead class="br-table-header-surface">
                        <tr>
                            <th class="text-center">Producto</th>
                            <th class="text-center">Identificación</th>
                            <th class="text-end">Precio</th>
                            <th>Inventario</th>
                            <th class="text-center">Estado</th>
                            <th class="text-center">
                                <span class="visually-hidden">Acciones</span>
                            </th>
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
                                <td>
                                    <span class="br-entity-table__name" v-text="record.name"></span>
                                    <span v-if="record.brand" class="br-entity-table__attribute">
                                        <span
                                            class="br-entity-table__attribute-icon"
                                            data-bs-toggle="tooltip"
                                            data-bs-placement="top"
                                            title="Marca"
                                            aria-label="Marca"
                                            tabindex="0">
                                            <i class="fa-solid fa-tag" aria-hidden="true"></i>
                                        </span>
                                        <strong v-text="record.brand.name"></strong>
                                    </span>
                                    <span v-if="record.description" class="br-entity-table__description" v-text="record.description"></span>
                                </td>
                                <td class="br-entity-table__identification-cell">
                                    <div class="br-entity-identifiers br-entity-identifiers--plain">
                                        <div class="br-entity-identifier">
                                            <span class="br-entity-identifier__label colon-at-end">Cód. interno</span>
                                            <span class="br-entity-identifier__value">
                                                <span
                                                    class="br-entity-code"
                                                    v-text="record.internal_code"></span>
                                                <span class="br-entity-identifier__actions">
                                                    <CopyButton
                                                        :value="record.internal_code"
                                                        label="Código interno"/>
                                                </span>
                                            </span>
                                        </div>
                                        <div class="br-entity-identifier">
                                            <span class="br-entity-identifier__label colon-at-end">Cód. barras</span>
                                            <span class="br-entity-identifier__value">
                                                <span
                                                    class="br-entity-barcode"
                                                    v-text="record.barcode"></span>
                                                <span class="br-entity-identifier__actions">
                                                    <CopyButton
                                                        :value="record.barcode"
                                                        label="Código de barras"/>
                                                </span>
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-end">
                                    <div class="br-entity-prices">
                                        <span class="br-entity-price-row is-sale">
                                            <span>Venta</span>
                                            <strong>{{ record.currency?.sign }} {{ separatorNumber(record.price) }}</strong>
                                        </span>
                                        <span
                                            v-if="isDefined(record.min_price)"
                                            class="br-entity-price-row is-minimum">
                                            <span>Mín.</span>
                                            <strong>{{ record.currency?.sign }} {{ separatorNumber(record.min_price) }}</strong>
                                        </span>
                                        <span
                                            v-if="isDefined(record.max_price)"
                                            class="br-entity-price-row is-maximum">
                                            <span>Máx.</span>
                                            <strong>{{ record.currency?.sign }} {{ separatorNumber(record.max_price) }}</strong>
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <span class="br-entity-stock-row">
                                        <span class="br-entity-stock">
                                            {{ separatorNumber(stockSummary(record).total) }} unidades
                                        </span>
                                        <button
                                            type="button"
                                            :class="['br-entity-stock-trigger', {'is-alert': stockSummary(record).alerts > 0}]"
                                            data-bs-toggle="tooltip"
                                            data-bs-placement="top"
                                            :title="stockInventoryActionText(record)"
                                            :aria-label="stockInventoryActionText(record)"
                                            @click="openInventoryModal(record)">
                                            <i :class="stockSummary(record).alerts > 0 ? 'fa-solid fa-triangle-exclamation' : 'fa-solid fa-box-open'" aria-hidden="true"></i>
                                        </button>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <StatusBadge
                                        class="flex-shrink-none"
                                        :status="record.status"
                                        :formatted-status="record.formatted_status"/>
                                </td>
                                <td class="text-center">
                                    <TableActionsDropdown :label="`Acciones de ${record.name}`">
                                        <li>
                                            <button type="button" class="dropdown-item" @click="openModal(record)">
                                                <i class="fa-solid fa-pen" aria-hidden="true"></i>
                                                <span v-text="MODULE.texts.actions.edit"></span>
                                            </button>
                                        </li>
                                        <li>
                                            <BarcodeDownloadButton
                                                menu-item
                                                tooltip="Descargar cód. barras"
                                                :value="record.barcode"
                                                :file-name="record.internal_code"/>
                                        </li>
                                    </TableActionsDropdown>
                                </td>
                            </tr>
                        </template>
                        <tr v-else>
                            <td colspan="6" class="py-4">
                                <WithoutData
                                    class="br-entity-empty-state"
                                    type="image"
                                    image-src="/System/assets/img/utils/without_data/empty_products.png?v=2"/>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <nav
            v-if="!entityList.extras.loading && entityList.records.total > 0"
            class="d-flex justify-content-center mt-3"
            aria-label="Paginación de productos">
            <Paginator :links="entityList.records.links" @clickPage="listEntity"/>
        </nav>
    </main>

    <AddProduct ref="productEditor" :show-trigger="false" :initial-options="options" @saved="listEntity({})"/>
</template>

<script>
import * as Alerts from "@System/Helpers/Alerts.js";
import {initCrudModule} from "@System/Helpers/ModuleFactory.js";
import * as Requests from "@System/Helpers/Requests.js";
import * as Utils from "@System/Helpers/Utils.js";
import BarcodeDownloadButton from "@System/Components/BarcodeDownloadButton.vue";
import ProductImportModal from "@System/Components/Catalogs/Products/ProductImportModal.vue";
import ProductInventoryModal from "@System/Components/Catalogs/Products/ProductInventoryModal.vue";
import AddProduct from "@System/Components/Catalogs/Products/AddProduct.vue";
import TableActionsDropdown from "@System/Components/Generics/TableActionsDropdown.vue";
import {PRODUCT_TEXTS} from "@System/Components/Catalogs/Products/productFormConfig.js";
import {summarizeProductStock} from "@System/Components/Catalogs/Products/productInventory.js";

const MODULE_CONFIG = {
    entity: "products",
    menuId: "menu-items-products",
    pageTitle: "Productos",
    pageTitleSingular: "Producto",
    breadcrumbParent: "Catálogo comercial",
    perPage: 10,
    hasDownloadRecords: true,
    hasImportRecords: true
};

const FILTER_OPTIONS = [
    {code: "all", label: "Todos los filtros"},
    {code: "internal_code", label: "Código interno"},
    {code: "barcode", label: "Código de barras"},
    {code: "name", label: "Nombre"},
    {code: "brand", label: "Marca"},
    {code: "description", label: "Descripción comercial adicional"},
    {code: "price", label: "Precio de venta"}
];

const MODULE = {
    config: MODULE_CONFIG,
    texts: PRODUCT_TEXTS,
    filterOptions: FILTER_OPTIONS
};

export default {
    name: "ProductsMain",
    components: {
        BarcodeDownloadButton,
        ProductImportModal,
        ProductInventoryModal,
        AddProduct,
        TableActionsDropdown
    },
    data() {

        const crudModule = initCrudModule({
            entity: MODULE.config.entity,
            menuId: MODULE.config.menuId,
            pageTitle: MODULE.config.pageTitle,
            pageTitleSingular: MODULE.config.pageTitleSingular
        });

        crudModule.lists[MODULE.config.entity].filters.filter_by = MODULE.filterOptions[0];

        return {
            ...crudModule,
            MODULE,
            isExporting: false
        };

    },
    async mounted() {

        Utils.navbarItem("menu-parent-items", {addClass: "open"});
        Utils.navbarItem(this.config.entity.page.menu.id, {});

        Alerts.swals({type: "initParams"});

        const initParams = await this.initParams();

        if(initParams) {

            Alerts.swals({show: false});
            await this.listEntity({});

        }

    },
    beforeUnmount() {

        Alerts.tooltips({show: false});

    },
    methods: {
        async initParams() {

            const response = await Requests.get({
                route: this.routeActions.initParams,
                data: {page: "main"},
                showAlert: true
            });

            if(response?.data?.config) {

                this.options.brands     = response.data.config.brands;
                this.options.categories = response.data.config.categories;
                this.options.currencies = response.data.config.currencies;
                this.options.statuses   = response.data.config.statuses;
                this.options.warehouses = response.data.config.warehouses;
                this.options.internal_code_prefixes = response.data.config.internal_code_prefixes ?? {};

            }

            return Requests.valid({result: response});

        },
        async listEntity(params = null) {

            const emptyRecords = {total: 0, data: [], links: []};
            const filterData = this.getListFilters({includePagination: true});

            this.entityList.extras.loading = true;

            try {

                const url = this.isDefined(params) && typeof params === "object" ? params.url : params;
                let requestUrl = url || this.entityList.extras.route;
                let requestData = {};

                if(this.isDefined(url)) {

                    const urlObject = new URL(url, window.location.origin);

                    Object.entries(filterData).forEach(([key, value]) => {

                        if(this.isDefined(value) && !urlObject.searchParams.has(key)) urlObject.searchParams.set(key, value);

                    });

                    requestUrl = `${urlObject.pathname}${urlObject.search}`;

                }else {

                    requestData = filterData;

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
        handleSearch() {

            this.listEntity({});

        },
        getListFilters({includePagination = false} = {}) {

            const filters = Utils.cloneJson(this.entityList.filters);
            const filterData = {
                filter_by: filters.filter_by?.code,
                word: filters.word
            };

            if(includePagination) {

                filterData.per_page = this.MODULE.config.perPage;

            }

            return filterData;

        },
        async downloadRecords() {

            if(this.isExporting) return;

            const confirmed = await Alerts.confirmDownload({resource: "el listado de productos"});

            if(!confirmed) return;

            this.isExporting = true;
            Alerts.swals({
                type: "default",
                title: "Preparando reporte de productos"
            });

            try {

                await Requests.download({
                    route: this.routeActions.export,
                    data: this.getListFilters(),
                    fileName: "productos.xlsx",
                    showAlert: true
                });

            }finally {

                Alerts.swals({show: false});
                this.isExporting = false;

            }

        },
        openImportModal() {

            Alerts.tooltips({show: false});
            this.$refs.productImportModal?.open();

        },
        async handleProductsImported() {

            await this.listEntity({});

        },
        openModal(record = null) {

            this.$refs.productEditor?.open(record);

        },
        openInventoryModal(record) {

            this.$refs.productInventoryModal?.open(record);

        },
        stockSummary(record) {

            return summarizeProductStock(record);

        },
        stockInventoryActionText(record) {

            const count = this.stockSummary(record).alerts;

            return count > 0
                ? `Ver inventario · ${count} ${count === 1 ? "almacén con stock bajo" : "almacenes con stock bajo"}`
                : "Ver inventario por almacén";

        },
        isDefined(value) {

            return Utils.isDefined({value});

        },
        separatorNumber(value) {

            return Utils.separatorNumber(value);

        }
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
        warehouses() {

            return this.options?.warehouses?.records ?? [];

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

            return filterBy ? `Buscar por ${(filterBy.label || "...").toLowerCase()}` : "Buscar productos";

        },
    }
};
</script>
