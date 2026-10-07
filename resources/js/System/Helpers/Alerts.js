import { requestRoute } from "./Constants.js";
import {LOADER_IMAGE_SRC} from "./BrandAssets.js";

/** Paleta para `swals()` (alineada con `br-branding.css`). */
const SWAL_BR = {
    primary: "#2899e5",
    secondary: "#1a1a35",
    textMuted: "#64748b",
    surface: "#ffffff",
    shadow: "0 4px 18px rgba(26, 26, 53, 0.1)"
};

const SWAL_INSTANT_TRANSITION = Object.freeze({
    animation: false,
    showClass: {
        popup: "",
        icon: ""
    },
    hideClass: {
        popup: "",
        icon: ""
    }
});

function prepareModal(modalElement) {

    if(!(modalElement instanceof HTMLElement)) return;

    modalElement.setAttribute("data-bs-backdrop", "static");
    modalElement.setAttribute("data-bs-keyboard", "false");
    modalElement.classList.add("br-modal-standard");

    const modalBody = modalElement.querySelector(".modal-body");

    if(modalBody && !modalBody.querySelector(".br-entity-tabs-shell")) {

        modalBody.classList.add("br-modal-standard__body");

    }

    const instance = window.bootstrap?.Modal?.getInstance(modalElement);

    if(instance?._config) {

        instance._config.backdrop = "static";
        instance._config.keyboard = false;

    }

}

if(typeof document !== "undefined") {

    document.addEventListener("click", event => {

        const trigger = event.target.closest?.('[data-bs-toggle="modal"]');
        const selector = trigger?.getAttribute("data-bs-target") || trigger?.getAttribute("href");

        if(selector?.startsWith("#")) prepareModal(document.querySelector(selector));

    }, true);

    document.addEventListener("show.bs.modal", event => prepareModal(event.target), true);

}

/** Layout y tiempos del loader circular (solo `buildSwalLoadingHtml`). */
const SWAL_LOAD = Object.freeze({
    segments: 20,
    chaseCycleS: 2.8,
    holdEndPct: 86,
    resetStartPct: 91,
    buildPhaseEndPct: 78,
    orbitPx: 40
});

function swalBrPrimaryRgbCsv() {
    const n = Number.parseInt(SWAL_BR.primary.slice(1), 16);
    return `${(n >> 16) & 255},${(n >> 8) & 255},${n & 255}`;
}

/** Keyframes acumulativos; radio vía `--br-swal-orbit` en `.br-swal-load`. */
function buildSwalAccumKeyframes(segmentCount, primaryRgb) {

    const {buildPhaseEndPct, holdEndPct, resetStartPct} = SWAL_LOAD;
    const baseT = "rotate(var(--br-t)) translateY(calc(-1 * var(--br-swal-orbit)))";
    const dim = `opacity:0.2;transform:${baseT} scale(0.78);box-shadow:none;filter:brightness(0.95)`;
    const bright = `opacity:1;transform:${baseT} scale(1.06);box-shadow:0 0 14px rgba(${primaryRgb},0.55),0 0 4px rgba(${primaryRgb},0.35);filter:brightness(1)`;

    if(segmentCount <= 1) {

        return `@keyframes brSwalAccum0{0%,100%{${dim}}2%,${holdEndPct}%{${bright}}${resetStartPct}%,100%{${dim}}}`;

    }

    const stepOn = buildPhaseEndPct / (segmentCount - 1);

    let out = "";

    for(let i = 0; i < segmentCount; i++) {

        if(i === 0) {

            out += `@keyframes brSwalAccum0{0%,100%{${dim}}0.55%,${holdEndPct}%{${bright}}${resetStartPct}%,100%{${dim}}}`;
            continue;

        }

        const onPct = Number((i * stepOn).toFixed(3));
        const beforeOn = Number(Math.max(0, onPct - 0.09).toFixed(3));
        out += `@keyframes brSwalAccum${i}{0%,100%{${dim}}${beforeOn}%{${dim}}${onPct}%,${holdEndPct}%{${bright}}${resetStartPct}%,100%{${dim}}}`;

    }

    return out;

}

let swalLoadStaticPrefix = null;

