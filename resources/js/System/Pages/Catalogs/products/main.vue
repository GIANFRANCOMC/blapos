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
            :show-labels-button="true"
            :group-secondary-actions="true"
            :import-button-text="MODULE.texts.actions.import"
            :import-button-tooltip="MODULE.texts.actions.import"
            :download-button-text="MODULE.texts.actions.download"
            :download-button-tooltip="MODULE.texts.actions.download"
            labels-button-text="Etiquetas"
            labels-button-tooltip="Imprimir etiquetas"
            :download-icon-only-on-desktop="true"
            :downloading="isExporting"
            :title-class="[config.forms.classes.title]"
            :select-class="config.forms.classes.select2"
            @search="handleSearch"
            @add="openModal()"
            @import="openImportModal"
            @download="downloadRecords"
            @labels="printVisibleLabels"/>

        <ProductImportModal
            ref="productImportModal"
            :import-route="routeActions.import"
            :template-route="routeActions.importTemplate"
            :warehouses="warehouses"
            @imported="handleProductsImported"/>

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
                                <td>
                                    <div class="br-entity-identifiers">
                                        <div class="br-entity-identifier">
                                            <span
                                                class="br-entity-identifier__label"
                                                data-bs-toggle="tooltip"
                                                data-bs-placement="top"
                                                title="Código interno">Cód. interno</span>
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
                                            <span
                                                class="br-entity-identifier__label"
                                                data-bs-toggle="tooltip"
                                                data-bs-placement="top"
                                                title="Código de barras">Cód. barras</span>
                                            <span class="br-entity-identifier__value">
                                                <span
                                                    class="br-entity-barcode"
                                                    v-text="record.barcode"></span>
                                                <span class="br-entity-identifier__actions">
                                                    <CopyButton
                                                        :value="record.barcode"
                                                        label="Código de barras"/>
                                                    <BarcodeDownloadButton
                                                        :value="record.barcode"
                                                        :file-name="record.internal_code"/>
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
                                    <span class="br-entity-stock">
                                        {{ separatorNumber(stockSummary(record).total) }} unidades
                                    </span>
                                    <span
                                        v-if="stockSummary(record).alerts > 0"
                                        class="br-entity-stock-alert">
                                        <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
                                        <span>
                                            {{ stockSummary(record).alerts }}
                                            {{ stockSummary(record).alerts === 1 ? "almacén con stock bajo" : "almacenes con stock bajo" }}
                                        </span>
                                    </span>
                                    <span v-else class="br-entity-stock-healthy">
                                        <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                                        <span>
                                            Stock saludable en
                                            {{ stockSummary(record).warehouses }}
                                            {{ stockSummary(record).warehouses === 1 ? "almacén" : "almacenes" }}
                                        </span>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <StatusBadge
                                        class="flex-shrink-none"
                                        :status="record.status"
                                        :formatted-status="record.formatted_status"/>
                                </td>
                                <td class="text-center">
                                    <button
                                        type="button"
                                        class="br-icon-action br-icon-action-edit"
                                        data-bs-toggle="tooltip"
                                        data-bs-placement="top"
                                        :title="MODULE.texts.actions.edit"
                                        :aria-label="`${MODULE.texts.actions.edit} ${record.name}`"
                                        @click="openModal(record)">
                                        <i class="fa-solid fa-pen" aria-hidden="true"></i>
                                    </button>
                                </td>
                            </tr>
                        </template>
                        <tr v-else>
                            <td colspan="6" class="py-4">
                                <WithoutData
                                    class="br-products-empty-state"
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
import AddProduct from "@System/Components/Catalogs/Products/AddProduct.vue";
import {PRODUCT_TEXTS, isValidEan13} from "@System/Components/Catalogs/Products/productFormConfig.js";
import JsBarcode from "jsbarcode";

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
        AddProduct
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
        printVisibleLabels() {

            const records = (this.entityList.records?.data || [])
                .filter(record => this.isDefined(record.barcode) && isValidEan13(record.barcode));

            if(records.length === 0) {

                Alerts.toastrs({
                    type: "warning",
                    subtitle: "No hay productos con código de barras válido en el listado actual."
                });
                return;

            }

            const labelMarkup = records.map(record => this.buildBarcodeLabelMarkup(record)).join("");
            const printWindow = window.open("", "_blank", "width=920,height=720");

            if(!printWindow) {

                Alerts.toastrs({
                    type: "warning",
                    subtitle: "Permite ventanas emergentes para imprimir etiquetas."
                });
                return;

            }

            printWindow.document.write(this.buildLabelsPrintDocument(labelMarkup));
            printWindow.document.close();
            printWindow.focus();
            printWindow.print();

        },
        buildBarcodeLabelMarkup(record) {

            const canvas = document.createElement("canvas");

            JsBarcode(canvas, String(record.barcode), {
                format: "EAN13",
                width: 2,
                height: 70,
                displayValue: true,
                font: "Arial",
                fontSize: 14,
                fontOptions: "bold",
                textMargin: 5,
                margin: 4,
                background: "rgba(255, 255, 255, 0)",
                lineColor: "#000000"
            });

            return `
                <article class="label">
                    <strong>${this.escapeHtml(record.name)}</strong>
                    <span>${this.escapeHtml(record.internal_code || "")}</span>
                    <img src="${canvas.toDataURL("image/png")}" alt="Código de barras ${this.escapeHtml(record.barcode)}">
                </article>
            `;

        },
        buildLabelsPrintDocument(labelMarkup) {

            return `
                <!doctype html>
                <html lang="es">
                <head>
                    <meta charset="utf-8">
                    <title>Etiquetas de productos</title>
                    <style>
                        @page { size: A4; margin: 10mm; }
                        * { box-sizing: border-box; }
                        body { margin: 0; font-family: Arial, sans-serif; color: #1a1a35; }
                        .sheet { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8mm; }
                        .label { min-height: 34mm; padding: 4mm; border: 1px dashed #9a9ab0; border-radius: 4px; break-inside: avoid; text-align: center; }
                        .label strong { display: block; font-size: 11px; line-height: 1.2; }
                        .label span { display: block; margin-top: 1mm; font-size: 9px; color: #5d6682; }
                        .label img { width: 100%; max-height: 22mm; object-fit: contain; margin-top: 2mm; }
                    </style>
                </head>
                <body><main class="sheet">${labelMarkup}</main></body>
                </html>
            `;

        },
        escapeHtml(value) {

            return String(value ?? "")
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");

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
        stockSummary(record) {

            const warehouseItems = record?.warehouse_items ?? [];

            return {
                total: warehouseItems.reduce((total, item) => total + Number(item.quantity ?? 0), 0),
                alerts: warehouseItems.filter(item => Number(item.quantity ?? 0) <= Number(item.minimum_stock ?? 0)).length,
                warehouses: warehouseItems.length
            };

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
