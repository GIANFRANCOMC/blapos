/**
 * Factory para crear módulos CRUD fácilmente
 * Simplifica la creación de nuevos módulos con configuración mínima
 */

import * as Requests from "./Requests.js";
import * as Constants from "./Constants.js";
import * as Utils from "./Utils.js";
import { CrudMixin } from "./CrudMixin.js";
import InitialPageMixin from "./InitialPageMixin.js";
import { FILTER_BY_OPTIONS, TEXT, CSS_CLASSES } from "./ModuleConstants.js";

/**
 * Crea la estructura inicial para un módulo CRUD (alias legacy)
 * @param {Object} config - Configuración {entity: string, menuId: string, pageTitle: string, pageTitleSingular: string}
 * @returns {Object} Estructura inicial del módulo
 */
export function initCrudModule(config = {}) {
    const entity = config.entity || "entity";
    const menuId = config.menuId || `menu-item-${entity}`;
    const pageTitle = config.pageTitle || "Entidad";
    const pageTitleSingular = config.pageTitleSingular || pageTitle;
    const modalTitle = pageTitleSingular.toUpperCase();

    return {
        lists: {
            [entity]: {
                extras: {
                    loading: false,
                    route: Requests.config({entity, type: "list"})
                },
                filters: {
                    filter_by: null,
                    word: ""
                },
                records: {
                    total: 0,
                    data: [],
                    current_page: 1,
                    last_page: 1,
                    links: []
                }
            }
        },
        forms: {
            [entity]: {
                createUpdate: {
                    extras: {
                        modals: {
                            default: {
                                id: Utils.uuid(),
                                titles: {
                                    store: `AGREGAR ${modalTitle}`,
                                    update: `EDITAR ${modalTitle}`
                                }
                            }
                        }
                    },
                    data: {},
                    errors: {}
                }
            }
        },
        options: {},
        config: {
            ...Constants.generalConfig,
            entity: {
                ...Requests.config({entity}),
                page: {
                    title: pageTitle,
                    active: true,
                    menu: {
                        id: menuId
                    }
                }
            }
        }
    };
}

/**
 * Crea la configuración base para un módulo Vue
 * @param {Object} config - Configuración del módulo
 * @returns {Object} Configuración para Vue component
 */
export function createModuleConfig(config = {}) {
    const {
        entity = "entity",
        menuId = `menu-${entity}`,
        pageTitle = "Entidad",
        parentMenuId = null,
        customRoutes = {},
        defaultFilters = {},
        defaultFormData = {}
    } = config;

    const entityRoutes = Requests.config({ entity });

    // Agregar rutas personalizadas
    if (Object.keys(customRoutes).length > 0) {
        Object.assign(entityRoutes.routes, customRoutes);
    }

    return {
        lists: {
            entity: {
                extras: {
                    loading: false,
                    route: entityRoutes.routes.list
                },
                filters: {
                    filter_by: null,
                    word: "",
                    ...defaultFilters
                },
                records: {
                    total: 0,
                    data: [],
                    current_page: 1,
                    last_page: 1,
                    links: []
                }
            }
        },
        forms: {
            entity: {
                createUpdate: {
                    extras: {
                        modals: {
                            default: {
                                id: Utils.uuid(),
                                titles: {
                                    store: `Agregar ${pageTitle}`,
                                    update: `Editar ${pageTitle}`
                                }
                            }
                        }
                    },
                    data: {
                        id: null,
                        ...defaultFormData
                    },
                    errors: {}
                }
            }
        },
        options: {},
        config: {
            ...Constants.generalConfig,
            entity: {
                ...entityRoutes,
                page: {
                    title: pageTitle,
                    active: true,
                    menu: {
                        id: menuId,
                        parentId: parentMenuId
                    }
                }
            }
        }
    };
}

/**
 * Crea opciones de filtrado estándar
 * @param {Array} customOptions - Opciones personalizadas adicionales
 * @returns {Array} Array de opciones de filtrado
 */
export function createFilterByOptions(customOptions = []) {
    const defaultOptions = [
        FILTER_BY_OPTIONS.ALL,
        FILTER_BY_OPTIONS.INTERNAL_CODE,
        FILTER_BY_OPTIONS.NAME,
        FILTER_BY_OPTIONS.DESCRIPTION
    ];

    return [...defaultOptions, ...customOptions];
}

/**
 * Inicializa un módulo Vue con configuración estándar
 * @param {Object} config - Configuración del módulo
 * @returns {Object} Objeto con métodos y datos para Vue
 */
export function initVueModule(config = {}) {
    const moduleConfig = createModuleConfig(config);

    return {
        mixins: [CrudMixin, InitialPageMixin],
        data() {
            return moduleConfig;
        },
        mounted: async function() {
            const { parentMenuId, menuId } = config;

            if (parentMenuId) {
                Utils.navbarItem(parentMenuId, { addClass: "open" });
            }

            Utils.navbarItem(menuId || moduleConfig.config.entity.page.menu.id, {});
            const initParams = await this.loadInitialParams(() => this.initParams({}));
            const initOthers = await this.initOthers({});

            if (initParams && initOthers && config.autoLoadList !== false) {
                await this.listEntity({});
            }
        },
        computed: {
            breadcrumbTitles() {
                const breadcrumbs = config.breadcrumbs || [];
                return [...breadcrumbs, moduleConfig.config.entity.page];
            },
            filterByOptions() {
                return createFilterByOptions(config.customFilterOptions || []);
            }
        }
    };
}

