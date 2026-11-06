import * as Alerts from "./Alerts.js";

/** Skeleton y Swal cubren initParams; finalizar antes del listado. Sin opciones se conserva solo Swal. */
export function createInitialLoad({skeleton = false, swal = !skeleton} = {}) {
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
