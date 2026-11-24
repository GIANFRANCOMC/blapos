import assert from "node:assert/strict";
import {readFileSync} from "node:fs";
import test from "node:test";
import {parse} from "@vue/compiler-sfc";

const read = path => readFileSync(new URL(`../../${path}`, import.meta.url), "utf8");
const readSharedSkeleton = () => read("resources/css/Shared/skeleton.css");

test("Roles cubre solo los parámetros iniciales con un skeleton proporcional", () => {
    const page = read("resources/js/System/Pages/Organizations/roles/main.vue");
    const skeleton = read("resources/js/System/Pages/Organizations/roles/RolesSkeleton.vue");
    const entry = read("resources/js/System/Pages/Organizations/roles/main.js");

    assert.equal(parse(page, {filename: "roles/main.vue"}).errors.length, 0);
    assert.equal(parse(skeleton, {filename: "RolesSkeleton.vue"}).errors.length, 0);
    assert.match(page, /<RolesSkeleton v-if="initialLoading"\/>[\s\S]*?<main v-else class="fg-entity fg-roles">/);
    assert.match(page, /await this\.initParams\(\)[\s\S]*?finally \{[\s\S]*?this\.initialLoading = false[\s\S]*?await this\.listRoles\(\{\}\)/);
    assert.doesNotMatch(page, /Alerts\.swals\(\{type: "initParams"\}\)/);
    assert.match(page, /<tr class="fg-table__state" v-if="loading">[\s\S]*?<Loader\/>/);
    assert.match(skeleton, /role="status" aria-live="polite" aria-label="Cargando perfiles de acceso"/);
    assert.match(skeleton, /fg-filter-bar[\s\S]*?fg-entity-list[\s\S]*?colspan="5" class="py-4"><Loader\/>/);
    assert.match(readSharedSkeleton(), /height: var\(--fg-control-height, 36px\)/);
    assert.match(readSharedSkeleton(), /@media \(prefers-reduced-motion: reduce\)/);
    assert.match(entry, /Organizations\/roles\/skeleton\.css/);
});

test("Datos maestros reserva pestañas, listado y formulario durante el primer listado", () => {
    const page = read("resources/js/System/Pages/General/master_data/main.vue");
    const skeleton = read("resources/js/System/Pages/General/master_data/MasterDataSkeleton.vue");
    const entry = read("resources/js/System/Pages/General/master_data/main.js");

    assert.equal(parse(page, {filename: "master_data/main.vue"}).errors.length, 0);
    assert.equal(parse(skeleton, {filename: "MasterDataSkeleton.vue"}).errors.length, 0);
    assert.match(page, /<MasterDataSkeleton v-if="initialLoading"\/>[\s\S]*?<section v-else class="fg-master-data">/);
    assert.match(page, /try \{[\s\S]*?this\.startCreate\(\)[\s\S]*?await this\.loadRecords\(\)[\s\S]*?finally \{[\s\S]*?this\.initialLoading = false/);
    assert.match(page, /async selectResource\(resourceCode\)[\s\S]*?await this\.loadRecords\(\)/);
    assert.match(skeleton, /role="status" aria-live="polite" aria-label="Cargando datos maestros"/);
    assert.match(skeleton, /fg-tabs--descriptive[\s\S]*?fg-master-data__grid[\s\S]*?fg-master-data__records[\s\S]*?<Loader\/>[\s\S]*?fg-master-data-form/);
    assert.match(readSharedSkeleton(), /height: var\(--fg-control-height, 36px\)/);
    assert.match(readSharedSkeleton(), /@media \(prefers-reduced-motion: reduce\)/);
    assert.match(entry, /General\/master_data\/skeleton\.css/);
});

test("la documentación general define el skeleton inicial como estándar", () => {
    const general = read("docs/frontend/fundamentos/ESTANDARES_UI.md");
    const guide = read("docs/frontend/desarrollo/GUIA_SYSTEM.md");

    assert.match(general, /### Carga inicial con skeleton/);
    assert.match(general, /InitialPageMixin.*PageSkeleton/);
    assert.match(general, /Loader.*fg-table__state/);
    assert.match(general, /liberar `initialLoading` en el `finally`/);
    assert.match(guide, /ESTANDARES_UI\.md#carga-inicial-con-skeleton/);
});
