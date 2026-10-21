import axios from "axios";
import * as Alerts from "./Alerts.js";
import * as Utils from "./Utils.js";
import { requestRoute, generalConfig, applyGeneralConfig } from "./Constants.js";

const DEFAULT_GET_TIMEOUT_MS = 20000;

export function config({entity = "", type = "", extras = null}) {

    let baseRoute = `${requestRoute}`;

    let config = {
        routes: generateRoutes({entity, requestRoute: baseRoute})
    };

    if(Utils.isDefined({value: type})) {

        return config?.routes[type];

    }else {

        return config;

    }

}

export function get({route = "", data = {}, showAlert = false}) {

	return new Promise((resolve, reject) => {

        let requestUrl    = route,
            requestConfig = {timeout: DEFAULT_GET_TIMEOUT_MS};

        let params = {...data};

        requestConfig.params = params;

		axios
		.get(requestUrl, requestConfig)
		.then(response => {

            applyGeneralConfig(response.data?.config?.generalConfig);

			resolve({data: response.data, bool: true});

		})
        .catch(error => {

            const status = error?.response?.status;
            const errorData = error?.response?.data;
            const errorMessage = error?.code === "ECONNABORTED"
                ? "La consulta tardó demasiado. Intenta nuevamente."
                : errorData?.msg || errorData?.message || error?.message || error;

            if(showAlert) {

                Alerts.swals({show: false});

                if([500].includes(status)) {

                    Alerts.toastrs({type: "error", subtitle: errorData?.message || errorMessage});

                }else {

                    Alerts.toastrs({type: "error", subtitle: errorMessage});

                }

            }

			resolve({data: errorData || {data: [], msg: errorMessage}, bool: false, code: status});

		})
		.finally(() => {

			Alerts.tooltips({});

		});

	});

}

export async function download({
    route = "",
    data = {},
    fileName = "reporte.xlsx",
    showAlert = false
}) {

    try {

        const response = await axios.get(route, {
            params: {...data},
            responseType: "blob"
        });
        const objectUrl = window.URL.createObjectURL(response.data);
        const link = document.createElement("a");

        link.href = objectUrl;
        link.download = resolveDownloadFileName(response.headers?.["content-disposition"], fileName);
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.URL.revokeObjectURL(objectUrl);

        return {data: response.data, bool: true};

    }catch(error) {

        const status = error?.response?.status;
        const errorMessage = await resolveBlobErrorMessage(error);

        if(showAlert) {

            Alerts.toastrs({
                type: "error",
                subtitle: errorMessage || "No fue posible descargar el archivo."
            });

        }

        return {
            data: {msg: errorMessage},
            bool: false,
            code: status
        };

    }finally {

        Alerts.tooltips({});

    }

}

function resolveDownloadFileName(contentDisposition, fallback) {

    if(!contentDisposition) return fallback;

    const utf8Match = contentDisposition.match(/filename\*=UTF-8''([^;]+)/i);
    const basicMatch = contentDisposition.match(/filename="?([^";]+)"?/i);
    const encodedName = utf8Match?.[1] ?? basicMatch?.[1];

    if(!encodedName) return fallback;

    try {

        return decodeURIComponent(encodedName);

    }catch(error) {

        return encodedName;

    }

}

async function resolveBlobErrorMessage(error) {

    const responseData = error?.response?.data;

    if(responseData instanceof Blob) {

        try {

            const payload = JSON.parse(await responseData.text());

            return payload?.msg || payload?.message || error?.message;

        }catch(parseError) {

            return error?.message;

        }

    }

    return responseData?.msg || responseData?.message || error?.message;

}

export function post({route = "", data = {}, id = "", formData = null}) {

	return new Promise((resolve, reject) => {

		let requestURL  = route,
			requestData = formData ?? {...data, id};

        if(!Utils.isDefined({value: requestData?.id})) {

            delete requestData.id;

        }

		axios
		.post(requestURL, requestData)
		.then(response => {

			resolve({data: response.data, bool: true});

		})
		.catch(error => {

            const status = error?.response?.status;
            const errorData = error?.response?.data;
            const errorMessage = errorData?.msg || errorData?.message || error?.message || error;

            if([405].includes(status)) {

                resolve({data: {msg: `${errorData?.message || errorMessage} (Code ${status})`}, bool: false, code: status});

            }else if([422].includes(status)) {

                resolve({data: {msg: `${errorData?.message || errorMessage} (Code ${status})`}, errors: errorData?.errors, bool: false, code: status});

            }else if([404].includes(status)) {

                resolve({data: errorData || {msg: errorMessage}, bool: false, code: status});

            }else {

                resolve({data: errorData || {msg: errorMessage}, bool: false, code: status});

            }

		})
		.finally(() => {

			Alerts.tooltips({});

		});

	});

}

