/**
 * Mixin Vue para funcionalidades CRUD comunes
 * Puede ser usado en cualquier componente Vue que necesite funcionalidad CRUD
 */

import * as Requests from "./Requests.js";
import * as Alerts from "./Alerts.js";
import * as Utils from "./Utils.js";
import * as Constants from "./Constants.js";
import { getStatusLabelClasses } from "./ModuleConstants.js";

export const CrudMixin = {
    methods: {
        // Init
        async initParams({ page = "main" }) {
            const initParams = await Requests.get({
                route: this.config.entity.routes.initParams,
                data: { page },
                showAlert: true
            });

            if (Requests.valid({ result: initParams })) {
                this.options = { ...this.options, ...initParams.data?.config };
            }

            return Requests.valid({ result: initParams });
        },

        async initOthers({}) {
            return new Promise(resolve => resolve(true));
        },

        // Entity forms
        async listEntity({ url = null, filters = {} }) {
            const defaultFilters = {
                filter_by: this.lists?.entity?.filters?.filter_by?.code || "all",
                word: this.lists?.entity?.filters?.word || "",
                ...filters
            };

            this.lists.entity.extras.loading = true;
            this.lists.entity.records = Requests.emptyPagination();
            
            try {
                const response = await Requests.get({
                    route: url || this.lists.entity.extras.route,
                    data: defaultFilters
                });
                
                if (Requests.valid({ result: response })) {
                    this.lists.entity.records = response.data;
                }
            } finally {
                this.lists.entity.extras.loading = false;
            }
        },

        // Forms utils
        clearForm({ functionName }) {
            // Debe ser implementado en el componente
        },

        formErrors({ functionName, type = "clear", errors = [] }) {
            if (["modalCreateUpdateEntity", "createUpdateEntity"].includes(functionName)) {
                this.forms.entity.createUpdate.errors = ["set"].includes(type) ? errors : {};
            }
        },

        validateForm({ functionName, form = null, extras = null }) {
            // Debe ser implementado en el componente
            return { bool: true };
        },

        // Others
        isDefined({ value }) {
            return Utils.isDefined({ value });
        },

        isNumber({ value, minValue = 0 }) {
            return Utils.isNumber({ value, minValue });
        },

        tooltips({ show = true, time = 10 }) {
            Alerts.tooltips({ show, time });
        },

        getStatusBadgeClasses(status, customVariants = {}) {
            return getStatusLabelClasses(status, customVariants);
        },

        separatorNumber(value) {
            return Utils.separatorNumber(value);
        },

        fixedNumber(value, decimals = null) {
            return Utils.fixedNumber(value, decimals);
        },

        legibleFormatDate({ dateString = null, type = "datetime" }) {
            return Utils.legibleFormatDate({ dateString, type });
        },

        cloneJson(json) {
            return Utils.cloneJson(json);
        }
    },

    computed: {
        isUpdate() {
            return this.isDefined({ value: this.forms?.entity?.createUpdate?.data?.id });
        }
    }
};