/** Estilos + ticks del loader (constantes; se construye una vez por carga del módulo). */
function getSwalLoadStaticPrefix() {

    if(swalLoadStaticPrefix !== null) {

        return swalLoadStaticPrefix;

    }

    const keyframes = "";
    const ticks = "";

    swalLoadStaticPrefix = {
        styleAndTicks: `
                <style>
                    .br-swal-load-popup.swal2-popup {
                        width: min(21rem, calc(100vw - 1.25rem));
                        padding: 1.35rem 1.5rem 1.2rem;
                        border-top: 3px solid ${SWAL_BR.secondary};
                        border-radius: 0.5rem;
                        box-shadow: 0 1rem 2.6rem rgba(26, 26, 53, 0.2);
                    }
                    .br-swal-load-popup .swal2-html-container {
                        margin: 0;
                        padding: 0;
                    }
                    .br-swal-load__wrap {
                        padding: 0.15rem 0.25rem 0.25rem;
                        text-align: center;
                        font-family: inherit;
                    }
                    .br-swal-load {
                        position: relative;
                        display: grid;
                        place-items: center;
                        width: 88px;
                        height: 88px;
                        margin: 0 auto 0.9rem;
                    }
                    .br-swal-load__orbit {
                        position: absolute;
                        inset: 1px;
                        border: 3px solid transparent;
                        border-top-color: ${SWAL_BR.primary};
                        border-right-color: rgba(40, 153, 229, 0.32);
                        border-radius: 50%;
                        animation: brSwalOrbitPrimary 0.9s linear infinite;
                    }
                    .br-swal-load__orbit::after {
                        content: "";
                        position: absolute;
                        inset: 7px;
                        border: 2px solid transparent;
                        border-bottom-color: ${SWAL_BR.secondary};
                        border-left-color: rgba(26, 26, 53, 0.25);
                        border-radius: 50%;
                        animation: brSwalOrbitSecondary 1.25s linear infinite;
                    }
                    .br-swal-load__tick {
                        display: none;
                    }
                    .br-swal-load__logo-wrap {
                        z-index: 1;
                        display: grid;
                        place-items: center;
                        width: 62px;
                        height: 62px;
                        border-radius: 50%;
                        background: ${SWAL_BR.surface};
                        box-shadow: ${SWAL_BR.shadow}, 0 0 0 1px rgba(26, 26, 53, 0.06);
                        animation: brSwalLogoBreathe 1.8s ease-in-out infinite;
                    }
                    .br-swal-load__logo {
                        width: 43px;
                        height: 43px;
                        object-fit: contain;
                        display: block;
                        transform-origin: center center;
                        animation: brSwalLogoFloat 1.8s ease-in-out infinite;
                    }
                    .br-swal-load-msg__title {
                        margin: 0 0 0.3rem;
                        font-size: 0.98rem;
                        font-weight: 700;
                        line-height: 1.35;
                        color: ${SWAL_BR.secondary};
                        letter-spacing: 0;
                    }
                    .br-swal-load-msg__hint {
                        margin: 0;
                        font-size: 0.76rem;
                        font-weight: 400;
                        line-height: 1.4;
                        color: ${SWAL_BR.textMuted};
                        max-width: 17rem;
                        margin-inline: auto;
                    }
                    .br-swal-load__progress {
                        display: inline-flex;
                        align-items: center;
                        justify-content: center;
                        gap: 0.3rem;
                        margin-top: 0.8rem;
                    }
                    .br-swal-load__progress span {
                        width: 0.34rem;
                        height: 0.34rem;
                        border-radius: 50%;
                        background: ${SWAL_BR.primary};
                        opacity: 0.28;
                        animation: brSwalProgress 1.1s ease-in-out infinite;
                    }
                    .br-swal-load__progress span:nth-child(2) {
                        animation-delay: 0.16s;
                    }
                    .br-swal-load__progress span:nth-child(3) {
                        background: ${SWAL_BR.secondary};
                        animation-delay: 0.32s;
                    }
                    @keyframes brSwalOrbitPrimary {
                        to { transform: rotate(360deg); }
                    }
                    @keyframes brSwalOrbitSecondary {
                        to { transform: rotate(-360deg); }
                    }
                    @keyframes brSwalLogoBreathe {
                        0%, 100% { box-shadow: ${SWAL_BR.shadow}, 0 0 0 1px rgba(26, 26, 53, 0.06); }
                        50% { box-shadow: 0 6px 20px rgba(40, 153, 229, 0.18), 0 0 0 4px rgba(40, 153, 229, 0.08); }
                    }
                    @keyframes brSwalLogoFloat {
                        0%, 100% { transform: translateY(0) scale(1); }
                        50% { transform: translateY(-1px) scale(1.035); }
                    }
                    @keyframes brSwalProgress {
                        0%, 100% { opacity: 0.24; transform: translateY(0) scale(0.86); }
                        50% { opacity: 1; transform: translateY(-2px) scale(1); }
                    }
                    @media (prefers-reduced-motion: reduce) {
                        .br-swal-load__orbit,
                        .br-swal-load__orbit::after,
                        .br-swal-load__logo-wrap,
                        .br-swal-load__logo,
                        .br-swal-load__progress span {
                            animation-duration: 2.4s;
                        }
                    }
                    ${keyframes}
                </style>
                <div class="br-swal-load__wrap">
                    <div class="br-swal-load" role="status" aria-live="polite" aria-busy="true">
                        <div class="br-swal-load__orbit" aria-hidden="true">${ticks}</div>
                        <div class="br-swal-load__logo-wrap">
            `,
        logoSuffix: `
                        </div>
                    </div>
                    <div class="br-swal-load-msg">
            `
    };

    return swalLoadStaticPrefix;

}