export function patch({route = "", data = {}, id = "", formData = null}) {

	return new Promise((resolve, reject) => {

		let requestURL  = Utils.isDefined({value: id}) ? `${route}/${id}` : route,
			requestData = formData ?? {...data, id};

        if(!Utils.isDefined({value: requestData?.id})) {

            delete requestData.id;

        }

        const isFormData = formData !== null;
        const method = isFormData ? "POST" : "PATCH";
        const requestConfig = isFormData  ? { headers: { "Content-Type": "multipart/form-data" } } : { headers: { "Content-Type": "application/json" } };

        if(isFormData) {

			formData.append("_method", "PATCH");

		}

		axios({
            method: method,
            url: requestURL,
            data: requestData,
            headers: requestConfig.headers
        })
		.then(response => {

			resolve({data: response.data, bool: true});

		})
		.catch(error => {

            const status = error?.response?.status;
            const errorData = error?.response?.data;
            const errorMessage = errorData?.msg || errorData?.message || error?.message || error;

            if([405].includes(status)) {

                resolve({data: {msg: `${errorData?.message || errorMessage} (Code ${status})`}, bool: false, code: status});

            }else if([422].includes(status)) {

                resolve({data: {msg: `${errorData?.message || errorMessage} (Code ${status})`}, errors: errorData?.errors, bool: false, code: status});

            }else if([404].includes(status)) {

                resolve({data: errorData || {msg: errorMessage}, bool: false, code: status});

            }else {

                resolve({data: errorData || {msg: errorMessage}, bool: false, code: status});

            }

		})
		.finally(() => {

			Alerts.tooltips({});

		});

	});

}

export function valid({result}) {

    return result?.bool && result?.data?.bool;

}

// Configuración de rutas especiales por entidad
const ENTITY_SPECIAL_ROUTES = {
    dashboard: {
        initData: "initData"
    },
    sales: {
        cancel: "cancel",
        deliveries: "deliveries",
        deliver: "deliveries"
    },
    tracking_subscriptions: {
        cancel: "cancel",
        manual: "manual"
    },
    tracking_attendances: {
        export: "export",
        cancel: "cancel",
        qrCamera: "qrCamera",
        qrScanner: "qrScanner"
    },
    tracking_customers: {
        getTracking: "getTracking"
    },
    customers: {
        import: "import",
        getSubscriptions: "getSubscriptions",
        registerBiometricFingerprint: "registerBiometricFingerprint"
    },
    assets_management: {
        assignAssetToBranch: "assignAssetToBranch",
        unassignAssetFromBranch: "unassignAssetFromBranch",
        getAssetAssignments: "getAssetAssignments",
        assetInBranch: "assetInBranch",
        assignToUser: "assignToUser",
        unassignToUser: "unassignToUser"
    },
    reports: {
        sale: "sale",
        saleShareLink: "sale/share-link"
    },
    stocks_management: {
        summary: "summary",
        alerts: "alerts",
        operationTemplate: "operations/template",
        operationPreview: "operations/preview",
        guides: "guides",
        movements: "movements",
        operations: "operations",
        transfers: "transfers"
    },
    cash_sessions: {
        open: "open",
        close: "close"
    },
    cash_summary: {
        data: "data"
    },
    branches: {
        seriesAudit: "series/audit",
        seriesAuditExport: "series/audit/export"
    },
    user_attendances: {
        export: "export",
        weekly: "weekly-summary",
        checkIn: "check-in",
        biometricCheckIn: "biometric/check-in",
        checkOut: "check-out",
        breakStart: "breaks",
        breakEnd: "breaks/end",
        correction: "corrections",
        correctionReview: "corrections"
    },
    users: {
        import: "import",
        password: "password",
        authenticationEvents: "authentication-events",
        registerBiometricFingerprint: "biometric-fingerprints"
    },
    biometric_devices: {
        credentials: "credentials",
        events: "events"
    },
    service_operations: {
        board: "board",
        options: "options",
        floors: "floors",
        stations: "stations",
        sessions: "sessions",
        reports: "reports"
    },
    purchases: {
        receive: "receive",
        cancel: "cancel"
    },
    products: {
        import: "import"
    },
    services: {
        import: "import"
    },
    subscriptions: {
        import: "import"
    },
    brands: {
        import: "import"
    },
    categories: {
        import: "import"
    },
    helpers: {
        searchDocumentNumber: "searchDocumentNumber",
        sendEmail: "sendEmail"
    }
};

export function generateRoutes({entity, requestRoute}) {

    const baseRoutes = {
        consult: `${requestRoute}/${entity}`,
        list: `${requestRoute}/${entity}/list`,
        export: `${requestRoute}/${entity}/export`,
        get: `${requestRoute}/${entity}/get`,
        create: `${requestRoute}/${entity}/create`,
        store: `${requestRoute}/${entity}`,
        update: `${requestRoute}/${entity}`,
        initParams: `${requestRoute}/${entity}/initParams`
    };

    // Agregar rutas especiales si existen
    const specialRoutes = ENTITY_SPECIAL_ROUTES[entity];
    if (specialRoutes) {
        Object.keys(specialRoutes).forEach(key => {
            baseRoutes[key] = `${requestRoute}/${entity}/${specialRoutes[key]}`;
        });
    }

    return baseRoutes;

}

export function routeReport({resource, params = null, extras = null}) {

    let route = `${config({entity: "reports", type: resource})}`;

    const url = new URL(route);
    const searchParams = url.searchParams;

    Object.keys(params).forEach(key => {

        searchParams.append(key, btoa(params[key]));

    });

    if(["reportSale"].includes(extras?.action)) {

        const expirationDate = Utils.addDuration({startDate: new Date(), type: "month", quantity: 3});

        searchParams.append("expdt", btoa(expirationDate));

    }

    return url.toString();

}

export async function saleReportShareUrl({document = null, type = "a4"} = {}) {

    const route = config({entity: "reports", type: "saleShareLink"});
    const response = await get({
        route,
        data: {document, type},
        showAlert: false
    });

    if(valid({result: response}) && response?.data?.data?.url) {

        return response.data.data.url;

    }

    return routeReport({
        resource: "sale",
        params: {document, type},
        extras: {action: "reportSale"}
    });

}
