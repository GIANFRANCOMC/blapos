import assert from "node:assert/strict";
import {readdirSync, readFileSync} from "node:fs";
import test from "node:test";

const root = new URL("../../", import.meta.url);

function files(directory) {
    return readdirSync(new URL(directory + "/", root), {withFileTypes: true}).flatMap(entry => {
        const file = directory + "/" + entry.name;
        return entry.isDirectory() ? files(file) : [file];
    });
}

test("las fuentes de Blapos y la configuración de assets usan exclusivamente el namespace FG", () => {
    const sources = [
        ...["resources/css", "resources/js", "resources/views", "public/System/assets/js"].flatMap(files),
        "public/System/assets/vendor/css/core.css",
        "public/System/assets/vendor/css/rtl/core.css",
        "public/System/assets/vendor/css/theme-default.css",
        "public/System/assets/vendor/css/rtl/theme-default.css",
        "public/System/assets/vendor/js/template-customizer.js",
        "package.json", "vite.config.js"
    ].filter(file => /\.(css|vue|js|php|json)$/.test(file));

    for (const file of sources) {
        const source = readFileSync(new URL(file, root), "utf8");
        assert.doesNotMatch(source, /\bbr-[\w-]|\.dataset\.br[A-Z]|build:css:system|build-system-css/, file);
    }
});