/** HTML del Swal de carga (solo lo usa `swals()`). */
function buildSwalLoadingHtml({message, logoSrc}) {

    const resolvedTitle = message.trim().replace(/\.\s*$/, "") || "Preparando informaci\u00f3n";

    return `
        <div class="br-swal-load__wrap" role="status" aria-live="polite" aria-busy="true">
            <div class="br-swal-load__logo-stage" aria-hidden="true">
                <img
                    src="${logoSrc}"
                    alt=""
                    class="br-swal-load__logo"
                    width="52"
                    height="52"
                    decoding="async">
            </div>
            <div class="br-swal-load-msg">
                <p class="br-swal-load-msg__title">${resolvedTitle}</p>
                <p class="br-swal-load-msg__hint">Espera un momento, por favor.</p>
                <div class="br-swal-load__progress" aria-hidden="true">
                    <span></span><span></span><span></span>
                </div>
            </div>
        </div>
    `;

    /*
     * Legacy markup kept temporarily below for compatibility auditing.
     * The early return above is the active implementation.
     */
    const {styleAndTicks, logoSuffix} = getSwalLoadStaticPrefix();
    const titleLine = message.trim().replace(/\.\s*$/, "") || "Preparando información";
    const titleHtml = `<p class="br-swal-load-msg__title">${titleLine}</p>`;

    return `${styleAndTicks}<img src="${logoSrc}" alt="" class="br-swal-load__logo" width="52" height="52" decoding="async">${logoSuffix}${titleHtml}
                        <p class="br-swal-load-msg__hint">Estamos procesando la información. No cierres esta ventana.</p>
                        <div class="br-swal-load__progress" aria-hidden="true">
                            <span></span><span></span><span></span>
                        </div>
                    </div>
                </div>
            `.replace(/\u00c3\u00b3/g, "\u00f3");

}

function resolveSwalLoadingTitle({type, title, entity}) {

    if(title) return String(title).trim();

    const entityName = String(entity ?? "").trim();
    const actionTitles = {
        add: "Agregando",
        create: "Agregando",
        edit: "Editando",
        update: "Editando",
        delete: "Eliminando",
        remove: "Eliminando",
        save: "Guardando",
        search: "Buscando",
        consult: "Consultando informaci\u00f3n",
        initParams: "Preparando informaci\u00f3n",
        default: "Cargando"
    };
    const actionTitle = actionTitles[type] ?? actionTitles.default;

    return entityName ? `${actionTitle} ${entityName}` : actionTitle;

}

export function swals({
    show = true,
    type = "default",
    timeout = 0,
    title = null,
    entity = null
}) {

    if(show) {

        let message = "";

        switch(type) {
            case "default":
                message = "Cargando.";
                break;

            case "consult":
                message = "Consultando información.";
                break;

            case "initParams":
                message = "";
                break;
        }

        message = resolveSwalLoadingTitle({type, title, entity});

        Swal.fire({
            ...SWAL_INSTANT_TRANSITION,
            target: document.body,
            html: buildSwalLoadingHtml({message, logoSrc: LOADER_IMAGE_SRC}),
            allowOutsideClick: false,
            allowEscapeKey: false,
            allowEnterKey: false,
            stopKeydownPropagation: true,
            showConfirmButton: false,
            customClass: {
                container: "br-swal-top-layer",
                popup: "br-swal-load-popup",
                htmlContainer: "br-swal-load-html"
            },
            didOpen: () => {

                const container = Swal.getContainer();

                container?.style.setProperty("z-index", "2147483647", "important");

            }
        });

    }else {

        timeout > 0 ? setTimeout(() => Swal.close(), timeout) : Swal.close();

    }

}

