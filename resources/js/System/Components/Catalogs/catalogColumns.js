const COMMON_IDENTITY = {title: "Identificación"};
const COMMON_INTERNAL_CODE = {title: "Código interno", align: "fg-align-center"};
const COMMON_STATUS = {title: "Estado", align: "fg-align-center"};
const COMMON_ACTIONS = {title: "Acciones", align: "fg-align-center"};

const offeringColumns = title => [
    {...COMMON_INTERNAL_CODE, width: "22%"},
    {title, width: "34%"},
    {title: "Detalle", width: "15%"},
    {title: "Precio", width: "15%", align: "fg-align-end"},
    {...COMMON_STATUS, width: "8%"},
    {...COMMON_ACTIONS, width: "6%"}
];

export const catalogColumns = {
    products: [
        {...COMMON_IDENTITY, width: "22%"},
        {title: "Producto", width: "40%"},
        {title: "Precio", width: "16%", align: "fg-align-end"},
        {...COMMON_STATUS, width: "13%"},
        {...COMMON_ACTIONS, width: "9%"}
    ],
    services: offeringColumns("Servicio"),
    subscriptions: offeringColumns("Membresía"),
    categories: [
        {...COMMON_INTERNAL_CODE, width: "20%"},
        {title: "Categoría", width: "40%"},
        {title: "En uso", width: "20%", align: "fg-align-center"},
        {...COMMON_STATUS, width: "13%"},
        {...COMMON_ACTIONS, width: "7%"}
    ],
    brands: [
        {...COMMON_INTERNAL_CODE, width: "20%"},
        {title: "Marca", width: "28%"},
        {title: "Sitio oficial", width: "14%", align: "fg-align-center"},
        {title: "En uso", width: "19%", align: "fg-align-center"},
        {...COMMON_STATUS, width: "11%"},
        {...COMMON_ACTIONS, width: "8%"}
    ],
    recipes: [
        {...COMMON_INTERNAL_CODE, width: "18%"},
        {title: "Platillo", width: "32%"},
        {title: "Fórmula", width: "18%"},
        {title: "Operación", width: "16%"},
        {...COMMON_STATUS, width: "10%"},
        {...COMMON_ACTIONS, width: "6%"}
    ],
    branches: [
        {...COMMON_IDENTITY, width: "20%"},
        {title: "Sucursal", width: "18%"},
        {title: "Ubicación", width: "19%"},
        {title: "Contacto", width: "19%"},
        {title: "Aforo", width: "10%", align: "fg-align-center"},
        {...COMMON_STATUS, width: "8%"},
        {...COMMON_ACTIONS, width: "6%"}
    ],
    users: [
        {...COMMON_IDENTITY, width: "20%"},
        {title: "Colaborador", width: "20%"},
        {title: "Acceso", width: "20%"},
        {title: "Contacto", width: "25%"},
        {...COMMON_STATUS, width: "9%"},
        {...COMMON_ACTIONS, width: "6%"}
    ]
};
