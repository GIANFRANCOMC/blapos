/**
 * Utilidades comunes generales
 */

import { getStatusLabelClasses } from "./ModuleConstants.js";

/**
 * Obtiene información esencial de la aplicación
 * @returns {Object} Información esencial
 */
export function getEssential() {
    return {
        ownerApp: window?.ownerApp ?? null,
        user: window?.user ?? null,
        company: window?.company ?? null,
        sections: window?.sections ?? [],
        preferences: window?.preferences ?? []
    };
}

/**
 * Clona un objeto JSON (deep clone)
 * @param {*} json - Objeto a clonar
 * @returns {*} Objeto clonado
 */
export function cloneJson(json) {
    return JSON.parse(JSON.stringify(json));
}

/**
 * Genera un UUID
 * @param {number} length - Longitud del UUID
 * @returns {string} UUID generado
 */
export function uuid(length = 36) {
    return ([1e7] + -1e3 + -4e3 + -8e3 + -1e11).replace(/[018]/g, c =>
        (c ^ crypto.getRandomValues(new Uint8Array(1))[0] & 15 >> c / 4).toString(16)
    ).substr(0, length);
}

/**
 * Obtiene la ruta de un asset
 * @param {string} path - Ruta del asset
 * @param {Object} options - {type: string, back: number}
 * @returns {string} Ruta completa del asset
 */
export function getAsset(path, {type = "storage", back = 0}) {
    if (["storage"].includes(type)) {
        return `/${type}/${path}`;
    } else if (["public"].includes(type)) {
        return `/${type}/${path}`;
    } else if (["none"].includes(type)) {
        return back == 1 ? `../${path}` : `${path}`;
    }
}

/**
 * Obtiene clases CSS para badge de estado
 * @param {string} status - Estado
 * @param {Object} variants - Variantes personalizadas
 * @returns {Array} Array de clases CSS
 */
export function getStatusBadgeClasses(status, variants = {}) {
    return getStatusLabelClasses(status, variants);
}