/**
 * Alias semántico para procesos asincrónicos de interfaz.
 * Conserva un único loader global y evita que cada módulo implemente el suyo.
 */
export function loading({message = "", type = "default", title = null, entity = null} = {}) {

    swals({
        show: true,
        type,
        title: title || message || null,
        entity
    });

}

export function close() {

    swals({show: false});

}

export function toastrs({type = "success", options = null, code = null, title = null, subtitle = null}) {

    let toastrOptions = {};

    if(!title) {

        switch(type) {
            case "error":
                title = "¡Ups! Algo salió mal";
                break;

            case "success":
                title = "Exitoso";
                break;

            case "warning":
                title = "Atención";
                break;
        }

    }

    if(!options) {

        toastrOptions = {
            closeButton: true,
            progressBar: true,
            positionClass: "toast-top-right",
            showMethod: "slideDown",
            timeOut: 2000
        };

    }

    toastr.options = toastrOptions;
    toastr[type](subtitle ?? "", title ?? "");

}

/**
 * Controla los tooltips de Bootstrap 5 (misma API que inicializa `main.js` con `bootstrap.Tooltip`).
 *
 * @param {Object} options
 * @param {boolean} [options.show=true] — `false`: oculta y destruye tooltips; `true`: limpia residuos y crea instancias en el DOM actual.
 * @param {number} [options.time] — Retraso en ms (0 = inmediato). Si se omite: `10` con `show: true`, `0` con `show: false`.
 * @param {string} [options.selector='[data-bs-toggle="tooltip"]'] — Selectores de disparadores.
 */
function bootstrapTooltipOptions() {

    return {
        animation: false,
        boundary: "viewport",
        customClass: "br-tooltip",
        delay: {
            show: 0,
            hide: 0
        },
        trigger: "hover focus"
    };

}

/**
 * Recrea un tooltip con la configuración visual global.
 *
 * @param {HTMLElement|null} element
 * @param {string|null} title
 * @returns {Object|null}
 */
export function createTooltip(element, title = null) {

    const Bootstrap = window.bootstrap;

    if(!element || !Bootstrap?.Tooltip) return null;

    const currentTitle = title
        ?? element.getAttribute("title")
        ?? element.getAttribute("data-bs-original-title")
        ?? "";
    const existing = Bootstrap.Tooltip.getInstance(element);

    existing?.dispose();
    element.removeAttribute("data-bs-original-title");
    element.removeAttribute("aria-describedby");

    if(currentTitle) element.setAttribute("title", currentTitle);

    return new Bootstrap.Tooltip(element, bootstrapTooltipOptions());

}

export function tooltips(options = {}) {

    const {show = true, selector = "[data-bs-toggle=\"tooltip\"]"} = options;
    const time = options.time !== undefined ? options.time : (show ? 10 : 0);

    const run = () => {

        const Bootstrap = window.bootstrap;

        if(!Bootstrap?.Tooltip) {

            return;

        }

        const triggers = document.querySelectorAll(selector);

        if(show) {

            document.querySelectorAll(".tooltip").forEach((tooltip) => tooltip.remove());

            triggers.forEach((el) => {

                const currentTitle = el.getAttribute("title") ?? el.getAttribute("data-bs-original-title") ?? "";

                createTooltip(el, currentTitle);

            });

        }else {

            triggers.forEach((el) => {

                const existing = Bootstrap.Tooltip.getInstance(el);

                if(existing) {

                    existing.dispose();

                }

            });

            document.querySelectorAll(".tooltip").forEach((tooltip) => tooltip.remove());

        }

    };

    time > 0 ? setTimeout(run, time) : run();

}

/**
 * Oculta únicamente el tooltip asociado a un disparador y conserva las demás instancias.
 *
 * @param {HTMLElement|null} element
 */
