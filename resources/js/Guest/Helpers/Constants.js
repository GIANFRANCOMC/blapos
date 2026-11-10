import * as Utils from "./Utils.js";
import {normalizePrecision} from "../../Shared/DecimalPrecision.js";

export const requestRoute = `${window.location.protocol}//${window.location.hostname}`;

export const generalConfig = {
    project: {
        company: "BLAPOS"
    },
    essential: Utils.getEssential(),
    messages: {
        withoutResults: "Sin registros",
        errorValidate: "Error al validar, revisar el formulario."
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
            maxValue: 999999999999.999
        },
        errors: {
            labels: {
                required: "Es obligatorio",
                min_number_0: "Debe ser mayor a 0",
                min_equal_number_0: "Debe ser mayor o igual a 0"
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
                bodyColor: "#6f6b7d",
                labelColor: "#7a7883",
                borderColor: "#dbdade",
                titleColor: "#5d596c",
                defaultColor: "#1da1f2",
                primaryColor: "#7367F0",
                successColor: "#28C76F",
                dangerColor: "#EA5455"
            }
        }
    }
};
