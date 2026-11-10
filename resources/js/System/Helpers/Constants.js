import * as Utils from "./Utils.js";
import {normalizePrecision} from "../../Shared/DecimalPrecision.js";

export const requestRoute = `${window.location.protocol}//${window.location.hostname}`;

export const generalConfig = {
    project: {
        company: "BLAPOS"
    },
    essential: Utils.getEssential(),
    messages: {
        withoutResults: "No hay registros disponibles",
        errorValidate: "Por favor, revisar el formulario para continuar.",
        errorSearchValidate: "Para realizar la búsqueda, complete los siguientes campos:",
        catchError: "Ha ocurrido un error al realizar la acción.",
        errorValidateFields: "El formulario contiene errores de validación. Por favor, revise los campos marcados en rojo y corrija la información según se indique."
    },
    forms: {
        classes: {
            title: "form-label colon-at-end",
            select2: ""
        },
        inputs: {
            maxlength: 999,
            required: "*",
            round: normalizePrecision(window.decimalPrecision),
            minValue: 0,
            maxValue: 999999999999.999,
            maxSize: 4096
        },
        errors: {
            functions: {
                beetwen: {
                    numeric: (min = "", max = "") => `Debe estar entre ${min} y ${max}`
                },
                min: {
                    numeric: (min = "") => `Debe ser al menos ${min}`
                },
                max: {
                    numeric: (min = "") => `No debe ser mayor que ${min}`
                },
                maxSize: {
                    numeric: (max = "") => `El archivo debe pesar menos de ${max} MB`
                }
            },
            labels: {
                required: "Campo obligatorio.",
                min_number_0: "Debe ser mayor a 0",
                min_equal_number_0: "Debe ser mayor o igual a 0",
                not_valid_extension: "Extensión no válida"
            },
            styles: {
                default: "text-danger"
            }
        }
    },
    colors: {
        charts: {
            default: {
                backgroundColor: "#ffffff",
                bodyColor: "#64748b",
                labelColor: "#64748b",
                borderColor: "#e2e8f0",
                titleColor: "#1e293b",
                defaultColor: "#2899e5",
                primaryColor: "#2899e5",
                successColor: "#10b981",
                dangerColor: "#ef4444"
            }
        }
    },
    assets: {
        backgrounds: {
            images: {
                bg1: "/System/assets/img/utils/customers/carnet/1.png",
                bg2: "/System/assets/img/utils/customers/carnet/2.png"
            }
        }
    }
};

export function applyGeneralConfig(runtimeConfig = {}) {

    if(runtimeConfig?.forms?.inputs) {

        generalConfig.forms.inputs = {
            ...generalConfig.forms.inputs,
            ...runtimeConfig.forms.inputs
        };

    }

}
