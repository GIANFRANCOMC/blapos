import assert from "node:assert/strict";
import {readFileSync} from "node:fs";
import test from "node:test";

const read = (path) => readFileSync(new URL(`../../${path}`, import.meta.url), "utf8");

test("Inter se carga una sola vez por layout y sustituye la fuente base de la plantilla", () => {

    const font = read("resources/views/shared/font-inter.blade.php");
    const layouts = [
        "resources/views/System/layouts/partials/up.blade.php",
        "resources/views/Guest/layouts/partials/up.blade.php",
        "resources/views/Platform/layouts/app.blade.php",
        "resources/views/errors/404.blade.php",
        "resources/views/errors/419.blade.php",
        "resources/views/errors/500.blade.php"
    ];

    assert.match(font, /fonts\.googleapis\.com\/css2\?family=Inter/);

    for(const layout of layouts) {

        const source = read(layout);

        assert.equal(source.match(/@include\("shared\.font-inter"\)/g)?.length, 1, layout);
        assert.doesNotMatch(source, /Public\+Sans|family=Inter/);

    }

    assert.match(read("resources/css/System/br-branding/00-tokens.css"), /--br-font-sans: "Inter", sans-serif/);
    assert.match(read("tailwind.config.js"), /sans: \['Inter'/);

    for(const core of [
        "public/System/assets/vendor/css/core.css",
        "public/System/assets/vendor/css/rtl/core.css",
        "public/System/assets/vendor/css/rtl/core-dark.css"
    ]) {

        assert.match(read(core), /--bs-font-sans-serif: "Inter", sans-serif/);
        assert.doesNotMatch(read(core), /Public Sans/);

    }

});
