/**
 * Utilidades para manejo de números.
 */

import { generalConfig } from "./Constants.js";
import { isDefined } from "./ValidationUtils.js";

/**
 * Valida si un valor es un número válido.
 * @param {Object} options - {value: *, minValue: number}
 * @returns {boolean} true si es válido
 */
export function isNumber({value, minValue = 0, maxValue = null, allowEmpty = false}) {

    if(!isDefined({value})) {

        return allowEmpty;

    }

    const normalizedValue = typeof value === "string" ? value.trim().replace(/,/g, "") : value;

    if(normalizedValue === "") {

        return allowEmpty;

    }

    const number = Number(normalizedValue);

    if(!Number.isFinite(number) || number < Number(minValue)) {

        return false;

    }

    return !isDefined({value: maxValue}) || number <= Number(maxValue);

}

/**
 * Formatea un número con decimales fijos.
 * @param {number} value - Valor numérico
 * @param {number|null} decimals - Número de decimales (null usa el config por defecto)
 * @returns {string} Número formateado
 */
export function fixedNumber(value, decimals = null) {

    return normalizeNumber(value).toFixed(resolveDecimals(decimals));

}

/**
 * Formatea un número con separadores de miles y punto decimal.
 * @param {*} value - Valor a formatear
 * @param {number|null} decimals - Decimales a mostrar
 * @returns {string} Número formateado
 */
export function separatorNumber(value, decimals = null) {

    const decimalPlaces = resolveDecimals(decimals);

    return new Intl.NumberFormat("en-US", {
        minimumFractionDigits: decimalPlaces,
        maximumFractionDigits: decimalPlaces
    }).format(normalizeNumber(value));

}

export function quantityWithUnit(value, measurementUnit = null, decimals = null) {

    const number = normalizeNumber(value);
    const singular = measurementUnit?.singular_name || measurementUnit?.unitSingular || "UNIDAD";
    const plural = measurementUnit?.plural_name || measurementUnit?.unitPlural || "UNIDADES";
    const label = Math.abs(number) === 1 ? singular : plural;

    return `${separatorNumber(number, decimals)} ${label.toLocaleLowerCase("es")}`;

}

/**
 * Calcula el total de un item (cantidad * precio).
 * @param {Object} options - {item: {quantity: number, price: number}}
 * @returns {number} Total calculado
 */
export function calculateTotal({item}) {

    const quantity = isNumber({value: item?.quantity}) ? Number(String(item.quantity).replace(/,/g, "")) : 0;
    const price = isNumber({value: item?.price}) ? Number(String(item.price).replace(/,/g, "")) : 0;

    return fixedNumber(quantity * price);

}

export function resolveDecimals(decimals = null) {

    const value = decimals ?? generalConfig.forms.inputs.round;
    const parsed = Number(value);

    return Number.isFinite(parsed) ? Math.max(0, Math.min(8, Math.trunc(parsed))) : 3;

}

export function normalizeNumber(value) {

    if(!isDefined({value})) return 0;

    const normalized = typeof value === "string"
        ? value.trim().replace(/,/g, "")
        : value;

    const parsed = Number(normalized);

    return Number.isFinite(parsed) ? parsed : 0;

}
