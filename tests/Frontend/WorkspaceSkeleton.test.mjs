import assert from "node:assert/strict";
import {readFileSync} from "node:fs";
import test from "node:test";

test("el skeleton inicial es optativo y conserva el Swal por defecto", () => {
    const helper = readFileSync(new URL("../../resources/js/System/Helpers/InitialLoad.js", import.meta.url), "utf8");
    const workspace = readFileSync(new URL("../../resources/js/System/Pages/Essentials/workspace/main.vue", import.meta.url), "utf8");

    assert.match(helper, /createInitialLoad\(\{skeleton = false\} = \{\}\)/);
    assert.match(helper, /if\(!skeleton\) Alerts\.swals\(\{type: "initParams"\}\)/);
    assert.match(helper, /if\(!skeleton\) Alerts\.swals\(\{show: false\}\)/);
    assert.match(workspace, /createInitialLoad\(\{skeleton: true\}\)/);
    assert.match(workspace, /<WorkspaceSkeleton v-if="loading && skeletonEnabled"\/>/);
    assert.match(workspace, /initialLoad\.start\(\)[\s\S]*?try \{[\s\S]*?Requests\.get[\s\S]*?finally \{[\s\S]*?initialLoad\.finish\(\)/);
    assert.doesNotMatch(workspace, /<Loader\/>/);
});

test("el skeleton conserva las proporciones del workspace y respeta movimiento reducido", () => {
    const skeleton = readFileSync(new URL("../../resources/js/System/Pages/Essentials/workspace/WorkspaceSkeleton.vue", import.meta.url), "utf8");
    const styles = readFileSync(new URL("../../resources/css/System/Pages/Essentials/workspace/skeleton.css", import.meta.url), "utf8");
    const entry = readFileSync(new URL("../../resources/js/System/Pages/Essentials/workspace/main.js", import.meta.url), "utf8");

    assert.match(skeleton, /role="status" aria-live="polite" aria-label="Cargando tu espacio"/);
    assert.match(skeleton, /br-workspace__layout[\s\S]*?br-workspace__main[\s\S]*?br-workspace__aside[\s\S]*?br-workspace__support/);
    assert.match(skeleton, /v-for="index in 6"/);
    assert.match(skeleton, /v-for="index in 3"[\s\S]*?v-for="index in 4"/);
    assert.match(styles, /@media \(prefers-reduced-motion: reduce\)/);
    assert.match(entry, /workspace\/skeleton\.css/);
});
