/**
 * Constantes centralizadas para módulos del sistema
 * Evita repetición de código y facilita mantenimiento
 */

// Estados comunes
export const STATUS = {
    ACTIVE: "active",
    INACTIVE: "inactive",
    PENDING: "pending",
    CANCELED: "canceled",
    SENT: "sent",
    FAILED: "failed",
    MAINTENANCE: "maintenance",
    RETIRED: "retired"
};

/**
 * Modificador visual para .br-badge--{valor} (estándar de plataforma).
 * Valores: success | info | warning | danger | primary | secondary | neutral | dark
 */
export const STATUS_BADGE_VARIANTS = {
    [STATUS.ACTIVE]: "success",
    [STATUS.INACTIVE]: "danger",
    [STATUS.PENDING]: "warning",
    [STATUS.CANCELED]: "danger",
    [STATUS.SENT]: "success",
    [STATUS.FAILED]: "danger",
    [STATUS.MAINTENANCE]: "warning",
    [STATUS.RETIRED]: "danger"
};

/** Suscripciones en tracking: inactive como primary (no danger). */
export const STATUS_BADGE_CUSTOM_SUBSCRIPTION = {
    inactive: "primary"
};

/** Asistencias en timeline de cliente. */
export const STATUS_BADGE_CUSTOM_ATTENDANCE_TIMELINE = {
    active: "success",
    finalized: "primary",
    canceled: "danger",
    inactive: "warning"
};

/** Libro de reclamos (códigos de estado). */
export const STATUS_BADGE_CUSTOM_BOOK_COMPLAINTS = {
    in_progress: "primary",
    resolved: "success",
    pending: "danger"
};

/** Activos en mantenimiento — variante warning (listados). */
export const STATUS_BADGE_CUSTOM_ASSET_MAINTENANCE_WARNING = {
    maintenance: "warning"
};

/** Activos en mantenimiento — variante primary (detalle). */
export const STATUS_BADGE_CUSTOM_ASSET_MAINTENANCE_PRIMARY = {
    maintenance: "primary"
};

const BADGE_MODIFIERS_ALLOWED = new Set([
    "success",
    "info",
    "warning",
    "danger",
    "primary",
    "secondary",
    "neutral",
    "dark"
]);

// Opciones de filtrado comunes
export const FILTER_BY_OPTIONS = {
    ALL: {code: "all", label: "Todos"},
    INTERNAL_CODE: {code: "internal_code", label: "Código interno"},
    NAME: {code: "name", label: "Nombre"},
    DESCRIPTION: {code: "description", label: "Descripción"},
    DOCUMENT_NUMBER: {code: "document_number", label: "Número de documento"},
    EMAIL: {code: "email", label: "Correo electrónico"}
};

// Clases CSS comunes
export const CSS_CLASSES = {
    TITLE: "form-label colon-at-end",
    SELECT2: "bg-white",
    BADGE_BASE: ["br-badge"],
    STATUS_LABEL_BASE: ["br-badge"],
    BUTTON_PRIMARY: "btn btn-primary waves-effect",
    BUTTON_SUCCESS: "btn btn-success waves-effect",
    BUTTON_WARNING: "btn btn-warning waves-effect",
    BUTTON_DANGER: "btn btn-danger waves-effect",
    BUTTON_INFO: "btn btn-info-1 waves-effect",
    BUTTON_SECONDARY: "btn btn-secondary waves-effect"
};

/**
 * Clases para indicador de estado (etiqueta de marca).
 * @param {string} status — clave de estado (p. ej. STATUS.ACTIVE)
 * @param {Record<string, string>} customVariants — mapa estado → modificador permitido
 * @returns {string[]}
 */
export function getStatusBadgeVariant(status, customVariants = {}) {
    const variants = {...STATUS_BADGE_VARIANTS, ...customVariants};
    const raw = variants[status];

    return typeof raw === "string" && BADGE_MODIFIERS_ALLOWED.has(raw) ? raw : "secondary";
}

export function getStatusLabelClasses(status, customVariants = {}) {
    const variant = getStatusBadgeVariant(status, customVariants);
    const statusClass = String(status ?? "unknown")
        .trim()
        .toLowerCase()
        .replace(/[^a-z0-9_-]+/g, "-")
        .replace(/^-+|-+$/g, "") || "unknown";

    return [
        ...CSS_CLASSES.STATUS_LABEL_BASE,
        `br-status-${statusClass}`,
        `br-badge--${variant}`
    ];
}

// Textos comunes
export const TEXT = {
    SEARCH: "Buscar",
    ADD: "Agregar",
    EDIT: "Editar",
    DELETE: "Eliminar",
    SAVE: "Guardar",
    CANCEL: "Cancelar",
    CLOSE: "Cerrar",
    ACTIONS: "Acciones",
    DETAIL: "Detalle",
    FILTER_BY: "Filtrar por",
    SEARCH_PLACEHOLDER: "Buscar...",
    WITHOUT_DATA: "Sin registros",
    LOADING: "Cargando...",
    YES: "Sí",
    NO: "No"
};

// Configuración de modales
export const MODAL_CONFIG = {
    BACKDROP: "static",
    KEYBOARD: false,
    CENTERED: true
};

// Configuración de paginación
export const PAGINATION = {
    PER_PAGE_OPTIONS: [10, 25, 50, 100],
    DEFAULT_PER_PAGE: 10
};

// Tipos de documentos
export const DOCUMENT_TYPES = {
    DNI: "dni",
    RUC: "ruc",
    CE: "ce",
    PASSPORT: "passport"
};

// Géneros
export const GENDERS = {
    MALE: {code: "male", label: "Masculino"},
    FEMALE: {code: "female", label: "Femenino"},
    OTHER: {code: "other", label: "Otro"}
};

// Tipos de gestión de activos
export const ASSET_MANAGEMENT_TYPES = {
    STOCK: "stock",
    UNIT: "unit"
};

// Rutas de entidades comunes
export const ENTITY_ROUTES = {
    DASHBOARD: "dashboard",
    USERS: "users",
    COMPANIES: "companies",
    BRANCHES: "branches",
    CUSTOMERS: "customers",
    SALES: "sales",
    ASSETS: "assets",
    ASSETS_MANAGEMENT: "assets_management",
    TRACKING_ATTENDANCES: "tracking_attendances",
    TRACKING_CUSTOMERS: "tracking_customers",
    TRACKING_SUBSCRIPTIONS: "tracking_subscriptions",
    TRACKING_NOTIFICATIONS: "tracking_notifications",
    STOCKS_MANAGEMENT: "stocks_management",
    INVENTORY: "stocks_management",
    REPORTS: "reports"
};

// Configuración de validación común
export const VALIDATION = {
    REQUIRED: "required",
    EMAIL: "email",
    URL: "url",
    NUMBER: "number",
    MIN_LENGTH: "minLength",
    MAX_LENGTH: "maxLength"
};

// Mensajes de confirmación comunes
export const CONFIRM_MESSAGES = {
    DELETE: "¿Está seguro de eliminar este registro?",
    CANCEL: "¿Está seguro de cancelar esta operación?",
    UNASSIGN: "¿Desea quitar este elemento?",
    ASSIGN: "¿Desea agregar este elemento?"
};

