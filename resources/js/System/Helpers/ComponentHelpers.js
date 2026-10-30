/**
 * Helpers para componentes Vue
 * Funciones útiles para trabajar con componentes
 */

import * as Alerts from "./Alerts.js";
import * as Utils from "./Utils.js";
import * as Requests from "./Requests.js";
import { TEXT } from "./ModuleConstants.js";

/**
 * Crea acciones estándar para tablas
 */
export function createTableActions(actions = []) {
    const defaultActions = [
        {
            key: "edit",
            label: TEXT.EDIT,
            icon: "fa fa-pencil",
            class: "btn-warning"
        },
        {
            key: "delete",
            label: TEXT.DELETE,
            icon: "fa fa-trash",
            class: "btn-danger"
        }
    ];

    return [...defaultActions, ...actions];
}

/**
 * Maneja confirmación antes de acción
 */
export function confirmAction(message, onConfirm, onCancel = null) {
    Alerts.confirmAction({
        title: "Realizar acción",
        text: message,
        confirmButtonText: "Realizar acción"
    }).then(confirmed => {
        if (confirmed && onConfirm) {
            onConfirm();
        } else if (!confirmed && onCancel) {
            onCancel();
        }
    });
}


