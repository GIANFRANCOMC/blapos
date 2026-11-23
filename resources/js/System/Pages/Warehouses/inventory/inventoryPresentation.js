export const inventoryProductPlaceholder = "Buscar producto o escanear código";
const warehouse = {key: "warehouse", label: "Almacén de trabajo", kind: "warehouse", options: "warehouses"};
const product = {key: "productSearch", label: "Producto", kind: "search", placeholder: inventoryProductPlaceholder};
const dates = [
    {key: "dateFrom", label: "Desde", kind: "date", compact: true},
    {key: "dateTo", label: "Hasta", kind: "date", compact: true}
];
const classifications = [
    {key: "stockCategory", label: "Categoría", options: "stockCategories", placeholder: "Todas las categorías", searchable: true},
    {key: "stockBrand", label: "Marca", options: "stockBrands", placeholder: "Todas las marcas", searchable: true}
];
const movementFilters = [
    warehouse, product, {key: "movementType", label: "Movimiento", options: "movementTypes", compact: true},
    ...dates, {key: "movementUser", label: "Realizado por", options: "movementUsers", searchable: true},
    ...classifications
];

export const inventoryFilters = {
    stock: [
        warehouse, product,
        {key: "stockSituation", label: "Estado del stock", options: "stockSituations", compact: true},
        {key: "stockMinimumStatus", label: "Stock mínimo", options: "stockMinimumOptions", compact: true},
        {key: "stockInactiveDays", label: "Último movimiento", options: "stockInactivityOptions", compact: true},
        ...classifications
    ],
    guides: [warehouse, {key: "guideType", label: "Tipo de guía", options: "guideTypes", compact: true}, ...dates],
    transfers: [
        {key: "sourceWarehouse", label: "Almacén de origen", kind: "warehouse", options: "transferWarehouseOptions"},
        {key: "destinationWarehouse", label: "Almacén de destino", kind: "warehouse", options: "transferWarehouseOptions"},
        {key: "transferReference", label: "Referencia", kind: "search", placeholder: "Buscar referencia", maxlength: 40, compact: true},
        ...dates,
        {key: "movementUser", label: "Registrado por", options: "movementUsers", searchable: true},
        product,
        {key: "transferStatus", label: "Estado del traslado", options: "transferStatusFilters", compact: true}
    ],
    kardex: movementFilters,
    valued: movementFilters
};

const productColumn = {label: "Producto", width: "37%", align: "center"};
const variation = {label: "Variación", align: "end"};
const actions = {label: "Acciones", width: "56px", hidden: true};

export const inventoryViews = {
    stock: {
        title: "Control de stock", description: "Consulta las existencias y los mínimos por almacén.",
        listTitle: "Existencias por producto", loading: "Buscando existencias…", minWidth: "800px",
        columns: [
            productColumn, {label: "Stock actual", align: "end"}, {label: "Stock mínimo", align: "end"},
            {label: "Diferencia", align: "end"}, {label: "Estado del stock", width: "16%", align: "center"}, actions
        ]
    },
    guides: {
        title: "Guías", description: "Consulta las guías de entrada y salida del almacén.",
        listTitle: "Guías registradas", loading: "Buscando guías…", minWidth: "800px",
        columns: [
            {label: "Fecha y número", width: "16%"}, {label: "Almacén", width: "19%"},
            {label: "Tipo", width: "10%"}, {label: "Detalle", width: "27%"},
            {label: "Referencia", width: "16%"}, {label: "Estado", width: "12%", align: "center"}
        ]
    },
    transfers: {
        title: "Traslados", description: "Consulta y registra movimientos de productos entre almacenes.",
        listTitle: "Traslados registrados", loading: "Buscando traslados…", minWidth: "880px",
        columns: [
            {label: "Referencia", width: "15%", align: "center"}, {label: "Almacén de origen", width: "17%", align: "end"},
            {label: "Dirección del traslado", width: "64px", hidden: true}, {label: "Almacén de destino", width: "17%"},
            {label: "Detalle"}, {label: "Estado", width: "20%", align: "center"}, actions
        ]
    },
    kardex: {
        title: "Kardex", description: "Consulta el historial de entradas, salidas y correcciones.",
        listTitle: "Movimientos de inventario", loading: "Buscando movimientos…", minWidth: "800px",
        columns: [productColumn, {label: "Movimiento", width: "31%", align: "center"}, variation, {label: "Saldo", align: "end"}]
    },
    valued: {
        title: "Kardex valorizado", description: "Consulta el costo y el valor de los movimientos de inventario.",
        listTitle: "Movimientos valorizados", loading: "Buscando movimientos valorizados…", minWidth: "1100px",
        columns: [
            productColumn, {label: "Movimiento", width: "19%", align: "center"}, variation,
            {label: "Costo unitario", align: "end"}, {label: "Valor movimiento", align: "end"}, {label: "Valor resultante", align: "end"}
        ]
    }
};

export const inventoryBadgeTones = {
    entry: "success", exit: "danger", correction: "warning",
    healthy: "success", empty: "danger", low: "warning"
};
