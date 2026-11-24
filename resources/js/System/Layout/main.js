// Comportamientos compartidos del layout del sistema.

import {createTooltip} from "../Helpers/Alerts.js";
import {writeClipboard} from "../Helpers/Clipboard.js";
import {initializeMenu} from "./Menu.js";
import {initializeFeedback} from "../Helpers/Feedback.js";
import "../../Shared/disabled-controls.js";

initializeFeedback(window.Swal);
initializeMenu(createTooltip);

const copyResetTimers = new WeakMap();

document.querySelectorAll("[data-fg-copy-value]").forEach(control => createTooltip(control));

document.addEventListener("keydown", function (event) {

    if(event.key !== "Enter" && event.key !== " ") return;

    const icon = event.target.closest('[data-fg-copy-value][role="button"]');
    if(!icon) return;

    event.preventDefault();
    if(!event.repeat) icon.click();

});

document.addEventListener("click", async function (event) {

    const button = event.target.closest("[data-fg-copy-value]");

    if(!button) return;

    try {

        await writeClipboard(button.dataset.fgCopyValue);
        createTooltip(button, "Copiado")?.show();

    }catch(error) {

        createTooltip(button, "No se pudo copiar")?.show();

    }

    window.clearTimeout(copyResetTimers.get(button));
    copyResetTimers.set(button, window.setTimeout(function () {

        createTooltip(button, "Copiar");

    }, 1400));

});