export function dismissTooltip(element) {

    if(!element) return;

    try {

        const Bootstrap = window.bootstrap;
        const instance = Bootstrap?.Tooltip?.getInstance(element);

        element.blur();
        instance?.hide();

    }catch(error) {

        element.removeAttribute("aria-describedby");

    }

}

export function modals({type = "show", id = null, timeout = 0}) {

    const modalAction = () => {

        const modalElement = document.getElementById(id);
        const modal = $(modalElement);

        if(["show"].includes(type)) {

            prepareModal(modalElement);
            modal.modal("show");

        }else if(["hide"].includes(type)) {

            modal.modal("hide");

        }

    };

    timeout > 0 ? window.requestAnimationFrame(modalAction) : modalAction();

}

/**
 * Genera un alert de SweetAlert2 con formato optimizado
 * @param {Array} messages - Array de mensajes para mostrar en tabla
 * @param {String} type - Tipo de alert (success, error, warning, info)
 * @param {String} headerTitle - Título del header del alert
 * @param {String} msgContent - Contenido del mensaje (se envuelve automáticamente con div fw-semibold si no contiene HTML)
 * @param {Array} keys - Claves para la tabla de mensajes
 * @param {Number} width - Ancho del alert
 */
export function generateAlert({messages = [], type = "warning", headerTitle = null, msgContent = null, keys = [], width = 500}) {

	let tableAlertHtml = messages.length > 0 ? generateTableAlert({messages, type, keys}) : "";

	// Envolver msgContent automáticamente con div fw-semibold si es un string simple (sin HTML)
	let formattedMsgContent = msgContent;

	if(msgContent && typeof msgContent === "string") {

		const trimmedContent = msgContent.trim();
		const hasHtml = trimmedContent.startsWith("<") || trimmedContent.includes("<div") || trimmedContent.includes("<span") || trimmedContent.includes("<p");

		if(!hasHtml) {

			formattedMsgContent = `<div class="fw-semibold">${msgContent}</div>`;

		}

	}

	return Swal.fire({...SWAL_INSTANT_TRANSITION,
               title             : headerTitle,
               icon              : type,
		       allowOutsideClick : false,
		       allowEscapeKey    : false,
		       html              : `${formattedMsgContent ?? ""} <div>${tableAlertHtml}</div>`,
               width             : width,
               buttonsStyling    : false,
               confirmButtonText: "Entendido",
               customClass: {
                   container: "br-swal-backdrop",
                   popup: `br-swal-alert br-swal-alert--${type}`,
                   confirmButton: `br-btn br-swal-alert__confirm br-swal-alert__confirm--${type}`
               }});

}

export function generateTableAlert({messages, keys = []}) {

    let header = "",
        content = "";

    if(keys.length === 0) {

        header = `<tr class="text-center align-middle">
                        <td class="text-center text-nowrap fw-semibold">N°</td>
                        <td class="text-center text-nowrap fw-semibold">Mensaje</td>
                  </tr>`;

        content = `${messages.reduce((carry, singleMessage, index) => carry+/*html*/`
                    <tr>
                        <td class="text-center">${index + 1}</td>
                        <td class="text-start">${singleMessage}</td>
                    </tr>
                  `, "")}`;

    }else {

        header = `<tr class="text-center align-middle">
                        <td class="text-center text-nowrap fw-semibold">N°</td>
                        ${keys.reduce((carryKey, singleMessageKey, indexKey) => carryKey+/*html*/`
                            <td class="text-center text-nowrap fw-semibold">${singleMessageKey?.label ?? ""}</td>
                        `, "")}
                  </tr>`;

        content = `${messages.reduce((carry, singleMessage, index) => carry+/*html*/`
                    <tr>
                        <td class="text-center">${index + 1}</td>
                        ${keys.reduce((carryKey, singleMessageKey, indexKey) => carryKey+/*html*/`
                            <td class="text-start">${singleMessage.length > 0 ? (singleMessage[0][singleMessageKey?.column] ?? "") : ""}</td>
                        `, "")}
                    </tr>
                   `, "")}`;

    }

	let result = messages.length === 0 ? "" : `
	<table class="table table table-hover table-bordered mt-3">
		<thead class="table-light">
			${header}
		</thead>
		<tbody class="table-border-bottom-0 bg-white">
			${content}
		</tbody>
	</table>
	`;

	return result;

}
