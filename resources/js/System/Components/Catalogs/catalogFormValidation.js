import {compareNumbers, normalizeNumber} from "../../../Shared/Numbers.js";

export const OFFERING_CAPACITY_MAX = 1000000;

export function commissionRequiresValue(type) {
    return type === "percentage" || type === "fixed";
}

export function validateCatalogCommission(result, {commission_type, commission_value}) {
    const type = commission_type?.code ?? commission_type;
    if(!commissionRequiresValue(type) || result.errors.commission_value) return;

    if(compareNumbers(commission_value, 0) <= 0) {
        result.errors.commission_value = ["Ingresa un valor de comisión mayor que 0."];
    }else if(type === "percentage" && compareNumbers(commission_value, 100) > 0) {
        result.errors.commission_value = ["No puede superar el 100%."];
    }

    if(result.errors.commission_value) result.bool = false;
}

export function resetCatalogCommission(form) {
    form.data.commission_value = "";
    delete form.errors.commission_value;
}

export function validateCatalogCapacity(result, {capacity_control_enabled, capacity_limit}) {
    if(!capacity_control_enabled || result.errors.capacity_limit) return;

    const limit = normalizeNumber(capacity_limit);
    if(!Number.isInteger(limit) || limit < 1 || limit > OFFERING_CAPACITY_MAX) {
        result.errors.capacity_limit = ["Indica una cantidad entera de cupos entre 1 y 1,000,000."];
        result.bool = false;
    }
}
