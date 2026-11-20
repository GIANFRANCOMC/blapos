import assert from "node:assert/strict";
import {existsSync, readFileSync} from "node:fs";
import test from "node:test";

const file = path => new URL(`../../${path}`, import.meta.url);
const read = path => readFileSync(file(path), "utf8");

test("Loader y Swal conservan carga animada accesible sin GIF", () => {

 const loader=read("resources/js/System/Components/Loader.vue"),alerts=read("resources/js/System/Helpers/Alerts.js");
 assert.match(loader,/fa-solid fa-circle-notch/);
 assert.match(alerts,/fg-swal__loading[\s\S]*?feedbackIconHtml\("loading", true\)/);
 assert.match(alerts,/role="status" aria-live="polite" aria-busy="true"/);
 assert.doesNotMatch(alerts,/loader\.gif|loading\.gif/);

});
