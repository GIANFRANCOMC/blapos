<template>
    <main class="fg-products">
        <header class="fg-page-header">
            <div><h1 class="fg-title">Productos</h1><p class="fg-help">Administra tu catálogo y consulta el stock por almacén.</p></div>
            <div class="fg-page-header__actions">
                <FgButton icon="fa-solid fa-plus" :disabled="initialLoading" @click="openModal()">Agregar producto</FgButton>
                <ActionsDropdown type="button" text="Acciones" label="Acciones de productos" :disabled="initialLoading">
                    <li><button type="button" class="fg-menu__item" @click="openImportModal">Importar productos</button></li>
                    <li><button type="button" class="fg-menu__item" :disabled="isExporting" @click="downloadRecords">Exportar productos</button></li>
                </ActionsDropdown>
            </div>
        </header>
        <InitialLoadTransition>
            <CatalogListSkeleton v-if="initialLoading" kind="products" label="productos"/>
            <div v-else>
                <form class="fg-filters" role="search" aria-label="Buscar productos" @submit.prevent="handleSearch">
                    <FgField label="Filtrar por" input-id="fg-products-filter">
                        <FgSelect input-id="fg-products-filter" v-model="filterByValue" :options="filterByOptions" class="fg-select" :clearable="false" :searchable="false"/>
                    </FgField>
                    <FgField label="Buscar" input-id="fg-products-search">
                        <div class="fg-field__control"><input id="fg-products-search" v-model="filterWordValue" class="fg-input" :placeholder="searchPlaceholder" type="search" autocomplete="off"/></div>
                    </FgField>
                    <FgFilterActions submit :loading="entityList.extras.loading" @clear="clearProductFilters"/>
                </form>
                <div class="fg-list-heading">
                    <h2 class="fg-section-title">{{ entityList.appliedFilters?.word?.trim() ? 'Resultados de «' + entityList.appliedFilters.word.trim() + '»' : 'Productos registrados' }}</h2>
                    <FgField class="fg-list-order" label="Ordenado por" input-id="fg-products-order"><FgSelect input-id="fg-products-order" :model-value="sortDirection" :options="sortOptions" :reduce="option => option.code" :clearable="false" :searchable="false" :disabled="entityList.extras.loading" @update:modelValue="changeSort"/></FgField>
                </div>
                <section class="fg-products-list" aria-label="Listado de productos">
                    <div class="fg-table-scroll" role="region" aria-label="Tabla de productos" tabindex="0">
                        <table class="fg-table fg-products-table">
                            <colgroup><col class="fg-products-table__identity"/><col class="fg-products-table__product"/><col class="fg-products-table__price"/><col class="fg-products-table__status"/><col class="fg-products-table__actions"/></colgroup>
                            <thead><tr><th scope="col">Identificación</th><th scope="col">Producto</th><th scope="col" class="fg-align-end">Precio</th><th scope="col" class="fg-align-center">Estado</th><th scope="col" class="fg-align-center">Acciones</th></tr></thead>
                            <tbody>
                                <tr v-if="entityList.extras.loading" class="fg-table__state"><td colspan="5"><FgListLoading label="Buscando productos…"/></td></tr>
                                <template v-else-if="entityList.records.total > 0">
                                    <tr v-for="record in entityList.records.data" :key="record.id">
                                        <td><div class="fg-product-codes"><span>{{ record.internal_code }}</span><small v-if="record.barcode">{{ record.barcode }}</small></div></td>
                                        <td><div class="fg-product-cell">
                                            <button type="button" :class="['fg-stock-trigger', {'fg-stock-trigger--warning': stockByProduct[record.id].code !== 'healthy'}]" :title="stockInventoryActionText(record)" :aria-label="stockInventoryActionText(record)" @click="openInventoryModal(record)"><i class="fa-solid fa-box" aria-hidden="true"/></button>
                                            <div class="fg-product-cell__copy"><span class="fg-product-name">{{ record.name }}</span><ExpandableText v-if="record.description" :text="record.description"/></div>
                                        </div></td>
                                        <td class="fg-align-end"><CatalogPriceCell :record="record"/></td>
                                        <td class="fg-align-center"><StatusBadge :status="record.status" :formatted-status="record.formatted_status"/></td>
                                        <td class="fg-align-center"><TableActionsDropdown :label="'Acciones de ' + record.name">
                                            <li><button type="button" class="fg-menu__item" @click="openModal(record)">Editar producto</button></li>
                                            <li v-if="record.barcode"><BarcodeDownloadButton menu-item tooltip="Descargar código de barras" :value="record.barcode" :file-name="record.internal_code"/></li>
                                        </TableActionsDropdown></td>
                                    </tr>
                                </template>
                                <tr v-else class="fg-table__state"><td colspan="5"><FgEmptyState :title="listFailed ? 'No se pudo cargar el listado' : entityList.appliedFilters?.word?.trim() ? 'No encontramos productos con esta búsqueda' : 'Aún no hay productos registrados'" :description="listFailed ? 'Vuelve a intentar la consulta.' : entityList.appliedFilters?.word?.trim() ? 'Prueba con otro término o limpia los filtros.' : 'Agrega un producto o importa un archivo para empezar.'">
                                    <div class="fg-empty__actions"><FgButton v-if="listFailed" @click="listEntity({})">Volver a intentar</FgButton><FgButton v-else-if="entityList.appliedFilters?.word?.trim()" tone="secondary" @click="clearProductFilters">Limpiar filtros</FgButton><template v-else><FgButton icon="fa-solid fa-plus" @click="openModal()">Agregar producto</FgButton><FgButton tone="secondary" @click="openImportModal">Importar productos</FgButton></template></div>
                                </FgEmptyState></td></tr>
                            </tbody>
                        </table>
                    </div>
                </section>
                <Paginator singular="producto" plural="productos" aria-label="Paginación de productos" :links="entityList.records.links" :pagination="entityList.records" :loading="entityList.extras.loading" @clickPage="listEntity"/>
            </div>
        </InitialLoadTransition>
        <ProductImportModal ref="productImportModal" :import-route="routeActions.import" :internal-code-prefix="options.internal_code_prefixes?.product" :warehouses="warehouses" @imported="handleProductsImported"/>
        <ProductInventoryModal ref="productInventoryModal"/>
        <AddProduct ref="productEditor" :show-trigger="false" :initial-options="options" @saved="listEntity({})"/>
    </main>
