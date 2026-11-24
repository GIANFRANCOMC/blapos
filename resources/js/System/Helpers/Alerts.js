import { requestRoute } from "./Constants.js";
import {tooltipOptionsFor} from "./TooltipOptions.js";
import {feedbackIconHtml, escapeFeedbackText} from "./Feedback.js";

const SWAL_INSTANT_TRANSITION = Object.freeze({
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
    modalElement.classList.add("fg-modal");

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

    document.addEventListener("show.bs.modal", event => {
        const modal = event.target;
        prepareModal(modal);

        const Tooltip = window.bootstrap?.Tooltip;
        if(!Tooltip) return;

        document.querySelectorAll("[aria-describedby]").forEach(trigger => {
            if(!modal.contains(trigger)) Tooltip.getInstance(trigger)?.hide();
        });
    }, true);

    document.addEventListener("hidden.bs.modal", () => {

        // Algunos formularios retiran los tooltips al abrirse; al cerrar se restauran
        // los disparadores de la página y de las demás modales desde un solo lugar.
        tooltips({time: 10});

    }, true);

}

/** HTML del Swal de carga; comparte el icono y la animación de Loader.vue. */
function buildSwalLoadingHtml({message}) {

    const title = String(message).trim().replace(/\.\s*$/, "") || "Preparando información";
    const safeTitle = title.replace(/[&<>"']/g, character => ({
        "&": "&amp;",
        "<": "&lt;",
        ">": "&gt;",
        '"': "&quot;",
        "'": "&#39;"
    })[character]);

    return '<div class="fg-swal__loading" role="status" aria-live="polite" aria-busy="true">' + feedbackIconHtml("loading", true) + '<h2>' + safeTitle + '</h2><p>Espera un momento, por favor.</p></div>';
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

        }

        message = resolveSwalLoadingTitle({type, title, entity});

        Swal.fire({
            ...SWAL_INSTANT_TRANSITION,
            target: document.body,
            html: buildSwalLoadingHtml({message}),
            allowOutsideClick: false,
            allowEscapeKey: false,
            allowEnterKey: false,
            stopKeydownPropagation: true,
            showConfirmButton: false,
            customClass: {
                container: "fg-swal-layer",
                popup: "fg-swal fg-swal--loading",
                htmlContainer: "fg-swal__html"
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

/** Confirmación reutilizable para acciones que alteran datos o inician procesos. */
export async function confirmAction(options) {
    return (await actionConfirmation(options)).isConfirmed;
}

export async function confirmActionWithReason(options) {
    const result = await actionConfirmation(options, {
        input: "textarea",
        inputLabel: "Motivo de cancelación (opcional)",
        inputAttributes: {maxlength: "255"},
        focusCancel: false,
        focusConfirm: false,
        didOpen: () => Swal.getInput()?.focus()
    });

    return result.isConfirmed ? String(result.value ?? "").trim() : null;
}

function actionConfirmation({
    title = "¿Confirmar operación?",
    text = "",
    icon = "question",
    confirmButtonText = "Realizar acción",
    target = document.body,
    actionIcon = null,
    scope = null
} = {}, inputOptions = {}) {

    return Swal.fire({
        ...SWAL_INSTANT_TRANSITION,
        allowEscapeKey: false,
        target,
        title,
        text,
        ...(actionIcon ? {icon: "info", iconHtml: feedbackIconHtml(actionIcon)} : {}),
        ...(scope ? {footer: escapeFeedbackText(scope)} : {}),
        showCancelButton: true,
        showCloseButton: true,
        closeButtonAriaLabel: "Cerrar confirmación",
        confirmButtonText: actionIcon === "export" ? '<span class="fg-button__content">' + feedbackIconHtml("exportWhite") + "<span>" + escapeFeedbackText(confirmButtonText) + "</span></span>" : confirmButtonText,
        cancelButtonText: "Cerrar",
        reverseButtons: true,
        focusCancel: true,
        allowOutsideClick: false,
        buttonsStyling: false,
        customClass: {
            container: "fg-swal-layer",
            popup: "fg-swal",
            confirmButton: "fg-button",
            cancelButton: "fg-button fg-button--secondary"
        },
        ...inputOptions
    });

}

function setMessageWidth(popup, width) {

    if(Number.isFinite(width) && width > 0) {

        popup.style.setProperty("--fg-swal-width", `${width}px`);

    }

}

/** Mensaje seguro: usa texto plano. `width` opcional, en píxeles, amplía el ancho compacto predeterminado. */
export function showMessage({title = "", text = "", type = "info", width = null, target = document.body} = {}) {

    return Swal.fire({
        ...SWAL_INSTANT_TRANSITION,
        allowEscapeKey: false,
        target,
        title,
        text,
        icon: type,
        iconHtml: feedbackIconHtml(type),
        willOpen: popup => setMessageWidth(popup, width),
        allowOutsideClick: false,
        buttonsStyling: false,
        confirmButtonText: "Entendido",
        customClass: {
            container: "fg-swal-layer",
            popup: "fg-swal",
            confirmButton: "fg-button"
        }
    });

}

export function showFiltersCleared() {

    toastrs({type: "info", title: "Se limpiaron los filtros correctamente."});

}

/** Confirma una descarga antes de iniciar la petición y mostrar el loader. */
export function confirmDownload({resource = "el archivo", format = "Excel"} = {}) {

    return confirmAction({
        title: `¿Descargar ${resource}?`,
        text: `Se generará un archivo ${format}. La descarga comenzará automáticamente cuando esté listo y puede tardar unos segundos.`,
        confirmButtonText: `Descargar ${format}`
    });

}

export function toastrs({type = "success", options = null, code = null, title = null, subtitle = null}) {

    if(!title) {

        switch(type) {
            case "error":
                title = "Error";
                break;

            case "success":
                title = "Listo";
                break;

            case "warning":
                title = "Atención";
                break;

            case "info":
                title = "Información";
                break;
        }

    }

    const reducedMotion = window.matchMedia?.("(prefers-reduced-motion: reduce)").matches ?? false;

    toastr.options = {
        closeButton: true,
        progressBar: true,
        positionClass: "toast-top-right",
        showMethod: "fadeIn",
        hideMethod: "fadeOut",
        showDuration: reducedMotion ? 0 : 160,
        hideDuration: reducedMotion ? 0 : 140,
        timeOut: ["error", "warning"].includes(type) ? 5500 : 4000,
        extendedTimeOut: 1500,
        ...options
    };

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

    return new Bootstrap.Tooltip(element, tooltipOptionsFor(element));

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

export function showStackedModal({id, parent, onHidden}) {
    const element = document.getElementById(id);
    const Modal = window.bootstrap?.Modal;
    const parentModal = parent && Modal?.getInstance(parent);
    if(!element || !parentModal || parent.inert || !parent.classList.contains("show")) return;

    prepareModal(element);
    // La segunda modal oscurece el fondo desde su propia capa para dejar el contenido por encima.
    element.classList.add("fg-modal--stacked");
    const modal = Modal.getOrCreateInstance(element, {backdrop: false});
    const scrollbar = modal._scrollBar;
    const {hide, reset} = scrollbar;

    // El modal padre conserva el bloqueo de desplazamiento mientras se muestra el segundo.
    scrollbar.hide = scrollbar.reset = () => {};
    parentModal._focustrap.deactivate();
    parent.inert = true;

    const restoreParent = () => {
        Object.assign(scrollbar, {hide, reset});
        parent.inert = false;
        if(!parent.classList.contains("show")) return;

        document.body.classList.add("modal-open");
        parentModal._focustrap.activate();
        onHidden?.();
    };

    element.addEventListener("hidden.bs.modal", restoreParent, {once: true});
    modal.show();

    if(!modal._isShown) {
        element.removeEventListener("hidden.bs.modal", restoreParent);
        restoreParent();
    }
}

/**
 * Genera un alert de SweetAlert2 con formato optimizado
 * @param {Array} messages - Array de mensajes para mostrar en tabla
 * @param {String} type - Tipo de alert (success, error, warning, info)
 * @param {String} headerTitle - Título del header del alert
 * @param {String} msgContent - Contenido del mensaje (se envuelve automáticamente con div fw-semibold si no contiene HTML)
 * @param {Array} keys - Claves para la tabla de mensajes
 * @param {Number|null} width - Ancho opcional en píxeles; por defecto usa el ancho compacto compartido
 */
export function generateAlert({messages = [], type = "warning", headerTitle = null, msgContent = null, keys = [], width = null} = {}) {

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
               iconHtml          : feedbackIconHtml(type),
		       willOpen          : popup => setMessageWidth(popup, width),
		       allowOutsideClick : false,
		       allowEscapeKey    : false,
		       html              : `${formattedMsgContent ?? ""} <div>${tableAlertHtml}</div>`,
               buttonsStyling    : false,
               confirmButtonText: "Entendido",
               customClass: {
                   container: "fg-swal-layer",
                   popup: "fg-swal",
                   confirmButton: "fg-button"
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
	<table class="fg-table fg-swal__table">
		<thead >
			${header}
		</thead>
		<tbody >
			${content}
		</tbody>
	</table>
	`;

	return result;

}
