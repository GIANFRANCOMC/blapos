import { requestRoute } from "./Constants.js";

export function swals({show = true, type = "default", timeout = 0}) {

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

            case "externalConsult":
                message = "Consultando.";
                break;

            case "list":
                message = "Cargando listado.";
                break;

            case "saveForm":
                message = "Guardando formulario.";
                break;
        }

        Swal.fire({
            allowEscapeKey: false,
            html: `
                <style>
                    @keyframes fadeInOutSwal {
                        0%, 100% { opacity: 0.2; }
                        50% { opacity: 1; }
                    }

                    .swal-logo {
                        width: 100px;
                        animation: fadeInOutSwal 2s infinite;
                        display: block;
                        margin: 10px auto;
                    }
                </style>
                <span class="h5">${message} Este proceso puede tomar algunos segundos, por favor espere.</span>
                <img src='${requestRoute}/System/assets/img/utils/spin.gif' class="img-fluid swal-logo mt-1 mb-0">
            `,
            allowOutsideClick: false,
            showConfirmButton: false
        });

    }else {

        timeout > 0 ? setTimeout(() => Swal.close(), timeout) : Swal.close();

    }

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
 * @param {boolean} [options.show=true] — `false`: oculta todos los tooltips abiertos (instancias siguen vivas); `true`: destruye y vuelve a crear instancias en el DOM actual.
 * @param {number} [options.time] — Retraso en ms (0 = inmediato). Si se omite: `10` con `show: true`, `0` con `show: false`.
 * @param {string} [options.selector='[data-bs-toggle="tooltip"]'] — Selectores de disparadores.
 */
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

            triggers.forEach((el) => {

                const existing = Bootstrap.Tooltip.getInstance(el);

                if(existing) {

                    existing.dispose();

                }

                new Bootstrap.Tooltip(el);

            });

        }else {

            triggers.forEach((el) => {

                const existing = Bootstrap.Tooltip.getInstance(el);

                if(existing) {

                    existing.hide();

                }

            });

        }

    };

    time > 0 ? setTimeout(run, time) : run();

}

export function modals({type = "show", id = null, timeout = 0}) {

    if(["show"].includes(type)) {

        timeout > 0 ? setTimeout(() => $(`#${id}`).modal("show"), timeout) : $(`#${id}`).modal("show");


    }else if(["hide"].includes(type)) {

        timeout > 0 ? setTimeout(() => $(`#${id}`).modal("hide"), timeout) : $(`#${id}`).modal("hide");

    }

}

export function generateAlert({messages = [], type = "warning", headerTitle = null, msgContent = null, keys = [], width = 550}) {

    let tableAlertHtml = messages.length > 0 ? generateTableAlert({messages, type, keys}) : "";

    Swal.fire({title             : headerTitle,
               icon              : type,
               allowOutsideClick : false,
               allowEscapeKey    : false,
               html              : `${msgContent ?? ""} <div>${tableAlertHtml}</div>`,
               width             : width,
               confirmButtonText: "Entendido",
               customClass: {
                   confirmButton: "btn btn-primary waves-effect"
               }});

}

export function generateTableAlert({messages, keys = []}) {

    let header = "",
        content = "";

    if(keys.length === 0) {

        header = `<tr class="text-center align-middle">
                        <td class="text-center">N°</td>
                        <td class="text-center">Mensaje</td>
                  </tr>`;

        content = `${messages.reduce((carry, singleMessage, index)=>carry+/*html*/`
                    <tr>
                        <td class="text-center">${index + 1}</td>
                        <td class="text-start">${singleMessage}</td>
                    </tr>
                  `, "")}`;

    }else {

        header = `<tr class="text-center align-middle">
                        <td class="text-center">N°</td>
                        ${keys.reduce((carryKey, singleMessageKey, indexKey)=>carryKey+/*html*/`
                            <td class="text-center">${singleMessageKey?.label ?? ""}</td>
                        `, "")}
                  </tr>`;

        content = `${messages.reduce((carry, singleMessage, index)=>carry+/*html*/`
                    <tr>
                        <td class="text-center">${index + 1}</td>
                        ${keys.reduce((carryKey, singleMessageKey, indexKey)=>carryKey+/*html*/`
                            <td class="text-start">${singleMessage.length > 0 ? (singleMessage[0][singleMessageKey?.column] ?? "") : ""}</td>
                        `, "")}
                    </tr>
                   `, "")}`;

    }

    let result = messages.length === 0 ? "" : `
    <table class="table table table-hover table-bordered">
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
