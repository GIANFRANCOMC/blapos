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

test("las confirmaciones tienen presentación común sin icono y guía UX documentada", () => {
    const css = read("resources/css/System/br-branding/95-swal-confirmations.css");
    const bundle = read("public/System/assets/css/br-branding.css");
    const docs = read("docs/GENERALIDADES.md");
    const alerts = read("resources/js/System/Helpers/Alerts.js");

    assert.match(css, /\.swal2-cancel\[style\*="display: inline-block"\]/);
    assert.match(css, /\.swal2-icon \{\s*display: none !important;/);
    assert.match(css, /\.swal2-title,[\s\S]*?\.swal2-html-container \{[\s\S]*?text-align: left !important;/);
    assert.match(css, /\.swal2-cancel \{[\s\S]*?order: 0;[\s\S]*?\.swal2-confirm \{[\s\S]*?order: 1;/);
    assert.match(bundle, /Source: resources\/css\/System\/br-branding\/95-swal-confirmations\.css/);
    assert.match(alerts, /title: `Descargar \$\{resource\}`/);
    assert.match(docs, /### Confirmaciones SweetAlert[\s\S]*?El cuerpo no repite el título[\s\S]*?showCloseButton: true/);
});
