
import {navbarItem} from "@System/Layout/Menu.js";
import InitialLoadTransition from "@System/Components/Generics/InitialLoadTransition.vue";
import * as Alerts from "@System/Helpers/Alerts.js";
import * as Constants from "@System/Helpers/Constants.js";
import * as Requests from "@System/Helpers/Requests.js";
import * as Utils from "@System/Helpers/Utils.js";
import * as Numbers from "../../../../Shared/Numbers.js";
import * as StockStatus from "../../../../Shared/StockStatus.js";
import SearchButton from "@/Shared/SearchButton.vue";
import ClearFiltersButton from "@System/Components/Generics/ClearFiltersButton.vue";
import FormModal from "@System/Components/Generics/FormModal.vue";
import ProductIdentity from "@System/Components/Generics/ProductIdentity.vue";
import ProductSelect from "@System/Components/Generics/ProductSelect.vue";
import QuickCreateTrigger from "@System/Components/Generics/QuickCreateTrigger.vue";
import QuantityValue from "@System/Components/Generics/QuantityValue.vue";
import SearchInput from "@System/Components/Generics/SearchInput.vue";
import TemplateFilePicker from "@System/Components/Generics/TemplateFilePicker.vue";
import WarehouseSelect from "@System/Components/Generics/WarehouseSelect.vue";
import InventorySkeleton from "./InventorySkeleton.vue";
import KardexMovementDetails from "./KardexMovementDetails.vue";
import StockAdvancedFilters from "./StockAdvancedFilters.vue";
import StockAlertsModal from "./StockAlertsModal.vue";
import StockDifference from "./StockDifference.vue";
import StockItemEditor from "./StockItemEditor.vue";
import StockTraceabilityModal from "./StockTraceabilityModal.vue";
import TableActionsDropdown from "@System/Components/Generics/TableActionsDropdown.vue";

const STOCK_PAGE_SIZE = 10;
const DEFAULT_TRANSFER_STATUS = "accepted";
const ALL_TRANSFER_STATUSES = {code: "", label: "Todos los estados"};
const ALL_MOVEMENT_USERS = {code: "", label: "Todos los usuarios"};

const TRANSFER_REASON_OPTIONS = [
    {label: "Reposición de stock", text: "Reponer existencias en el almacén de destino para mantener la disponibilidad de los productos."},
    {label: "Atención de un pedido", text: "Abastecer el almacén de destino con los productos necesarios para preparar un pedido de cliente."},
    {label: "Redistribución de excedentes", text: "Trasladar el excedente del almacén de origen a un almacén con mayor demanda."},
    {label: "Stock agotado", text: "Restablecer la disponibilidad de productos agotados en el almacén de destino."},
    {label: "Campaña o temporada", text: "Abastecer el almacén de destino con anticipación para cubrir la demanda prevista de una campaña o temporada."},
    {label: "Apertura de sucursal", text: "Enviar el inventario inicial al almacén de una nueva sucursal para el inicio de operaciones."},
    {label: "Consolidación de inventario", text: "Concentrar existencias en un solo almacén para facilitar el almacenamiento y el despacho."},
    {label: "Liberación de espacio", text: "Reubicar productos para liberar espacio en el almacén de origen y facilitar sus operaciones."},
    {label: "Control de calidad", text: "Enviar productos al almacén habilitado para su inspección y control de calidad."},
    {label: "Productos próximos a vencer", text: "Trasladar productos próximos a vencer a un almacén con mayor rotación para priorizar su salida."}
];

const VIEWS = [
    {code: "stock", title: "Control de stock"},
    {code: "kardex", title: "Kardex"},
    {code: "transfers", title: "Traslados"},
    {code: "valued", title: "Kardex valorizado"},
    {code: "guides", title: "Guías"}
];

const VIEW_MENU_MAP = {
    stock: "menu-inventory-stock",
    kardex: "menu-inventory-kardex",
    transfers: "menu-inventory-transfers",
    valued: "menu-inventory-valued",
    guides: "menu-inventory-guides"
};

const MOVEMENT_TYPES = [
    {code: "", label: "Todos los movimientos"},
    {code: "entry", label: "Entrada"},
    {code: "exit", label: "Salida"},
    {code: "correction", label: "Corrección"}
];

const GUIDE_TYPES = [
    {code: "", label: "Todas las guías"},
    {code: "entry", label: "Entrada"},
    {code: "exit", label: "Salida"}
];

const ALL_STOCK_STATUSES = {code: "", label: "Todos los estados"};

const STOCK_MINIMUM_OPTIONS = [
    {code: "", label: "Todos los productos"},
    {code: "configured", label: "Con mínimo configurado"},
    {code: "unconfigured", label: "Sin mínimo configurado"}
];

const STOCK_INACTIVITY_OPTIONS = [
    {code: "", label: "Cualquier fecha"},
    {code: "30", label: "Sin movimientos en 30 días"},
    {code: "60", label: "Sin movimientos en 60 días"},
    {code: "90", label: "Sin movimientos en 90 días"}
];

function createInventoryFilters(warehouse = null) {
    return {
        warehouse,
        productSearch: "",
        stockSituation: ALL_STOCK_STATUSES,
        stockCategory: null,
        stockBrand: null,
        stockMinimumStatus: STOCK_MINIMUM_OPTIONS[0],
        stockInactiveDays: STOCK_INACTIVITY_OPTIONS[0],
        movementType: MOVEMENT_TYPES[0],
        movementUser: ALL_MOVEMENT_USERS,
        guideType: GUIDE_TYPES[0],
        transferStatus: ALL_TRANSFER_STATUSES,
        sourceWarehouse: null,
        destinationWarehouse: null,
        transferReference: "",
        dateFrom: "",
        dateTo: ""
    };
}

