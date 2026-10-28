import assert from "node:assert/strict";
import {readFileSync} from "node:fs";
import test from "node:test";

test("la ayuda del inicio muestra contactos configurables en una barra adaptable", () => {

    const view = readFileSync(new URL("../../resources/views/System/general/Essentials/workspace/main.blade.php", import.meta.url), "utf8");
    const styles = readFileSync(new URL("../../resources/css/System/br-branding/52-workspace.css", import.meta.url), "utf8");

    assert.match(view, /br-workspace__support-intro/);
    assert.match(view, /Estamos para ayudarte\. Contáctanos\./);
    assert.match(view, /href="mailto:\{\{ \$supportContact\['email'\] \}\}"/);
    assert.match(view, /href="tel:\{\{ \$supportContact\['phone_href'\] \}\}"/);
    assert.match(view, /br-workspace__support-channel-copy[\s\S]*?Envíanos un correo/);
    assert.match(view, /br-workspace__support-channel-copy[\s\S]*?Llámanos/);
    assert.match(view, /class="br-workspace__support-cta"/);
    assert.ok(view.indexOf('class="br-workspace__main"') < view.indexOf('class="br-workspace__aside"'));
    assert.ok(view.indexOf('class="br-workspace__aside"') < view.indexOf('class="br-workspace__support"'));
    assert.doesNotMatch(styles, /\.br-workspace__aside\s*\{[^}]*order:\s*-1;/);
    assert.match(styles, /\.br-workspace\s*\{[^}]*container-type: inline-size;/);
    assert.match(styles, /@container \(max-width: 50rem\)/);
    assert.match(styles, /@container \(max-width: 34rem\)/);
    assert.match(styles, /\.br-workspace__support-channel-copy\s*\{[^}]*display: grid;/);

});
