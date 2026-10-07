export const FORM_TABS = [
    {
        id: "general",
        label: "Información general",
        description: "Identidad, precios y estado",
        fields: ["internal_code", "barcode", "name", "price", "min_price", "max_price", "currency", "currency_id", "status"]
    },
    {
        id: "commercial",
        label: "Atributos e impuestos",
        description: "Impuestos, clasificación y publicación",
        fields: ["price_includes_tax", "igv_exempt", "expires_at", "commission_type", "commission_value", "brand", "brand_id", "categories", "description", "see_my_web", "see_my_web_price"]
    },
    {
        id: "inventory",
        label: "Inventario",
        description: "Stock por almacén",
        fields: ["inventory"]
    }
];

export const FORM_FIELDS = {
    internal_code: "",
    barcode: "",
    name: "",
    description: "",
    price: "",
    price_includes_tax: true,
    igv_exempt: false,
    expires_at: "",
    commission_type: "none",
    commission_value: "",
    min_price: "",
    max_price: "",
    currency: null,
    categories: [],
    brand: null,
    see_my_web: true,
    see_my_web_price: false,
    inventory: [],
    status: null
};

export const FORM_FIELD_CONFIG = {
    internal_code: {trim: true},
    barcode: {trim: true},
    name: {trim: true},
    description: {normalize: true},
    price: {toNumber: true, minValue: 0},
    price_includes_tax: {toBoolean: true},
    igv_exempt: {toBoolean: true},
    expires_at: {normalize: true},
    commission_value: {toNumber: true, minValue: 0},
    min_price: {toNumber: true, minValue: 0},
    max_price: {toNumber: true, minValue: 0},
    currency: {mapToField: "currency_id"},
    categories: {getArray: {mapTo: "category_id"}},
    brand: {mapToField: "brand_id"},
    see_my_web: {toBoolean: true},
    see_my_web_price: {toBoolean: true},
    status: {getCode: true}
};

export const VALIDATION_RULES = {
    internal_code: {required: true},
    barcode: {required: true},
    name: {required: true},
    description: {required: false},
    price: {required: true, number: true, min: 0},
    price_includes_tax: {required: false},
    igv_exempt: {required: false},
    expires_at: {required: false},
    commission_type: {required: true},
    commission_value: {required: false, number: true, min: 0},
    min_price: {required: false, number: true, min: 0},
    max_price: {required: false, number: true, min: 0},
    currency: {required: true},
    categories: {required: false},
    brand: {required: false},
    see_my_web: {required: false},
    see_my_web_price: {required: false},
    inventory: {required: true},
    status: {required: true}
};

export const ERROR_LABELS = {
    internal_code: "Código interno",
    barcode: "Código de barras",
    name: "Nombre",
    description: "Descripción comercial adicional",
    price: "Precio de venta",
    price_includes_tax: "Precio incluye IGV",
    igv_exempt: "IGV exonerado",
    expires_at: "Fecha de vencimiento",
    min_price: "Precio mínimo",
    max_price: "Precio máximo",
    currency: "Moneda",
    commission_type: "Comisión",
    commission_value: "Valor de comisión",
    categories: "Categorías",
    brand: "Marca",
    inventory: "Inventario por almacén",
    status: "Estado"
};

function calculateEan13CheckDigit(twelveDigits) {

    const sum = twelveDigits
        .split("")
        .reduce((total, digit, index) => total + Number(digit) * (index % 2 === 0 ? 1 : 3), 0);

    return String((10 - (sum % 10)) % 10);

}

export function generateEan13() {

    const randomValue = window.crypto?.getRandomValues
        ? (() => {

            const values = new Uint32Array(1);
            window.crypto.getRandomValues(values);

            return values[0];

        })()
        : Math.floor(Math.random() * 1000000000);

    const body = `200${String(randomValue % 1000000000).padStart(9, "0")}`;

    return `${body}${calculateEan13CheckDigit(body)}`;

}

export function isValidEan13(value) {

    const barcode = String(value ?? "");

    return /^\d{13}$/.test(barcode) && barcode[12] === calculateEan13CheckDigit(barcode.slice(0, 12));

}

export const PRODUCT_TEXTS = {
    filters: {
        filterBy: "Filtrar por",
        search: "Búsqueda"
    },
    actions: {
        search: "Buscar",
        add: "Agregar producto",
        edit: "Editar producto",
        import: "Carga masiva",
        download: "Descargar Excel"
    },
    table: {
        ariaLabel: "Listado de productos"
    },
    form: {
        internalCode: "Código interno",
        barcode: "Código de barras",
        name: "Nombre",
        commercialDescription: "Descripción comercial adicional",
        price: "Precio de venta",
        minPrice: "Precio mínimo",
        maxPrice: "Precio máximo",
        expiresAt: "Fecha de vencimiento",
        categories: "Categorías",
        brand: "Marca",
        status: "Estado",
        internalCodeHelp: "Identificador privado que la empresa utiliza para ordenar, buscar y controlar internamente el producto.",
        barcodeHelp: "Código de barras en formato EAN-13 que puede imprimirse en la etiqueta del producto y ser leído por clientes o escáneres.",
        generateInternalCodeTooltip: "Generar y reemplazar por un código interno válido",
        generateBarcodeTooltip: "Generar y reemplazar por un código de barras EAN-13 válido"
    },
    modal: {
        store: "Agregar producto",
        update: "Editar producto",
        storing: "Agregando",
        updating: "Editando"
    }
};