const INVENTORY_OPERATIONS = [
    {code: "manual_entry", label: "Entrada manual", amountLabel: "Cantidad de entrada", movementType: "entry", originType: "manual", description: "Suma las unidades indicadas."},
    {code: "manual_exit", label: "Salida manual", amountLabel: "Cantidad de salida", movementType: "exit", originType: "manual", description: "Resta las unidades indicadas."},
    {code: "physical_count", label: "Toma física", amountLabel: "Saldo contado", movementType: "correction", originType: "physical_count", description: "Usa el saldo contado."},
    {code: "customer_return", label: "Devolución de cliente", amountLabel: "Cantidad devuelta", movementType: "entry", originType: "customer_return", description: "Devuelve unidades al stock."},
    {code: "supplier_return", label: "Devolución a proveedor", amountLabel: "Cantidad al proveedor", movementType: "exit", originType: "supplier_return", description: "Resta unidades devueltas."}
];

const INVENTORY_OPERATION_GROUPS = [
    {code: "correction", label: "Actualización", effect: "Actualiza el saldo"},
    {code: "entry", label: "Ingresos", effect: "Aumenta el stock"},
    {code: "exit", label: "Salidas", effect: "Disminuye el stock"}
];

const INVENTORY_OPERATION_OPTIONS = INVENTORY_OPERATION_GROUPS.flatMap(group => [
    {code: `group-${group.code}`, label: group.label, isGroup: true},
    ...INVENTORY_OPERATIONS.filter(operation => operation.movementType === group.code)
]);