</template>
<script>
import Paginator from "@System/Components/Paginator.vue";
import StatusBadge from "@System/Components/Generics/StatusBadge.vue";
import FgListLoading from "@System/Components/Figma/FgListLoading.vue";
import FgSelect from "@System/Components/Figma/FgSelect.vue";
import FgButton from "@System/Components/Figma/FgButton.vue";
import FgFilterActions from "@System/Components/Figma/FgFilterActions.vue";
import FgField from "@System/Components/Figma/FgField.vue";
import FgEmptyState from "@System/Components/Figma/FgEmptyState.vue";
import ActionsDropdown from "@System/Components/Generics/ActionsDropdown.vue";

import {downloadCatalogList} from "@System/Components/Catalogs/downloadCatalogList.js";
import CatalogPriceCell from "@System/Components/Catalogs/CatalogPriceCell.vue";
import {navbarItem} from "@System/Layout/Menu.js";
import ExpandableText from "@System/Components/Generics/ExpandableText.vue";
import InitialLoadTransition from "@System/Components/Generics/InitialLoadTransition.vue";
import CatalogListSkeleton from "@System/Components/Catalogs/CatalogListSkeleton.vue";
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
import * as StockStatus from "../../../../Shared/StockStatus.js";


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
    {code: "description", label: "Descripción comercial"},
    {code: "price", label: "Precio de venta"}
];

const MODULE = {
    config: MODULE_CONFIG,
    texts: PRODUCT_TEXTS,
    filterOptions: FILTER_OPTIONS
};

export default {
    name: "ProductsMain",
    components: {FgListLoading, Paginator, StatusBadge,FgSelect,FgButton, FgField, FgFilterActions, FgEmptyState, ActionsDropdown,
        CatalogPriceCell,
        ExpandableText,
        InitialLoadTransition,
        CatalogListSkeleton,
        BarcodeDownloadButton,
        ProductImportModal,
        ProductInventoryModal,
        AddProduct,
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

        return {
            ...crudModule,
            MODULE,
            isExporting: false,
            initialLoading: true,
            sortDirection: "asc",
            sortOptions: [{code: "asc", label: "Nombre A–Z"}, {code: "desc", label: "Nombre Z–A"}],
            listFailed: false
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
        else this.listFailed = true;
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
                this.options.timezone = response.data.config.timezone;
                this.options.warehouses = response.data.config.warehouses;
                this.options.internal_code_prefixes = response.data.config.internal_code_prefixes ?? {};

            }

            return Requests.valid({result: response});

        },
        async listEntity(params = null) {

            const emptyRecords = {total: 0, data: [], links: []};
            const filterData = this.getListFilters({includePagination: true});

            this.entityList.extras.loading = true;
            this.listFailed = false;
            this.entityList.records = Requests.emptyPagination();

            try {

                const url = this.isDefined(params) && typeof params === "object" ? params.url : params;
                let requestUrl = url || this.entityList.extras.route;
                let requestData = {};

                if(this.isDefined(url)) {

                    const urlObject = new URL(url, window.location.origin);

                    Object.entries(filterData).forEach(([key, value]) => {

                        if(value != null) urlObject.searchParams.set(key, value);
                        else urlObject.searchParams.delete(key);

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
        changeSort(direction) {
            if(!["asc", "desc"].includes(direction) || direction === this.sortDirection) return;
            this.sortDirection = direction;
            return this.listEntity({});
        },
        clearProductFilters() {
            this.entityList.filters.filter_by = this.MODULE.filterOptions[0];
            this.entityList.filters.word = "";
            return this.handleSearch();
        },
        handleSearch() {
            this.entityList.appliedFilters = {...this.entityList.filters};
            return this.listEntity({});
        },
        getListFilters({includePagination = false} = {}) {

            const filters = (this.entityList.appliedFilters ??= {...this.entityList.filters});
            const filterData = {
                filter_by: filters.filter_by?.code,
                word: filters.word,
                sort_direction: this.sortDirection
            };

            if(includePagination) {

                filterData.per_page = this.MODULE.config.perPage;

            }

            return filterData;

        },
        async downloadRecords() {
            if(this.isExporting) return;
            this.isExporting = true;
            try {
                await downloadCatalogList({
                    resource: "productos",
                    route: this.routeActions.export,
                    filters: this.getListFilters(),
                    fileName: "productos.xlsx",
                    total: this.entityList.extras.loading || this.listFailed ? null : this.entityList.records.total
                });
            } finally {
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
        stockInventoryActionText(record) {

            const {code, alerts} = this.stockByProduct[record.id];
            const status = StockStatus.labels[code];

            return alerts > 0
                ? `Ver inventario · ${status} · ${alerts} ${alerts === 1 ? "almacén con alerta" : "almacenes con alerta"}`
                : `Ver inventario · ${status}`;

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
        stockByProduct() {

            return Object.fromEntries((this.entityList.records.data ?? []).map(record => {
                const {total, alerts} = summarizeProductStock(record);
                return [record.id, {code: StockStatus.forProduct(total, alerts > 0), alerts}];
            }));

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
