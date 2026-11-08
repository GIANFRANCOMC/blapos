import assert from "node:assert/strict";
import {readFileSync} from "node:fs";
import test from "node:test";

test("Workspace usa el skeleton durante su primera consulta", () => {
    const workspace = readFileSync(new URL("../../resources/js/System/Pages/Essentials/workspace/main.vue", import.meta.url), "utf8");

    assert.match(workspace, /<WorkspaceSkeleton v-if="loading"\/>/);
    assert.match(workspace, /try \{[\s\S]*?Requests\.get[\s\S]*?finally \{[\s\S]*?this\.loading = false/);
    assert.doesNotMatch(workspace, /type: "initParams"/);
});

test("el skeleton conserva las proporciones del workspace y respeta movimiento reducido", () => {
    const skeleton = readFileSync(new URL("../../resources/js/System/Pages/Essentials/workspace/WorkspaceSkeleton.vue", import.meta.url), "utf8");
    const companySkeleton = readFileSync(new URL("../../resources/js/System/Pages/Essentials/workspace/CompanyCardSkeleton.vue", import.meta.url), "utf8");
    const styles = readFileSync(new URL("../../resources/css/System/Pages/Essentials/workspace/skeleton.css", import.meta.url), "utf8");
    const entry = readFileSync(new URL("../../resources/js/System/Pages/Essentials/workspace/main.js", import.meta.url), "utf8");

    assert.match(skeleton, /role="status" aria-live="polite" aria-label="Cargando tu espacio"/);
    assert.doesNotMatch(skeleton, /br-workspace-skeleton__eyebrow/);
    assert.match(skeleton, /br-workspace__layout[\s\S]*?br-workspace__main[\s\S]*?br-workspace__aside[\s\S]*?br-workspace__support/);
    assert.match(skeleton, /v-for="index in 6"/);
    assert.match(skeleton, /<CompanyCardSkeleton\/>/);
    assert.match(companySkeleton, /v-for="index in 3"[\s\S]*?v-for="index in 4"/);
    assert.match(styles, /@media \(prefers-reduced-motion: reduce\)/);
    assert.match(entry, /workspace\/skeleton\.css/);
});
