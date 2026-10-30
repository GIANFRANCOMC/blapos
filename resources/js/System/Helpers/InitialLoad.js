import * as Alerts from "./Alerts.js";

/** El skeleton es optativo; el comportamiento habitual sigue siendo el Swal de initParams. */
export function createInitialLoad({skeleton = false} = {}) {
    return {
        skeleton,
        start() {
            if(!skeleton) Alerts.swals({type: "initParams"});
        },
        finish() {
            if(!skeleton) Alerts.swals({show: false});
        }
    };
}
