import * as Alerts from "@System/Helpers/Alerts.js";
import * as Constants from "@System/Helpers/Constants.js";
import * as Requests from "@System/Helpers/Requests.js";
import * as Utils from "@System/Helpers/Utils.js";
import {createInitialLoad} from "@System/Helpers/InitialLoad.js";
import ProductSelect from "@System/Components/Generics/ProductSelect.vue";
import SearchInput from "@System/Components/Generics/SearchInput.vue";
import TemplateFilePicker from "@System/Components/Generics/TemplateFilePicker.vue";
import WarehouseSelect from "@System/Components/Generics/WarehouseSelect.vue";
import InventorySkeleton from "./InventorySkeleton.vue";

const initialLoad = createInitialLoad({skeleton: true});

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
    components: {ProductSelect, SearchInput, TemplateFilePicker, WarehouseSelect, InventorySkeleton},
    props: {
        page: {
            type: String,
            required: true,
            validator: value => VIEWS.some(view => view.code === value)
        }
    },
    async mounted() {
        Utils.navbarItem("menu-parent-items", {addClass: "open"});
        Utils.navbarItem(this.activeMenuId(), {});
        initialLoad.start();
        try {
            const initialized = await this.initParams();

            if(initialized && this.realWarehouses.length) {
                this.filters.warehouse = this.realWarehouses[0];
                await this.$nextTick();
                await this.listCurrentView({});
            }
        } finally {
            this.warehouseSearchEnabled = true;
            this.initialLoading = false;
            initialLoad.finish();
        }
    },
    data() {
        return {
            activeView: this.page,
            initialLoading: true,
            skeletonEnabled: initialLoad.skeleton,
            loadingStock: false,
            loadingKardex: false,
            loadingAlerts: false,
            loadingGuides: false,
            warehouseSearchEnabled: false,
            requestVersion: 0,
            savingMovement: false,
            confirmingMovement: false,
            previewingOperation: false,
            downloadingOperation: false,
            operationFileName: "",
            operationPreviewVersion: 0,
            savingTransfer: false,
            exporting: false,
            scannedProductId: null,
            stockRecords: {total: 0, data: []},
            stockAlerts: {total: 0, data: []},
            kardexRecords: {total: 0, data: []},
            guideRecords: {total: 0, data: []},
            filters: {
                warehouse: null,
                productSearch: "",
                movementType: MOVEMENT_TYPES[0],
                guideType: GUIDE_TYPES[0],
                dateFrom: "",
                dateTo: ""
            },
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
                items: [{
                    key: `initial-transfer-${Date.now()}`,
                    product: null,
                    quantity: ""
                }],
                reason: ""
            },
            transferErrors: {},
            options: {warehouses: [], products: []},
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
            return Requests.valid({result});
        },
        async listStock({url = null} = {}) {
            if(!this.filters.warehouse?.code) return;

            const requestVersion = ++this.requestVersion;
            const warehouseId = this.filters.warehouse.code;
            const isConsolidated = warehouseId === "all";
            const productSearch = this.filters.productSearch;

            this.loadingStock = true;

            const result = await Requests.get({
                route: url || Requests.config({
                    entity: "stocks_management",
                    type: isConsolidated ? "summary" : "list"
                }),
                data: {
                    warehouse_id: warehouseId,
                    product_search: productSearch
                }
            });

            if(requestVersion !== this.requestVersion) return;

            this.stockRecords = isConsolidated
                ? (result?.data?.data || {total: 0, data: []})
                : (result?.data || {total: 0, data: []});
            this.loadingStock = false;

            if(isConsolidated) {
                this.stockAlerts = {total: 0, data: []};
            }else {
                const alertsRequest = this.listStockAlerts({warehouseId, requestVersion});
                if(this.initialLoading) await alertsRequest;
            }
        },
        async listStockAlerts({warehouseId, requestVersion}) {
            this.loadingAlerts = true;

            const result = await Requests.get({
                route: Requests.config({entity: "stocks_management", type: "alerts"}),
                data: {
                    warehouse_id: warehouseId,
                    status: "open"
                }
            });

            if(requestVersion !== this.requestVersion) return;

            this.stockAlerts = result?.data || {total: 0, data: []};
            this.loadingAlerts = false;
        },
        async listKardex({url = null} = {}) {
            if(!this.filters.warehouse?.code) return;

            const requestVersion = ++this.requestVersion;
            const filters = this.currentReportFilters();

            this.loadingKardex = true;

            const result = await Requests.get({
                route: url || Requests.config({entity: "stocks_management", type: "movements"}),
                data: filters
            });

            if(requestVersion !== this.requestVersion) return;

            this.kardexRecords = result?.data || {total: 0, data: []};
            this.loadingKardex = false;
        },
        async listGuides({url = null} = {}) {
            if(!this.filters.warehouse?.code) return;

            const requestVersion = ++this.requestVersion;
            const filters = {
                warehouse_id: this.isConsolidatedStock ? "" : this.filters.warehouse.code,
                guide_type: this.filters.guideType?.code || "",
                date_from: this.filters.dateFrom,
                date_to: this.filters.dateTo
            };

            this.loadingGuides = true;

            const result = await Requests.get({
                route: url || Requests.config({entity: "stocks_management", type: "guides"}),
                data: filters
            });

            if(requestVersion !== this.requestVersion) return;

            this.guideRecords = result?.data || {total: 0, data: []};
            this.loadingGuides = false;
        },
        searchCurrentView() {
            if(!this.filters.warehouse?.code || this.isCurrentViewLoading) return Promise.resolve();

            return this.listCurrentView({});
        },
        async handleProductSearch() {
            if(this.isCurrentViewLoading) return;

            const exactProduct = this.findExactProduct(this.filters.productSearch);
            this.scannedProductId = exactProduct ? Number(exactProduct.code) : null;

            await this.searchCurrentView();
        },
        clearProductSearch() {
            this.scannedProductId = null;
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
            this.loadingStock = false;
            this.loadingKardex = false;
            this.loadingAlerts = false;
            this.loadingGuides = false;
            this.scannedProductId = null;
            this.stockRecords = {total: 0, data: []};
            this.stockAlerts = {total: 0, data: []};
            this.kardexRecords = {total: 0, data: []};
            this.guideRecords = {total: 0, data: []};
        },
        listCurrentView({url = null} = {}) {
            if(this.activeView === "stock") return this.listStock({url});
            if(["kardex", "transfers", "valued"].includes(this.activeView)) return this.listKardex({url});
            if(this.activeView === "guides") return this.listGuides({url});
            return Promise.resolve();
        },
        currentReportFilters() {
            const filters = {
                warehouse_id: this.filters.warehouse?.code,
                movement_type: this.filters.movementType?.code || "",
                product_search: this.filters.productSearch,
                date_from: this.filters.dateFrom,
                date_to: this.filters.dateTo
            };

            if(this.activeView === "transfers") {
                filters.origin_types = ["transfer_out", "transfer_in"];
                filters.movement_type = "";
            }

            if(this.activeView === "guides") {
                filters.guide_type = this.filters.guideType?.code || "";
            }

            return filters;
        },
        async exportCurrentView() {
            if(!this.filters.warehouse?.code || this.exporting || this.isCurrentViewLoading) return;

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

            if(!this.canConfigureMovement) return;

            if(this.movementForm.items.length > 1) {
                this.disposeInventoryModalTooltips("inventoryMovementModal");
                this.movementForm.items.splice(index, 1);
                this.refreshInventoryModalTooltips("inventoryMovementModal");
            }
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
                            singular_name: imported.unit_singular,
                            plural_name: imported.unit_plural
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
            if(this.savingMovement || this.confirmingMovement || this.previewingOperation || !this.movementForm.warehouse?.code) return;

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
                text: `Registrarás ${operation.label.toLocaleLowerCase("es")} en ${this.movementForm.warehouse.displayLabel || this.movementForm.warehouse.label}. Se actualizará el stock de ${productCount} ${productCount === 1 ? "producto" : "productos"}. ¿Deseas continuar?`,
                confirmButtonText: "Sí, registrar",
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
                items: [this.newTransferItem()],
                reason: ""
            };
            this.refreshInventoryModalTooltips("inventoryTransferModal");
        },
        addTransferItem() {

            if(!this.canConfigureTransfer) return;

            if(this.transferForm.items.length < 100) {
                this.disposeInventoryModalTooltips("inventoryTransferModal");
                this.transferForm.items.push(this.newTransferItem());
                this.refreshInventoryModalTooltips("inventoryTransferModal");
            }
        },
        removeTransferItem(index) {

            if(!this.canConfigureTransfer) return;

            if(this.transferForm.items.length > 1) {
                this.disposeInventoryModalTooltips("inventoryTransferModal");
                this.transferForm.items.splice(index, 1);
                this.refreshInventoryModalTooltips("inventoryTransferModal");
            }
        },
        transferProductOptions(index) {
            const selected = this.transferForm.items
                .filter((item, itemIndex) => itemIndex !== index)
                .map(item => Number(item.product?.code))
                .filter(Boolean);
            return this.products.filter(product => !selected.includes(Number(product.code)));
        },
        transferItemError(index, field) {
            return this.firstError(this.transferErrors[`items.${index}.${field}`]);
        },
        async saveTransfer() {
            if(this.savingTransfer || !this.transferForm.source?.code || !this.transferForm.destination?.code) return;

            this.transferErrors = {};
            this.savingTransfer = true;
            Alerts.swals({type: "loading", message: "Registrando traslado"});
            const result = await Requests.post({
                route: Requests.config({entity: "stocks_management", type: "transfers"}),
                data: {
                    source_warehouse_id: this.transferForm.source.code,
                    destination_warehouse_id: this.transferForm.destination?.code,
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
                await this.listKardex({});
                return;
            }

            this.transferErrors = result?.errors ?? result?.data?.errors ?? {};
            Alerts.generateAlert({
                type: "error",
                messages: Object.values(this.transferErrors).flat().length
                    ? Object.values(this.transferErrors).flat()
                    : [result?.data?.msg || "No se pudo registrar el traslado."]
            });
        },
        stockStatus(record) {
            const stock = Number(record.stock_quantity || 0);
            const minimum = Number(record.minimum_stock || 0);
            if(stock <= 0) return {label: "Sin existencias", icon: "fa-solid fa-circle-xmark", badgeClass: "br-badge--danger"};
            if(stock <= minimum) return {label: "Stock bajo", icon: "fa-solid fa-triangle-exclamation", badgeClass: "br-badge--warning"};
            return {label: "Stock saludable", icon: "fa-solid fa-circle-check", badgeClass: "br-badge--success"};
        },
        stockWarehouseBreakdown(record) {
            if(this.isConsolidatedStock) return record.warehouse_breakdown || [];

            const warehouse = this.filters.warehouse;
            if(!warehouse?.code) return [];

            return [{
                warehouse_id: warehouse.code,
                warehouse_name: warehouse.warehouseName,
                branch_name: warehouse.branchName,
                quantity: record.stock_quantity || 0,
                requires_attention: this.stockStatus(record).badgeClass !== "br-badge--success"
            }];
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
                sale_cancellation: "Devolución automática por anulación",
                purchase: "Compra",
                purchase_cancellation: "Anulación de compra",
                transfer_out: "Traslado enviado",
                transfer_in: "Traslado recibido",
                customer_return: "Devolución de cliente",
                supplier_return: "Devolución a proveedor",
                physical_count: "Toma física"
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
        quantityWithUnit(value, item) {
            return Utils.quantityWithUnit(value, item?.measurement_unit || item);
        },
        signedQuantity(value, item) {
            const number = Number(value || 0);

            return `${number > 0 ? "+" : ""}${this.quantityWithUnit(number, item)}`;
        },
        currencyNumber(value, decimals = null) {
            return `S/ ${Utils.separatorNumber(value || 0, decimals)}`;
        },
        signedCurrency(value) {
            const number = Number(value || 0);
            return `${number > 0 ? "+" : number < 0 ? "-" : ""}${this.currencyNumber(Math.abs(number))}`;
        },
        firstError(error) {
            return Array.isArray(error) ? error[0] : error;
        },
        alertProductName(alert) {
            return alert?.warehouse_item?.item?.name || "Producto sin nombre";
        },
        separatorNumber(value) {
            return Utils.separatorNumber(value || 0);
        }
    },
    computed: {
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
            if(["stock", "transfers"].includes(this.activeView)) return "col-12 col-lg-6 col-xl-6";
            if(this.isReportView) return "col-12 col-md-6 col-lg-3 col-xl-3";

            return "col-12 col-md-6 col-lg-9 col-xl-9";
        },
        warehouses() {
            const warehouses = (this.options.warehouses || []).map(record => ({
                code: record.id,
                branchName: record.branch?.name || "",
                warehouseName: record.name,
                displayLabel: record.branch?.name ? `${record.branch.name} - ${record.name}` : record.name,
                label: record.branch?.name ? `${record.branch.name} / ${record.name}` : record.name
            }));

            return ["stock", "transfers"].includes(this.activeView)
                ? [{code: "all", label: "Todos los almacenes"}, ...warehouses]
                : warehouses;
        },
        destinationWarehouses() {
            if(!this.transferForm.source?.code) return [];

            return this.realWarehouses.filter(warehouse => Number(warehouse.code) !== Number(this.transferForm.source?.code));
        },
        realWarehouses() {
            return this.warehouses.filter(warehouse => warehouse.code !== "all");
        },
        products() {
            return (this.options.products || []).map(product => ({
                code: product.id,
                name: product.name,
                internalCode: product.internal_code,
                barcode: product.barcode,
                unitSingular: product.measurement_unit?.singular_name || "UNIDAD",
                unitPlural: product.measurement_unit?.plural_name || "UNIDADES",
                label: `${product.name} · ${product.internal_code}${product.barcode ? ` · ${product.barcode}` : ""}`
            }));
        },
        movementTypes() {
            return MOVEMENT_TYPES;
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
            if(this.activeView === "guides") return this.guideRecords;

            return this.kardexRecords;
        },
        openStockAlerts() {
            return this.stockAlerts?.data || [];
        },
        isCurrentViewLoading() {
            if(this.activeView === "stock") return this.loadingStock || this.loadingAlerts;
            if(this.activeView === "guides") return this.loadingGuides;

            return this.loadingKardex;
        },
        canConfigureMovement() {

            return Boolean(this.movementForm.warehouse?.code);

        },
        canConfigureTransfer() {

            return Boolean(this.transferForm.source?.code && this.transferForm.destination?.code);

        },
        isConsolidatedStock() {
            return this.filters.warehouse?.code === "all";
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
        "filters.warehouse"(warehouse, previous) {
            if(warehouse?.code === previous?.code) return;

            this.invalidateListing();

            if(this.warehouseSearchEnabled && warehouse?.code) {
                this.listCurrentView({});
            }
        },
        "transferForm.source"() {
            if(!this.destinationWarehouses.some(warehouse => warehouse.code === this.transferForm.destination?.code)) {
                this.transferForm.destination = null;
            }
        },
        "filters.productSearch"() {
            this.invalidateListing();
        },
        "filters.movementType"() {
            this.invalidateListing();
        },
        "filters.guideType"() {
            this.invalidateListing();
        },
        "filters.dateFrom"() {
            this.invalidateListing();
        },
        "filters.dateTo"() {
            this.invalidateListing();
        }
    }
};