export default {
    components: {InitialLoadTransition, ClearFiltersButton, FormModal, ProductIdentity, ProductSelect, QuickCreateTrigger, QuantityValue, SearchButton, SearchInput, TemplateFilePicker, WarehouseSelect, InventorySkeleton, KardexMovementDetails, StockAdvancedFilters, StockAlertsModal, StockDifference, StockItemEditor, StockTraceabilityModal, TableActionsDropdown},
    props: {
        page: {
            type: String,
            required: true,
            validator: value => VIEWS.some(view => view.code === value)
        }
    },
    async mounted() {
        navbarItem("menu-parent-items", {addClass: "open"});
        navbarItem(this.activeMenuId(), {});
        let initialized;
        try {
            initialized = await this.initParams();
        } finally {
            this.initialLoading = false;
        }

        if(initialized && this.activeView === "transfers") {
            await this.listCurrentView({});
        }else if(initialized && this.realWarehouses.length) {
            this.filters.warehouse = this.realWarehouses[0];
            this.appliedFilters = {...this.filters};
            await this.listCurrentView({});
        }
    },
    beforeUnmount() {
        this.traceabilityRequest += 1;
        this.transferAvailabilityRequest += 1;
        if(this.loadingTraceability) Alerts.close();
    },
    data() {
        return {
            activeView: this.page,
            initialLoading: true,
            loadingStock: false,
            loadingKardex: false,
            loadingTransfers: false,
            loadingAlerts: false,
            canUpdateStock: false,
            loadingTraceability: false,
            traceabilityProduct: null,
            traceabilityWarehouse: null,
            traceabilityRecords: {data: [], total: 0, links: []},
            traceabilityError: "",
            traceabilityRequest: 0,
            downloadingAlerts: false,
            stockAlertsError: "",
            alertProductSearch: "",
            appliedAlertProductSearch: "",
            alertMinimumStatus: STOCK_MINIMUM_OPTIONS[0],
            appliedAlertMinimumStatus: "",
            loadingGuides: false,
            requestVersion: 0,
            savingMovement: false,
            confirmingMovement: false,
            previewingOperation: false,
            downloadingOperation: false,
            operationFileName: "",
            operationPreviewVersion: 0,
            savingTransfer: false,
            canOperateTransfers: false,
            focusTransferReasonAfterClose: false,
            exporting: false,
            scannedProductId: null,
            stockRecords: {total: 0, data: []},
            stockAlerts: {total: 0, data: []},
            kardexRecords: {total: 0, data: []},
            guideRecords: {total: 0, data: []},
            transferRecords: {total: 0, data: [], links: []},
            filters: createInventoryFilters(),
            appliedFilters: createInventoryFilters(),
            movementForm: {
                operation: null,
                warehouse: null,
                items: [{
                    key: `initial-operation-${Date.now()}`,
                    product: null,
                    amount: "",
                    unitCost: ""
                }],
                reason: ""
            },
            movementErrors: {},
            transferForm: {
                source: null,
                destination: null,
                status: DEFAULT_TRANSFER_STATUS,
                items: [{
                    key: `initial-transfer-${Date.now()}`,
                    product: null,
                    quantity: ""
                }],
                reason: ""
            },
            transferErrors: {},
            transferAvailability: {},
            transferAvailabilityRequest: 0,
            loadingTransferAvailability: false,
            transferAvailabilityError: false,
            options: {warehouses: [], products: [], stockCategories: [], stockBrands: [], movementUsers: [], transferStatuses: []},
            config: {
                ...Constants.generalConfig,
                entity: {
                    ...Requests.config({entity: "stocks_management"}),
                    page: {
                        title: VIEWS.find(view => view.code === this.page)?.title || "Inventario",
                        active: true,
                        menu: {id: VIEW_MENU_MAP[this.page]}
                    }
                }
            }
        };
    },
    methods: {
        activeMenuId() {
            return VIEW_MENU_MAP[this.activeView] || VIEW_MENU_MAP.stock;
        },
        async initParams() {
            const result = await Requests.get({
                route: this.config.entity.routes.initParams,
                data: {page: this.page},
                showAlert: true
            });
            this.options.warehouses = result.data?.config?.warehouses?.records || [];
            this.options.products = result.data?.config?.products?.records || [];
            this.options.stockCategories = result.data?.config?.stock_categories?.records || [];
            this.options.stockBrands = result.data?.config?.stock_brands?.records || [];
            this.options.movementUsers = result.data?.config?.movement_users || [];
            this.options.transferStatuses = result.data?.config?.transfer_statuses || [];
            this.canOperateTransfers = Boolean(result.data?.config?.can_operate_transfers);
            this.canUpdateStock = Boolean(result.data?.config?.can_update_stock);
            return Requests.valid({result});
        },
        async listStock({url = null} = {}) {
            if(!this.appliedFilters.warehouse?.code) return;

            const requestVersion = ++this.requestVersion;
            const warehouseId = this.appliedFilters.warehouse.code;
            const productSearch = this.appliedFilters.productSearch;
            const situation = this.appliedFilters.stockSituation?.code || "";

            this.loadingStock = true;

            try {
                this.stockRecords = Requests.emptyPagination();

                const result = await Requests.get({
                    route: url || Requests.config({entity: "stocks_management", type: "list"}),
                    data: {
                        warehouse_id: warehouseId,
                        per_page: STOCK_PAGE_SIZE,
                        product_search: productSearch,
                        situation,
                        ...this.currentStockAdvancedFilters(this.appliedFilters)
                    }
                });

                if(requestVersion !== this.requestVersion) return;

                this.stockRecords = result?.bool ? result.data : Requests.emptyPagination();
            } finally {
                if(requestVersion === this.requestVersion) this.loadingStock = false;
            }
        },
        async listStockAlerts({url = null, showModal = false} = {}) {
            const warehouseId = this.appliedFilters.warehouse?.code;
            if(!warehouseId || this.loadingStock || this.loadingAlerts) return;

            const requestVersion = this.requestVersion;
            this.loadingAlerts = true;
            this.stockAlertsError = "";
            this.stockAlerts = Requests.emptyPagination();
            Alerts.loading({message: "Consultando alertas de stock"});

            try {
                if(showModal) {
                    this.alertProductSearch = "";
                    this.alertMinimumStatus = STOCK_MINIMUM_OPTIONS[0];
                    this.appliedAlertProductSearch = "";
                    this.appliedAlertMinimumStatus = "";
                }

                const result = await Requests.get({
                    route: url || Requests.config({entity: "stocks_management", type: "alerts"}),
                    data: {
                        warehouse_id: warehouseId,
                        per_page: STOCK_PAGE_SIZE,
                        status: "open",
                        product_search: this.appliedAlertProductSearch,
                        minimum_status: this.appliedAlertMinimumStatus
                    }
                });

                if(requestVersion !== this.requestVersion || warehouseId !== this.appliedFilters.warehouse?.code) return;

                if(result.bool) {
                    this.stockAlerts = result.data;
                }else {
                    this.stockAlertsError = result.data?.msg || "No fue posible cargar las alertas de stock.";
                }
            }catch(error) {
                if(requestVersion === this.requestVersion) this.stockAlertsError = "No fue posible cargar las alertas de stock.";
            }finally {
                if(requestVersion === this.requestVersion) {
                    Alerts.close();
                    this.loadingAlerts = false;
                }
            }

            if(showModal) {
                await this.$nextTick();
                if(requestVersion !== this.requestVersion || warehouseId !== this.appliedFilters.warehouse?.code) return;

                Alerts.modals({id: "inventoryStockAlertsModal"});
            }
        },
        openTraceability(record) {
            if(!record?.id || !this.appliedFilters.warehouse?.code || this.loadingStock || this.loadingAlerts || this.loadingTraceability) return;
            this.traceabilityProduct = {...record};
            this.traceabilityWarehouse = {...this.appliedFilters.warehouse};
            return this.listTraceability({showModal: true});
        },
        async listTraceability({url = null, showModal = false} = {}) {
            const warehouseId = this.traceabilityWarehouse?.code;
            const itemId = this.traceabilityProduct?.id;
            if(!warehouseId || !itemId || this.loadingTraceability) return;
            const sequence = ++this.traceabilityRequest;
            this.loadingTraceability = true;
            this.traceabilityRecords = Requests.emptyPagination();
            this.traceabilityError = "";
            Alerts.loading({message: "Consultando trazabilidad del producto"});
            try {
                const result = await Requests.get({
                    route: url || Requests.config({entity: "stocks_management", type: "traceability"}),
                    data: {warehouse_id: warehouseId, item_id: itemId, per_page: STOCK_PAGE_SIZE}
                });
                if(sequence !== this.traceabilityRequest || Number(warehouseId) !== Number(this.appliedFilters.warehouse?.code)) return;
                if(result?.bool) this.traceabilityRecords = result.data;
                else this.traceabilityError = result?.data?.msg || "No fue posible cargar la trazabilidad.";
            } catch {
                if(sequence === this.traceabilityRequest) this.traceabilityError = "No fue posible cargar la trazabilidad.";
            } finally {
                if(sequence === this.traceabilityRequest) {
                    this.loadingTraceability = false;
                    Alerts.close();
                }
            }
            if(showModal && sequence === this.traceabilityRequest) {
                await this.$nextTick();
                if(sequence !== this.traceabilityRequest || Number(warehouseId) !== Number(this.appliedFilters.warehouse?.code)) return;
                Alerts.modals({id: "inventoryStockTraceabilityModal"});
            }
        },
        editStock(record, fromAlerts = false) {
            if(!this.canUpdateStock || this.loadingStock || this.loadingAlerts || this.loadingTraceability) return;
            const product = fromAlerts ? {
                ...record.warehouse_item?.item,
                stock_quantity: record.quantity,
                minimum_stock: record.minimum_stock
            } : record;
            return this.$refs.stockEditor?.open({record: product, warehouse: this.appliedFilters.warehouse, fromAlerts});
        },
        async refreshEditedStock({warehouseId, fromAlerts}) {
            if(Number(warehouseId) !== Number(this.appliedFilters.warehouse?.code)) return false;
            await this.listStock({});
            if(Number(warehouseId) !== Number(this.appliedFilters.warehouse?.code)) return false;
            if(fromAlerts) await this.listStockAlerts({});
            return true;
        },
        searchStockAlerts(value = this.alertProductSearch) {
            if(this.loadingAlerts) return;

            this.alertProductSearch = String(value ?? "").trim();
            this.appliedAlertProductSearch = this.alertProductSearch;
            this.appliedAlertMinimumStatus = this.alertMinimumStatus?.code || "";
            return this.listStockAlerts({});
        },
        async downloadStockAlerts(format) {
            const warehouseId = this.appliedFilters.warehouse?.code;
            if(!warehouseId || this.loadingAlerts || this.downloadingAlerts || !this.stockAlerts.total || !["pdf", "excel"].includes(format)) return;

            this.downloadingAlerts = true;
            Alerts.loading({message: `Generando ${format === "pdf" ? "PDF" : "Excel"} de alertas`});

            let result;

            try {
                result = await Requests.download({
                    route: Requests.config({entity: "stocks_management", type: "alertsExport"}),
                    data: {
                        warehouse_id: warehouseId,
                        export: format,
                        product_search: this.appliedAlertProductSearch,
                        minimum_status: this.appliedAlertMinimumStatus
                    },
                    fileName: `alertas_stock_${warehouseId}.${format === "pdf" ? "pdf" : "xlsx"}`
                });
            }catch(error) {
                result = {bool: false, data: {msg: "No se pudo generar el archivo de alertas."}};
            }finally {
                Alerts.close();
                this.downloadingAlerts = false;
            }

            if(!result?.bool) {
                Alerts.toastrs({type: "error", subtitle: result?.data?.msg || "No se pudo descargar el archivo de alertas."});
            }
        },
        async listKardex({url = null} = {}) {
            if(!this.appliedFilters.warehouse?.code) return;

            const requestVersion = ++this.requestVersion;
            const filters = this.currentReportFilters();

            this.loadingKardex = true;

            try {
                this.kardexRecords = Requests.emptyPagination();

                const result = await Requests.get({
                    route: url || Requests.config({entity: "stocks_management", type: "movements"}),
                    data: filters
                });

                if(requestVersion !== this.requestVersion) return;

                this.kardexRecords = result?.bool ? result.data : Requests.emptyPagination();
            } finally {
                if(requestVersion === this.requestVersion) this.loadingKardex = false;
            }
        },
        async listGuides({url = null} = {}) {
            if(!this.appliedFilters.warehouse?.code) return;

            const requestVersion = ++this.requestVersion;
            const filters = {
                warehouse_id: this.appliedFilters.warehouse.code,
                guide_type: this.appliedFilters.guideType?.code || "",
                date_from: this.appliedFilters.dateFrom,
                date_to: this.appliedFilters.dateTo
            };

            this.loadingGuides = true;

            try {
                this.guideRecords = Requests.emptyPagination();

                const result = await Requests.get({
                    route: url || Requests.config({entity: "stocks_management", type: "guides"}),
                    data: filters
                });

                if(requestVersion !== this.requestVersion) return;

                this.guideRecords = result?.bool ? result.data : Requests.emptyPagination();
            } finally {
                if(requestVersion === this.requestVersion) this.loadingGuides = false;
            }
        },
        async listTransfers({url = null} = {}) {
            const requestVersion = ++this.requestVersion;
            this.loadingTransfers = true;
            this.transferRecords = Requests.emptyPagination();

            try {
                const result = await Requests.get({
                    route: url || Requests.config({entity: "stocks_management", type: "transfers"}),
                    data: this.currentReportFilters(),
                    showAlert: true
                });

                if(requestVersion === this.requestVersion && result?.bool) this.transferRecords = result.data;
            } finally {
                if(requestVersion === this.requestVersion) this.loadingTransfers = false;
            }
        },
        searchCurrentView() {
            const warehouse = this.filters.warehouse?.code;
            if((this.activeView !== "transfers" && !warehouse) || this.isCurrentViewLoading) return Promise.resolve();

            const previousWarehouse = this.appliedFilters.warehouse?.code;
            if(previousWarehouse && previousWarehouse !== warehouse) this.invalidateListing();
            this.appliedFilters = {...this.filters};
            const exactProduct = this.activeView === "stock" ? this.findExactProduct(this.appliedFilters.productSearch) : null;
            this.scannedProductId = exactProduct ? Number(exactProduct.code) : null;

            return this.listCurrentView({});
        },
        clearFilters() {
            if(this.isCurrentViewLoading) return;

            this.filters = createInventoryFilters(this.filters.warehouse);
            return Alerts.showFiltersCleared();
        },
        findExactProduct(value) {
            const search = String(value || "").trim().toLocaleLowerCase();
            if(!search) return null;
            return this.products.find(product =>
                [product.barcode, product.internalCode, product.name]
                    .filter(Boolean)
                    .some(candidate => String(candidate).trim().toLocaleLowerCase() === search)
            ) || null;
        },
        focusProductSearch() {
            this.$nextTick(() => {
                this.$refs.productSearch?.focus();
            });
        },
        invalidateListing() {
            this.requestVersion += 1;
            if(this.loadingAlerts || this.loadingTraceability) Alerts.close();
            if(this.traceabilityProduct) Alerts.modals({type: "hide", id: "inventoryStockTraceabilityModal"});
            this.traceabilityRequest = (this.traceabilityRequest || 0) + 1;
            this.loadingTraceability = false;
            this.traceabilityProduct = null;
            this.traceabilityWarehouse = null;
            this.traceabilityRecords = {data: [], total: 0, links: []};
            this.traceabilityError = "";
            this.loadingStock = false;
            this.loadingKardex = false;
            this.loadingTransfers = false;
            this.loadingAlerts = false;
            this.stockAlertsError = "";
            this.loadingGuides = false;
            this.scannedProductId = null;
            this.stockRecords = {total: 0, data: []};
            this.stockAlerts = {total: 0, data: []};
            this.kardexRecords = {total: 0, data: []};
            this.guideRecords = {total: 0, data: []};
            this.transferRecords = Requests.emptyPagination();
        },
        listCurrentView({url = null} = {}) {
            if(this.activeView === "stock") return this.listStock({url});
            if(this.activeView === "transfers") return this.listTransfers({url});
            if(["kardex", "valued"].includes(this.activeView)) return this.listKardex({url});
            if(this.activeView === "guides") return this.listGuides({url});
            return Promise.resolve();
        },
        currentReportFilters() {
            const applied = this.appliedFilters;
            const filters = {
                warehouse_id: applied.warehouse?.code,
                movement_type: applied.movementType?.code || "",
                product_search: applied.productSearch,
                date_from: applied.dateFrom,
                date_to: applied.dateTo
            };

            if(this.activeView === "guides") {
                filters.guide_type = applied.guideType?.code || "";
            }

            if(this.activeView === "transfers") {
                filters.source_warehouse_id = applied.sourceWarehouse?.code || undefined;
                filters.destination_warehouse_id = applied.destinationWarehouse?.code || undefined;
                filters.transfer_status = applied.transferStatus?.code || "";
                filters.reference = applied.transferReference;
            }

            if(this.activeView === "stock") {
                filters.situation = applied.stockSituation?.code || "";
            }

            if(["kardex", "valued"].includes(this.activeView)) {
                filters.user_id = applied.movementUser?.code || "";
            }

            if(["stock", "kardex", "valued"].includes(this.activeView)) {
                Object.assign(filters, this.currentStockAdvancedFilters(applied));
            }

            return filters;
        },
        currentStockAdvancedFilters(selected = this.filters) {
            const filters = {
                category_id: selected.stockCategory?.code || "",
                brand_id: selected.stockBrand?.code || ""
            };
            if(this.activeView === "stock") {
                filters.minimum_status = selected.stockMinimumStatus?.code || "";
                filters.inactive_days = selected.stockInactiveDays?.code || "";
            }
            return filters;
        },
        async exportCurrentView() {
            if((this.activeView !== "transfers" && !this.appliedFilters.warehouse?.code) || this.exporting || this.isCurrentViewLoading) return;

            const confirmed = await Alerts.confirmDownload({resource: `el reporte de ${this.currentView.title.toLocaleLowerCase("es")}`});

            if(!confirmed || this.isCurrentViewLoading) return;

            this.exporting = true;
            Alerts.swals({type: "loading", message: "Preparando reporte"});
            const result = await Requests.download({
                route: this.config.entity.routes.export,
                data: {...this.currentReportFilters(), view: this.activeView},
                fileName: `inventario_${this.activeView}.xlsx`
            });
            this.exporting = false;
            Alerts.swals({show: false});

            if(!result?.bool) {
                Alerts.toastrs({type: "error", subtitle: result?.data?.msg || "No se pudo descargar el reporte."});
            }
        },
        newOperationItem(product = null) {
            return {
                key: `${Date.now()}-${Math.random().toString(16).slice(2)}`,
                product,
                amount: "",
                unitCost: "",
                currentStock: null
            };
        },
        prepareMovement(record = null) {
            this.resetOperationImport();
            const product = record
                ? this.products.find(item => Number(item.code) === Number(record.id))
                : this.findExactProduct(this.filters.productSearch);
            this.movementForm = {
                operation: null,
                warehouse: null,
                items: [this.newOperationItem(product)],
                reason: ""
            };
            this.movementErrors = {};
            this.refreshInventoryModalTooltips("inventoryMovementModal");
        },
        addMovementItem() {

            if(!this.canConfigureMovement) return;

            if(this.movementForm.items.length < 100) {
                this.disposeInventoryModalTooltips("inventoryMovementModal");
                this.movementForm.items.push(this.newOperationItem());
                this.refreshInventoryModalTooltips("inventoryMovementModal");
            }
        },
        removeMovementItem(index) {

            if(this.savingMovement || this.confirmingMovement || this.previewingOperation || !this.movementForm.items[index]) return;

            this.disposeInventoryModalTooltips("inventoryMovementModal");
            this.movementForm.items.splice(index, 1);
            this.movementErrors = {};
            this.refreshInventoryModalTooltips("inventoryMovementModal");
        },
        disposeInventoryModalTooltips(modalId) {
            Alerts.tooltips({show: false, time: 0, selector: `#${modalId} [data-bs-toggle='tooltip']`});
        },
        refreshInventoryModalTooltips(modalId) {
            this.$nextTick(() => Alerts.tooltips({show: true, time: 0, selector: `#${modalId} [data-bs-toggle='tooltip']`}));
        },
        movementProductOptions(index) {
            const selected = this.movementForm.items
                .filter((item, itemIndex) => itemIndex !== index)
                .map(item => Number(item.product?.code))
                .filter(Boolean);
            return this.products.filter(product => !selected.includes(Number(product.code)));
        },
        movementItemError(index, field) {
            return this.firstError(this.movementErrors[`items.${index}.${field}`]);
        },
        resetOperationImport({clearItems = false} = {}) {
            this.operationPreviewVersion++;
            this.previewingOperation = false;
            this.operationFileName = "";

            if(clearItems) {
                this.movementForm.items = [this.newOperationItem()];
            }
        },
        async downloadOperationTemplate() {
            if(!this.movementForm.operation?.code || !this.movementForm.warehouse?.code || this.downloadingOperation || this.previewingOperation) return;

            this.downloadingOperation = true;
            Alerts.loading({message: "Generando y descargando plantilla"});

            let result;

            try {

                result = await Requests.download({
                    route: Requests.config({entity: "stocks_management", type: "operationTemplate"}),
                    data: {
                        warehouse_id: this.movementForm.warehouse.code,
                        operation_code: this.movementForm.operation.code
                    },
                    fileName: `plantilla_${this.movementForm.operation.code}.xlsx`
                });

            }catch(error) {

                result = {bool: false, data: {msg: "No se pudo generar la plantilla."}};

            }finally {

                Alerts.close();
                this.downloadingOperation = false;

            }

            if(!result?.bool) {
                Alerts.toastrs({type: "error", subtitle: result?.data?.msg || "No se pudo descargar la plantilla."});
            }
        },
        async previewOperation(file) {
            if(!file || !this.movementForm.operation?.code || !this.movementForm.warehouse?.code) return;

            const warehouseId = this.movementForm.warehouse.code;
            const operationCode = this.movementForm.operation.code;
            const requestVersion = ++this.operationPreviewVersion;
            const formData = new FormData();

            formData.append("warehouse_id", warehouseId);
            formData.append("operation_code", operationCode);
            formData.append("file", file);
            if(this.operationFileName) {
                this.operationFileName = "";
                this.movementForm.items = [this.newOperationItem()];
            }
            this.previewingOperation = true;
            Alerts.loading({message: "Procesando plantilla"});

            let result;

            try {

                result = await Requests.post({
                    route: Requests.config({entity: "stocks_management", type: "operationPreview"}),
                    formData
                });

            }catch(error) {

                result = {bool: false, data: {msg: "No se pudo procesar la plantilla. Inténtalo nuevamente."}};

            }finally {

                Alerts.close();

                if(requestVersion === this.operationPreviewVersion) {

                    this.previewingOperation = false;

                }

            }

            if(requestVersion !== this.operationPreviewVersion) return;

            if(!Requests.valid({result})) {
                const details = Object.values(result?.errors || result?.data?.errors || {})
                    .flat()
                    .filter(Boolean)
                    .join(" ");

                Alerts.showMessage({
                    type: result?.code === 422 ? "warning" : "error",
                    title: "No se pudo procesar el archivo",
                    text: details || result?.data?.msg || "Comprueba el formato de la plantilla e inténtalo nuevamente."
                });
                return;
            }

            const importedItems = result.data.data.items || [];

            if(!importedItems.length) {
                Alerts.showMessage({
                    type: "warning",
                    title: "Archivo sin cantidades",
                    text: `Completa ${this.operationAmountLabel} de al menos un producto y vuelve a subir la plantilla.`
                });
                return;
            }

            for(const imported of importedItems) {
                if(!this.products.some(product => Number(product.code) === Number(imported.item_id))) {
                    this.options.products.push({
                        id: imported.item_id,
                        internal_code: imported.internal_code,
                        barcode: null,
                        name: imported.name,
                        measurement_unit: {
                            abbreviation: imported.unit_abbreviation
                        }
                    });
                }
            }

            this.disposeInventoryModalTooltips("inventoryMovementModal");
            this.movementForm.items = importedItems.map(imported => ({
                ...this.newOperationItem(this.products.find(product => Number(product.code) === Number(imported.item_id))),
                amount: imported.amount,
                unitCost: imported.unit_cost ?? "",
                currentStock: imported.current_stock
            }));
            this.operationFileName = file.name;
            this.movementErrors = {};
            this.refreshInventoryModalTooltips("inventoryMovementModal");
            Alerts.showMessage({
                type: "success",
                title: "Archivo procesado",
                text: `Se ${importedItems.length === 1 ? "cargó" : "cargaron"} ${importedItems.length} ${importedItems.length === 1 ? "producto" : "productos"} para ${this.movementForm.operation.label.toLocaleLowerCase("es")}. Las filas sin cantidad se omitieron. Revisa la tabla antes de guardar; el inventario aún no cambió.`
            });
        },
        async saveMovement() {
            if(this.savingMovement || this.confirmingMovement || this.previewingOperation || !this.movementForm.warehouse?.code || !this.movementForm.items.length) return;

            this.movementErrors = {};
            const operation = this.movementForm.operation;
            const amountField = this.movementAmountField;

            if(!operation?.code) {
                this.movementErrors.operation = ["Selecciona un tipo de operación."];
            }

            if(!this.movementForm.reason?.trim()) {
                this.movementErrors.reason = ["Campo obligatorio."];
            }

            this.movementForm.items.forEach((item, index) => {
                if(!item.product?.code) this.movementErrors[`items.${index}.item_id`] = ["Campo obligatorio."];
                if(item.amount === "" || item.amount === null || item.amount === undefined) {
                    this.movementErrors[`items.${index}.${amountField}`] = ["Campo obligatorio."];
                }
            });

            if(Object.keys(this.movementErrors).length) {
                Alerts.showMessage({
                    type: "warning",
                    title: "Revisa la operación",
                    text: "Selecciona un tipo de operación y completa el motivo, los productos y sus cantidades antes de continuar."
                });
                return;
            }

            this.confirmingMovement = true;
            const productCount = this.movementForm.items.length;
            const confirmed = await Alerts.confirmAction({
                title: "¿Registrar operación?",
                text: `La operación ${operation.label.toLocaleLowerCase("es")} actualizará el stock de ${productCount} ${productCount === 1 ? "producto" : "productos"} en ${this.movementForm.warehouse.displayLabel || this.movementForm.warehouse.label}.`,
                confirmButtonText: "Registrar operación",
                icon: "warning"
            });
            this.confirmingMovement = false;

            if(!confirmed) return;

            this.savingMovement = true;
            Alerts.swals({type: "loading", message: "Registrando operación"});
            const result = await Requests.post({
                route: Requests.config({entity: "stocks_management", type: "operations"}),
                data: {
                    warehouse_id: this.movementForm.warehouse.code,
                    movement_type: operation?.movementType,
                    origin_type: operation?.originType,
                    items: this.movementForm.items.map(item => ({
                        item_id: item.product?.code,
                        quantity: operation?.movementType === "correction" ? null : item.amount,
                        resulting_balance: operation?.movementType === "correction" ? item.amount : null,
                        unit_cost: operation?.movementType === "entry" ? item.unitCost : null
                    })),
                    reason: this.movementForm.reason
                }
            });
            this.savingMovement = false;
            Alerts.swals({show: false});

            if(Requests.valid({result})) {
                this.$refs.closeMovementModal?.click();
                Alerts.generateAlert({type: "success", msgContent: result.data.msg});
                await this.listStock({});
                return;
            }

            this.movementErrors = result?.errors || result?.data?.errors || {};
            Alerts.generateAlert({
                type: "error",
                messages: Object.values(this.movementErrors).flat().length
                    ? Object.values(this.movementErrors).flat()
                    : [result?.data?.msg || "No se pudo registrar la operación."]
            });
        },
        newTransferItem() {
            return {
                key: `${Date.now()}-${Math.random().toString(16).slice(2)}`,
                product: null,
                quantity: ""
            };
        },
        prepareTransfer() {

            this.transferErrors = {};
            this.transferForm = {
                source: null,
                destination: null,
                status: DEFAULT_TRANSFER_STATUS,
                items: [this.newTransferItem()],
                reason: ""
            };
        },
        openTransferReasons(event) {
            if(!this.canConfigureTransfer || this.savingTransfer) return;

            const trigger = event.currentTarget;
            this.focusTransferReasonAfterClose = false;
            Alerts.showStackedModal({
                id: "transferReasonsDialog",
                parent: trigger.closest(".modal"),
                onHidden: () => {
                    if(this.focusTransferReasonAfterClose) this.$refs.transferReason?.focus();
                    else trigger.focus();
                }
            });
        },
        applyTransferReason(reason) {
            if(!this.canConfigureTransfer || this.savingTransfer) return;

            this.transferForm.reason = reason;
            delete this.transferErrors.reason;
            this.focusTransferReasonAfterClose = true;
            Alerts.modals({type: "hide", id: "transferReasonsDialog"});
        },
        addTransferItem() {

            if(!this.canConfigureTransfer || this.savingTransfer) return;

            if(this.transferForm.items.length < 100) {
                this.disposeInventoryModalTooltips("inventoryTransferModal");
                this.transferForm.items.push(this.newTransferItem());
                this.refreshInventoryModalTooltips("inventoryTransferModal");
                this.$nextTick(() => {
                    const scroller = this.$refs.transferItemsScroll;
                    scroller?.scrollTo({top: scroller.scrollHeight, left: 0});
                });
            }
        },
        removeTransferItem(index) {

            if(this.savingTransfer || !this.transferForm.items[index]) return;

            this.disposeInventoryModalTooltips("inventoryTransferModal");
            this.transferForm.items.splice(index, 1);
            this.transferErrors = {};
            this.refreshInventoryModalTooltips("inventoryTransferModal");
        },
        clearTransferItems() {
            if(this.savingTransfer || !this.transferForm.items.length) return;

            this.disposeInventoryModalTooltips("inventoryTransferModal");
            this.transferForm.items = [];
            for(const field of Object.keys(this.transferErrors)) {
                if(field === "items" || field.startsWith("items.")) delete this.transferErrors[field];
            }
            this.refreshInventoryModalTooltips("inventoryTransferModal");
            this.$nextTick(() => this.$refs.addTransferProduct?.focus());
        },
        transferItemError(index, field) {
            return this.firstError(this.transferErrors[`items.${index}.${field}`]);
        },
        transferQuantityExceedsStock(item) {
            const available = this.transferAvailability[item.product?.code];
            return available != null && Numbers.compareNumbers(Numbers.fixedNumber(item.quantity), available) > 0;
        },
        transferQuantityErrors(item, index) {
            if(this.transferQuantityExceedsStock(item)) return ["La cantidad supera el stock disponible."];

            const error = this.transferItemError(index, "quantity");
            return error ? [error] : [];
        },
        clearTransferQuantityError(index) {
            delete this.transferErrors[`items.${index}.quantity`];
        },
        async loadTransferAvailability(query) {
            const request = ++this.transferAvailabilityRequest;
            this.transferAvailability = {};
            this.transferAvailabilityError = false;
            this.loadingTransferAvailability = Boolean(query);
            if(!query) return;

            const result = await Requests.get({
                route: Requests.config({entity: "stocks_management", type: "transferAvailability"}),
                data: query
            }).catch(() => null);

            if(request !== this.transferAvailabilityRequest) return;

            this.loadingTransferAvailability = false;
            if(Requests.valid({result})) {
                this.transferAvailability = result.data.data;
            }else {
                this.transferAvailabilityError = true;
            }
        },
        async saveTransfer() {
            if(this.savingTransfer || !this.canConfigureTransfer || !this.transferForm.items.length || this.hasTransferQuantityOverStock) return;

            this.transferErrors = {};
            this.savingTransfer = true;
            Alerts.swals({type: "loading", message: "Registrando traslado"});
            const result = await Requests.post({
                route: Requests.config({entity: "stocks_management", type: "transfers"}),
                data: {
                    source_warehouse_id: this.transferForm.source.code,
                    destination_warehouse_id: this.transferForm.destination?.code,
                    status: this.transferForm.status,
                    items: this.transferForm.items.map(item => ({
                        item_id: item.product?.code,
                        quantity: item.quantity
                    })),
                    reason: this.transferForm.reason
                }
            });
            this.savingTransfer = false;
            Alerts.swals({show: false});

            if(Requests.valid({result})) {
                this.$refs.closeTransferModal?.click();
                Alerts.generateAlert({type: "success", msgContent: result.data.msg});
                this.prepareTransfer();
                await this.listCurrentView({});
                return;
            }

            this.transferErrors = result?.errors ?? result?.data?.errors ?? {};
            this.loadTransferAvailability(this.transferAvailabilityQuery);
            Alerts.generateAlert({
                type: "error",
                messages: Object.values(this.transferErrors).flat().length
                    ? Object.values(this.transferErrors).flat()
                    : [result?.data?.msg || "No se pudo registrar el traslado."]
            });
        },
        stockStatus(record) {
            const {stock_quantity: stock, minimum_stock: minimum} = record;
            const code = StockStatus.fromLevels(stock, minimum);
            return {label: StockStatus.labels[code], cellClass: `br-inventory__situation--${code}`};
        },
        movementTypeLabel(type) {
            return MOVEMENT_TYPES.find(record => record.code === type)?.label || type;
        },
        originLabel(origin) {
            return {
                product_opening: "Creación de producto",
                commercial_catalog_seeder: "Stock inicial de demostración",
                manual: "Operación manual",
                sale: "Venta",
                sale_delivery: "Despacho de venta",
                sale_cancellation: "Devolución automática por anulación",
                purchase: "Compra",
                purchase_cancellation: "Anulación de compra",
                transfer_out: "Traslado enviado",
                transfer_in: "Traslado recibido",
                transfer_cancellation: "Cancelación de traslado",
                customer_return: "Devolución de cliente",
                supplier_return: "Devolución a proveedor",
                physical_count: "Toma física",
                recipe_sale: "Venta de receta",
                recipe_waste: "Merma de receta"
            }[origin] || origin;
        },
        formatDate(value) {
            if(!value) return "";
            return new Intl.DateTimeFormat("es-PE", {dateStyle: "short", timeStyle: "short"}).format(new Date(value));
        },
        formatSimpleDate(value) {
            if(!value) return "";
            const normalized = typeof value === "string" && value.length <= 10 ? `${value}T00:00:00` : value;
            return new Intl.DateTimeFormat("es-PE").format(new Date(normalized));
        },
        currencyNumber(value) {
            return Utils.formatCurrencyAmount(value, {sign: "S/"});
        },
        signedCurrency(value) {
            return Utils.formatSignedCurrencyAmount(value, {sign: "S/"});
        },
        firstError(error) {
            return Array.isArray(error) ? error[0] : error;
        },
        separatorNumber(value) {
            return Utils.separatorNumber(value || 0);
        }
    },
    computed: {
        productSearchPlaceholder: () => "Buscar producto o escanear código",
        breadcrumbTitles() {
            return [{title: "Catálogo e inventario"}, this.config.entity.page];
        },
        currentView() {
            return VIEWS.find(view => view.code === this.activeView) || VIEWS[0];
        },
        isReportView() {
            return ["kardex", "valued", "guides"].includes(this.activeView);
        },
        contextActionColumns() {
            if(this.isReportView) return "col-12 col-md-6 col-lg-3 col-xl-3";

            return "col-12 col-md-6 col-lg-9 col-xl-9";
        },
        warehouses() {
            return (this.options.warehouses || []).map(record => ({
                code: record.id,
                branchName: record.branch?.name || "",
                warehouseName: record.name,
                displayLabel: record.branch?.name ? `${record.branch.name} - ${record.name}` : record.name,
                label: record.branch?.name ? `${record.branch.name} / ${record.name}` : record.name
            }));
        },
        destinationWarehouses() {
            if(!this.transferForm.source?.code) return [];

            return this.realWarehouses.filter(warehouse => Number(warehouse.code) !== Number(this.transferForm.source?.code));
        },
        transferReasonOptions() {
            return TRANSFER_REASON_OPTIONS;
        },
        transferStatusFilters() {
            return [ALL_TRANSFER_STATUSES, ...this.options.transferStatuses];
        },
        hasTransferQuantityOverStock() {
            return this.transferForm.items.some(this.transferQuantityExceedsStock);
        },
        transferAvailabilityQuery() {
            if(!this.canConfigureTransfer) return null;

            const itemIds = this.transferForm.items.map(item => item.product?.code).filter(Boolean);

            return itemIds.length ? {warehouse_id: this.transferForm.source.code, item_ids: itemIds} : null;
        },
        realWarehouses() {
            return this.warehouses;
        },
        products() {
            return (this.options.products || []).map(product => ({
                code: product.id,
                name: product.name,
                internalCode: product.internal_code,
                barcode: product.barcode,
                unitAbbreviation: product.measurement_unit?.abbreviation,
                label: `${product.name} · ${product.internal_code}${product.barcode ? ` · ${product.barcode}` : ""}`
            }));
        },
        movementTypes() {
            return MOVEMENT_TYPES;
        },
        stockSituations() {
            return [ALL_STOCK_STATUSES, ...Object.entries(StockStatus.labels).map(([code, label]) => ({code, label}))];
        },
        movementUsers() {
            return [
                ALL_MOVEMENT_USERS,
                ...this.options.movementUsers.map(user => ({code: user.id, label: user.name}))
            ];
        },
        stockCategories() {
            return [
                {code: "", label: "Todas las categorías"},
                ...(this.options.stockCategories || []).map(category => ({code: category.id, label: category.name}))
            ];
        },
        stockBrands() {
            return [
                {code: "", label: "Todas las marcas"},
                ...(this.options.stockBrands || []).map(brand => ({code: brand.id, label: brand.name}))
            ];
        },
        stockMinimumOptions() {
            return STOCK_MINIMUM_OPTIONS;
        },
        stockInactivityOptions() {
            return STOCK_INACTIVITY_OPTIONS;
        },
        guideTypes() {
            return GUIDE_TYPES;
        },
        inventoryOperations() {
            return INVENTORY_OPERATION_OPTIONS;
        },
        inventoryOperationEffect() {
            return INVENTORY_OPERATION_GROUPS.find(group => group.code === this.movementForm.operation?.movementType)?.effect || "";
        },
        operationAmountLabel() {
            return this.movementForm.operation?.amountLabel || "Cantidad";
        },
        operationTemplateUrl() {
            const warehouseId = this.movementForm.warehouse?.code;
            const operationCode = this.movementForm.operation?.code;

            return warehouseId && operationCode
                ? `${Requests.config({entity: "stocks_management", type: "operationTemplate"})}?warehouse_id=${encodeURIComponent(warehouseId)}&operation_code=${encodeURIComponent(operationCode)}`
                : null;
        },
        movementAmountField() {
            return this.movementForm.operation?.movementType === "correction"
                ? "resulting_balance"
                : "quantity";
        },
        currentRecords() {
            if(this.activeView === "stock") return this.stockRecords;
            if(this.activeView === "transfers") return this.transferRecords;
            if(this.activeView === "guides") return this.guideRecords;

            return this.kardexRecords;
        },
        isCurrentViewLoading() {
            if(this.activeView === "stock") return this.loadingStock;
            if(this.activeView === "transfers") return this.loadingTransfers;
            if(this.activeView === "guides") return this.loadingGuides;

            return this.loadingKardex;
        },
        canConfigureMovement() {

            return Boolean(this.movementForm.warehouse?.code);

        },
        canConfigureTransfer() {

            const {source, destination} = this.transferForm;

            return Boolean(source?.code && destination?.code && Number(source.code) !== Number(destination.code));

        },
        canViewStockAlerts() {
            return Boolean(this.appliedFilters.warehouse?.code) && !this.loadingStock;
        }
    },
    watch: {
        "movementForm.operation"(operation, previous) {
            if(operation?.code) delete this.movementErrors.operation;

            if(operation?.code !== previous?.code) {
                this.resetOperationImport({clearItems: true});
            }

            this.refreshInventoryModalTooltips("inventoryMovementModal");
        },
        "movementForm.warehouse"(warehouse, previous) {
            if(warehouse?.code !== previous?.code) {
                this.resetOperationImport({clearItems: true});
            }
        },
        canConfigureTransfer(ready) {
            if(ready) {
                this.refreshInventoryModalTooltips("inventoryTransferModal");
            }else {
                this.disposeInventoryModalTooltips("inventoryTransferModal");
            }
        },
        transferAvailabilityQuery(query) {
            this.loadTransferAvailability(query);
        },
        "transferForm.source"() {
            if(!this.destinationWarehouses.some(warehouse => warehouse.code === this.transferForm.destination?.code)) {
                this.transferForm.destination = null;
            }
        }
    }
};
