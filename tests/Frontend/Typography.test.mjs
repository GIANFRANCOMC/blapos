import assert from "node:assert/strict";
import {readFileSync} from "node:fs";
import test from "node:test";

const read = (path) => readFileSync(new URL(`../../${path}`, import.meta.url), "utf8");

test("Source Sans 3 se sirve desde una sola fuente en las superficies FG", () => {
    const entries = [
        "resources/css/System/fg/index.css", "resources/css/System/fg/auth.css",
        "resources/css/Guest/app.css", "resources/css/Platform/app.css"
    ];
    for (const entry of entries) assert.match(read(entry), /Shared\/ui\.css/, entry);
    assert.match(read("resources/css/Shared/ui.css"), /@import "\.\/tokens\.css"/);
    assert.match(read("resources/css/Shared/tokens.css"), /@font-face[\s\S]*?source-sans-3\.ttf/);
    assert.match(read("resources/css/Shared/tokens.css"), /--fg-font: "Source Sans 3", sans-serif/);
    assert.match(read("resources/css/Shared/error-page.css"), /@import "\.\/tokens\.css"/);
});

test("los gráficos usan la familia tipográfica centralizada de CSS", () => {

    const config = read("public/System/assets/js/config.js");
    const main = read("public/System/assets/js/main.js");
    const charts = [
        "app-academy-dashboard.js",
        "app-ecommerce-dashboard.js",
        "app-logistics-dashboard.js",
        "cards-advance.js",
        "cards-analytics.js",
        "cards-statistics.js",
        "charts-apex.js",
        "dashboards-analytics.js",
        "dashboards-crm.js"
    ];

    assert.match(config, /get fontFamily\(\) \{ return fgCssToken\("--fg-font",/);
    assert.match(main, /Chart\.defaults\.font\.family = config\.typography\.fontFamily/);

    for(const chart of charts) {

        const source = read(`public/System/assets/js/${chart}`);

        assert.match(source, /fontFamily: config\.typography\.fontFamily/, chart);
        assert.doesNotMatch(source, /fontFamily: ["']Inter["']/, chart);

    }

});
