import assert from "node:assert/strict";
import {readFileSync} from "node:fs";
import test from "node:test";

for(const page of ["workspace", "account"]) {
    test(`${page} consulta sus datos sin reutilizar la URL HTML`, () => {
        const component = readFileSync(
            new URL(`../../resources/js/System/Pages/Essentials/${page}/main.vue`, import.meta.url),
            "utf8",
        );
        const routes = readFileSync(
            new URL(`../../routes/System/Essentials/${page[0].toUpperCase()}${page.slice(1)}.php`, import.meta.url),
            "utf8",
        );
        const controller = readFileSync(
            new URL(`../../app/Http/Controllers/System/Essentials/${page[0].toUpperCase()}${page.slice(1)}Controller.php`, import.meta.url),
            "utf8",
        );

        assert.match(component, /Requests\.get\(\{route: document\.getElementById\("app"\)\.dataset\.endpoint\}\)/);
        assert.doesNotMatch(component, /__json/);
        assert.match(routes, new RegExp(`Route::get\\("data", \\[\\w+Controller::class, "data"\\]\\)->name\\("${page}\\.data"\\)`));
        assert.match(controller, /public function index\(\): View \{[\s\S]*?return view\(/);
        assert.match(controller, /public function data\([^)]*\): JsonResponse \{/);
        assert.doesNotMatch(controller, /index\([^)]*\): View\|JsonResponse|expectsJson\(\)[\s\S]*?return view\(/);
    });
}
