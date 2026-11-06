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
    const scripts = [...systemScripts("resources/js/System/Pages"), "resources/js/System/Layout/main.js"];

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
    const css = read("resources/css/System/br-branding/95-swal-confirmations.css");
    const bundle = read("public/System/assets/css/br-branding.css");
    const docs = read("docs/GENERALIDADES.md");
    const alerts = read("resources/js/System/Helpers/Alerts.js");

    assert.match(css, /\.swal2-cancel\[style\*="display: inline-block"\]/);
    assert.match(css, /\.swal2-icon \{\s*display: none !important;/);
    assert.match(css, /\.swal2-title,[\s\S]*?\.swal2-html-container \{[\s\S]*?text-align: left !important;/);
    assert.match(css, /\.swal2-cancel \{[\s\S]*?order: 0;[\s\S]*?\.swal2-confirm \{[\s\S]*?order: 1;/);
    assert.match(alerts, /cancelButton: "br-btn br-btn-cancel"/);
    assert.doesNotMatch(css, /\.swal2-cancel \{[^}]*(?:background|color|border):/);
    assert.match(bundle, /Source: resources\/css\/System\/br-branding\/95-swal-confirmations\.css/);
    assert.match(alerts, /title: `¿Descargar \$\{resource\}\?`/);
    assert.match(css, /\.swal2-title \{[\s\S]*?border-bottom: 1px solid var\(--br-border\);[\s\S]*?font-size: var\(--br-font-size-fluid-confirmation\)/);
    assert.match(css, /\.swal2-title \{[^}]*font-size: var\(--br-font-size-fluid-confirmation\)/);
    assert.match(css, /\.swal2-html-container \{[^}]*font-size: calc\(var\(--br-font-size-label\) \+ var\(--br-font-size-0075\)\) !important;/);
    assert.match(css, /\.swal2-actions \{[\s\S]*?border-top: 1px solid var\(--br-border\);/);
    assert.match(css, /\.swal2-actions \{[^}]*padding: \.7rem 1\.25rem \.8rem !important;/);
    assert.match(css, /\.swal2-title \{[^}]*background: var\(--br-neutral-panel\);/);
    assert.match(css, /\.swal2-actions \{[^}]*background: var\(--br-neutral-panel\);/);
    assert.match(css, /\.swal2-close \{[\s\S]*?width: 2rem;[\s\S]*?height: 2rem;[\s\S]*?background: transparent !important;[\s\S]*?font-size: var\(--br-font-size-150\) !important;/);
    assert.match(docs, /El título formula una pregunta breve y específica[\s\S]*?¿Anular venta\?/);
    assert.match(docs, /### Confirmaciones SweetAlert[\s\S]*?El cuerpo no repite el título[\s\S]*?showCloseButton: true/);
});

test("todos los mensajes de estado usan iconos completos y ancho ampliable sin afectar confirmaciones", () => {
    const css = read("resources/css/System/br-branding/81-core-roles-entities.css");
    const alerts = read("resources/js/System/Helpers/Alerts.js");
    const docs = read("docs/GENERALIDADES.md");

    assert.match(css, /\.swal2-popup\.br-swal-alert \{[^}]*width: min\(var\(--br-swal-alert-width, 25rem\), calc\(100vw - 2rem\)\) !important;/);
    assert.match(css, /\.br-swal-alert--message \.swal2-title,\s*\.swal2-popup\.br-swal-alert--message \.swal2-html-container \{\s*font-size: calc\(var\(--br-font-size-label\) \+ var\(--br-font-size-0075\)\) !important;/);
    assert.match(css, /\.br-swal-alert--message \.swal2-icon \{[^}]*width: 3\.25rem;[^}]*height: 3\.25rem;[^}]*animation: br-swal-message-icon-in/);
    assert.match(css, /\.br-swal-alert--message \.swal2-icon > \* \{\s*display: none !important;/);
    assert.match(css, /\.swal2-icon\.swal2-success \.swal2-icon-content \{\s*display: flex !important;/);
    assert.match(css, /\.br-swal-message-check \{[^}]*width: 2rem;[^}]*height: 2rem;/);
    assert.match(css, /\.br-swal-message-check path \{[^}]*stroke-dasharray: 1;[^}]*animation: br-swal-message-check-draw/);
    assert.match(css, /animation: br-swal-message-icon-in \.32s/);
    assert.match(css, /animation: br-swal-message-check-draw \.36s \.14s/);
    assert.match(css, /@media \(prefers-reduced-motion: reduce\) \{[\s\S]*?\.br-swal-message-check path \{\s*stroke-dashoffset: 0;/);
    for(const type of ["success", "warning", "info", "error", "question"]) {
        assert.match(css, new RegExp(`\\.br-swal-alert--message \\.swal2-icon\\.swal2-${type}(?:::before| \\{)`));
    }
    assert.match(alerts, /function setMessageWidth\(popup, width\)[\s\S]*?Number\.isFinite\(width\)[\s\S]*?--br-swal-alert-width/);
    assert.match(alerts, /export function showMessage\(\{[^}]*width = null\} = \{\}\)[\s\S]*?willOpen: popup => setMessageWidth\(popup, width\)/);
    assert.match(alerts, /export function generateAlert\(\{[^}]*width = null\} = \{\}\)[\s\S]*?willOpen\s*: popup => setMessageWidth\(popup, width\)/);
    assert.match(alerts, /popup: `br-swal-alert br-swal-alert--message br-swal-alert--\$\{type\}`/);
    assert.match(alerts, /const SUCCESS_MESSAGE_ICON = '<svg[^']*viewBox="2 5 20 16"[^']*pathLength="1"[^']*<\/svg>'/);
    assert.equal((alerts.match(/iconHtml:\s*type === "success" \? SUCCESS_MESSAGE_ICON : undefined/g) || []).length, 1);
    assert.match(alerts, /iconHtml\s*: type === "success" \? SUCCESS_MESSAGE_ICON : undefined/);
    assert.match(docs, /### Mensajes de estado SweetAlert[\s\S]*?éxito, advertencia, información, error y pregunta[\s\S]*?width: 520/);
    assert.match(docs, /misma escala tipográfica que el cuerpo de una confirmación/);
});
