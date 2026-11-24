import assert from "node:assert/strict";
import {existsSync, readdirSync, readFileSync} from "node:fs";
import test from "node:test";

const root = new URL("../../", import.meta.url);
const read = file => readFileSync(new URL(file, root), "utf8");

function files(directory) {
    return readdirSync(new URL(directory + "/", root), {withFileTypes: true}).flatMap(entry => {
        const file = directory + "/" + entry.name;
        return entry.isDirectory() ? files(file) : [file];
    });
}

test("las fuentes de Blapos y la configuración de assets usan exclusivamente el namespace FG", () => {
    const sources = [
        ...["resources/css", "resources/js", "resources/views", "public/System/assets/js"].flatMap(files),
        "public/System/assets/vendor/js/template-customizer.js",
        "package.json", "vite.config.js"
    ].filter(file => /\.(css|vue|js|php|json)$/.test(file));

    for (const file of sources) {
        const source = readFileSync(new URL(file, root), "utf8");
        assert.doesNotMatch(source, /\bbr-[\w-]|\.dataset\.br[A-Z]|build:css:system|build-system-css/, file);
    }
});

test("los layouts cargan la base FG sin otra hoja de tema ni parciales obsoletos de System", () => {
    assert.equal(existsSync(new URL("public/System/assets/vendor/css/core.css", root)), false);
    assert.equal(existsSync(new URL("resources/css/System/app.css", root)), false);
    for(const partial of ["up", "down"]) {
        assert.equal(existsSync(new URL("resources/views/System/layouts/partials/" + partial + ".blade.php", root)), false);
    }

    const layouts = [
        ...files("resources/views/System/layouts"),
        ...files("resources/views/Guest/layouts"),
        "resources/views/Platform/layouts/app.blade.php"
    ];
    for(const file of layouts) {
        assert.doesNotMatch(read(file), /vendor\/css\/(?:rtl\/)?(?:core|theme-default)\.css|vendor\/css\/pages\/front-page|template-customizer|bootstrap-select\.css|css\/System\/app\.css/, file);
    }
    assert.doesNotMatch(read("vite.config.js"), /css\/System\/app\.css|css\/Shared\/viewport\.css/);
    for(const file of files("resources/js").filter(file => /\.(?:js|vue)$/.test(file))) {
        assert.doesNotMatch(read(file), /import ["'][^"']*(?:vue-select\.css|Shared\/skeleton\.css)["']/, file);
    }
});
