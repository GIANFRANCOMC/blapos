import * as Alerts from "./Alerts.js";

/** Skeleton y Swal cubren initParams; finalizar ambos antes del listado. */
export function createInitialLoad({skeleton = true, swal = true} = {}) {
    return {
        skeleton,
        swal,
        start() {
            if(swal) Alerts.swals({type: "initParams"});
        },
        finish() {
            if(swal) Alerts.swals({show: false});
        }
    };
}
