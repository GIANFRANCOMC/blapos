import assert from "node:assert/strict";
import {readFileSync, readdirSync} from "node:fs";
import {join} from "node:path";
import test from "node:test";

const root = process.cwd();
const read = path => readFileSync(join(root, path), "utf8");

function systemScripts(directory) {
    return readdirSync(join(root, directory), {withFileTypes: true}).flatMap(entry => {
        const path = join(directory, entry.name);
        if(entry.isDirectory()) return systemScripts(path);
        return /\.(js|vue)$/.test(entry.name) ? [path] : [];
    });
}

test("Escape no cierra ningún SweetAlert de System ni Guest", () => {
    const scripts = [
        ...systemScripts("resources/js/System"),
        ...systemScripts("resources/js/Guest"),
        "resources/views/System/auth/login.blade.php"
    ];
    let dialogs = 0;

    for(const path of scripts) {
        const source = read(path);
        for(const block of source.split(/(?:window\.)?Swal\.fire\(\{/).slice(1)) {
            assert.match(block.slice(0, 10000), /\ballowEscapeKey\s*:\s*false\b/, path);
            dialogs++;
        }
    }

    assert.ok(dialogs >= 32);
});

test("todas las confirmaciones System muestran cierre accesible y evitan respuestas sí/no", () => {
    let confirmations = 0;

    for(const path of systemScripts("resources/js/System")) {
        const source = read(path);
        for(const match of source.matchAll(/showCancelButton:\s*true/g)) {
            confirmations++;
            const options = source.slice(match.index, match.index + 170);
            assert.match(options, /showCloseButton:\s*true/, path);
            assert.match(options, /closeButtonAriaLabel:\s*"Cerrar confirmación"/, path);
        }
        assert.doesNotMatch(source, /confirmButtonText:\s*["']Sí(?:,|["'])/, path);
        assert.doesNotMatch(source, /cancelButtonText:\s*TEXT\.(?:YES|NO)/, path);
    }

    assert.ok(confirmations >= 25);
});

test("los títulos de confirmación formulan preguntas con verbo y sustantivo", () => {
    let confirmations = 0;
    const scripts = [...systemScripts("resources/js/System/Pages"), "resources/js/System/Layout/Menu.js"];

    for(const path of scripts) {
        const source = read(path);
        for(const block of source.split(/(?:window\.)?Swal\.fire\(\{/).slice(1)) {
            const cancelAt = block.indexOf("showCancelButton: true");
            if(cancelAt < 0 || cancelAt > 5000) continue;

            const title = block.slice(0, cancelAt).match(/\btitle:\s*([^\r\n]+)/)?.[1] || "";
            if(title.includes("confirmation.title")) {
                assert.match(source, /title: "¿Agregar favorito\?"[\s\S]*?title: "¿Quitar favorito\?"/, path);
            } else if(title.includes("approve ?")) {
                assert.match(title, /"¿Aprobar corrección\?"\s*:\s*"¿Rechazar corrección\?"/, path);
            } else {
                assert.match(title, /^"¿[^"?]+\?"/, path);
            }
            confirmations++;
        }
    }

    assert.ok(confirmations >= 25);
    assert.match(read("resources/js/System/Helpers/Alerts.js"), /title = "¿Confirmar operación\?"/);
    assert.match(read("resources/js/System/Helpers/Alerts.js"), /title: `¿Descargar \$\{resource\}\?`/);
});

test("las confirmaciones tienen presentación común sin icono y guía UX documentada", () => {

 const alerts=read("resources/js/System/Helpers/Alerts.js"),css=read("resources/css/System/fg/feedback.css");
 const confirmation=alerts.slice(alerts.indexOf("function actionConfirmation("),alerts.indexOf("function setMessageWidth("));
 assert.match(confirmation,/showCancelButton: true/);
 assert.match(confirmation,/showCloseButton: true/);
 assert.match(confirmation,/cancelButton: "fg-button fg-button--secondary"/);
 assert.match(confirmation,/confirmButton: "fg-button"/);
 assert.match(confirmation,/popup: "fg-swal"/);
 assert.doesNotMatch(confirmation,/\n\s*icon,/);
 assert.match(css,/font-family: var\(--fg-font\)/);
 assert.ok(alerts.includes("¿Descargar"));

});

test("todos los mensajes de estado usan iconos completos y ancho ampliable sin afectar confirmaciones", () => {

 const alerts=read("resources/js/System/Helpers/Alerts.js"),css=read("resources/css/System/fg/feedback.css");
 const feedback=read("resources/js/System/Helpers/Feedback.js");
 assert.match(feedback,/--fg-swal-width/);
 assert.match(alerts,/Number.isFinite\(width\)[\s\S]*?--fg-swal-width/);
 assert.match(alerts,/icon: type/);
 assert.match(alerts,/iconHtml: feedbackIconHtml\(type\)/);
 assert.match(feedback,/success.svg/);
 assert.match(css,/fg-feedback-icon/);

});

test("limpiar filtros muestra el aviso informativo de Toastr y usa el botón compartido", () => {
    const alerts = read("resources/js/System/Helpers/Alerts.js");
    const button = read("resources/js/System/Components/Generics/ClearFiltersButton.vue");

    assert.match(alerts, /export function showFiltersCleared\(\) \{\s*toastrs\(\{type: "info", title: "Se limpiaron los filtros correctamente\."\}\);\s*\}/);
    assert.match(button, /<svg[^>]*viewBox="0 0 384 560"[^>]*fill="currentColor"[^>]*aria-hidden="true"/);
    const fgFilters = read("resources/js/System/Components/Figma/FgResponsiveFilters.vue");
    const payable = read("resources/js/System/Pages/Finance/accounts_payable/main.vue");
    assert.match(fgFilters, /<FgFilterActions[^>]*@clear="\$emit\('clear'\)"/);
    assert.match(payable, /<ClearFiltersButton[^>]*@clear="clearFilters"/);
    for(const page of ["Sales/sales/list.vue", "Sales/sales/deliveries.vue", "Sales/quotations/main.vue", "Finance/accounts_receivable/main.vue"]) {
        const source = read(`resources/js/System/Pages/${page}`);
        assert.match(source, /<FgResponsiveFilters[^>]*@clear="clearFilters"/);
        assert.match(source, /Alerts\.showFiltersCleared\(\)/);
    }
});
