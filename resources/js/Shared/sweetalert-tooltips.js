import {prepareModalResult} from "./modal-results.js";

function alertOptions(title, html, icon) {
    if(title && typeof title === "object" && !title.nodeType && !title.jquery) return title;

    const options = {};
    if(title != null) options.title = title;
    if(html !== undefined) options.html = html;
    if(icon !== undefined) options.icon = icon;
    return options;
}

export function initializeSweetAlertTooltips({Swal, bootstrap, document}) {
    const Tooltip = bootstrap?.Tooltip;
    if(!Swal?.fire) return;

    const fire = Swal.fire;
    let activePopup = null;

    function hideTooltips(container) {
        if(!Tooltip) return;

        container.querySelectorAll("[aria-describedby]").forEach(trigger => {
            Tooltip.getInstance(trigger)?.hide();
        });
    }

    document.addEventListener("inserted.bs.tooltip", ({target}) => {
        if(!Tooltip || !activePopup || activePopup.contains(target)) return;

        // Bootstrap finishes showing synchronously after insertion. Hiding before
        // paint also resets its hover and focus state for the next interaction.
        const tooltip = Tooltip.getInstance(target);
        queueMicrotask(() => {
            if(tooltip && Tooltip.getInstance(target) === tooltip) tooltip.hide();
        });
    }, true);

    Swal.fire = function (title, html, icon) {
        const options = alertOptions(title, html, icon);
        const modalResult = prepareModalResult({options, document, bootstrap});
        let popup;

        const preparedOptions = {
            ...options,
            ...(modalResult ? {target: document.body, returnFocus: false} : {}),
            cancelButtonText: "Cerrar",
            willOpen(element) {
                popup = activePopup = element;
                hideTooltips(document);
                if(typeof options.willOpen === "function") options.willOpen.call(this, element);
            },
            willClose(element) {
                hideTooltips(element);
                if(typeof options.willClose === "function") options.willClose.call(this, element);
            },
            didDestroy() {
                if(popup) hideTooltips(popup);
                // SweetAlert has already restored focus on normal close.
                if(activePopup === popup) activePopup = null;
                modalResult?.restore();
                if(typeof options.didDestroy === "function") options.didDestroy.call(this);
            }
        };
        if(!modalResult) return fire.call(this, preparedOptions);
        return modalResult.ready.then(() => fire.call(this, preparedOptions)).catch(error => {
            modalResult.restore();
            throw error;
        });
    };
}

if(typeof window !== "undefined") initializeSweetAlertTooltips(window);
