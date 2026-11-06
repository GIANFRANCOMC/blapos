import assert from "node:assert/strict";
import {readFileSync, readdirSync} from "node:fs";
import path from "node:path";
import test from "node:test";
import postcss from "postcss";

const root = new URL("../../", import.meta.url);
const read = file => readFileSync(new URL(file, root), "utf8");
const sharedPath = "resources/css/Shared/typography-tokens.css";

function files(directory) {
    return readdirSync(new URL(`${directory}/`, root), {withFileTypes: true}).flatMap(entry => {
        const file = `${directory}/${entry.name}`;
        return entry.isDirectory() ? files(file) : [file];
    });
}

test("los estilos propios consumen tamaños reutilizables en CSS y componentes Vue", () => {
    const sources = [...files("resources/css"), ...files("resources/js")];
    for(const file of sources) {
        if(![".css", ".vue"].includes(path.extname(file)) || file === sharedPath) continue;
        const source = read(file);
        const styles = file.endsWith(".vue")
            ? [...source.matchAll(/<style\b[^>]*>([\s\S]*?)<\/style>/g)].map(match => match[1])
            : [source];
        for(const style of styles) {
            postcss.parse(style).walkDecls("font-size", declaration => {
                assert.ok(/^(?:inherit|0|var\(|calc\(var\()/.test(declaration.value), `${file}: ${declaration.toString()}`);
                assert.doesNotMatch(declaration.value, /\d(?:rem|em|px|vw)\b/, file);
            });
        }
    }
});

test("la escala compartida amplía tamaños absolutos y fluidos una sola vez", () => {
    const css = postcss.parse(read(sharedPath));
    const definitions = new Map();
    css.walkDecls(declaration => definitions.set(declaration.prop, declaration.value));
    assert.equal(definitions.get("--br-font-scale"), "1");
    for(const [name, value] of definitions) {
        if(/^--br-font-size-\d+$/.test(name)) {
            assert.match(value, /^calc\([\d.]+rem \* var\(--br-font-scale\)\)$/, name);
        } else if(name.startsWith("--br-font-size-fluid-")) {
            assert.match(value, /^calc\(clamp\(.+\) \* var\(--br-font-scale\)\)$/, name);
        } else if(name.startsWith("--br-icon-size-")) {
            assert.match(value, /^[\d.]+em$/, name);
        }
    }
    const bundle = read("public/System/assets/css/br-branding.css");
    const platform = read("resources/css/Platform/app.css");
    assert.equal(bundle.split("--br-font-scale: 1;").length - 1, 1);
    assert.match(platform, /@import "\.\.\/Shared\/typography-tokens\.css"/);
    assert.ok(bundle.indexOf("--br-font-scale: 1;") < bundle.indexOf("--br-ui-font-size:"));
});

test("los roles globales tienen un único origen y stock comparte el mismo tamaño", () => {
    const tokens = postcss.parse(read("resources/css/System/br-branding/00-tokens.css"));
    const shared = postcss.parse(read(sharedPath));
    const definitions = new Map();
    for(const css of [tokens, shared]) css.walkDecls(d => definitions.set(d.prop, d.value));
    for(const name of ["--br-ui-font-size", "--br-stock-quantity-font-size", "--br-table-value-font-size", "--br-table-heading-font-size"]) {
        const reference = definitions.get(name).match(/^var\((--[\w-]+)\)$/)?.[1];
        assert.ok(definitions.has(reference), `${name} referencia un paso definido`);
        let count = 0;
        postcss.parse(read("public/System/assets/css/br-branding.css")).walkDecls(name, () => { count++; });
        assert.equal(count, 1, `${name} no se redefine en otra capa`);
    }
    assert.equal(definitions.get("--br-stock-quantity-font-size"), "var(--br-font-size-080)");
    const inventory = postcss.parse(read("resources/css/System/br-branding/82-inventory.css"));
    let sharedRule;
    inventory.walkRules(rule => {
        if(rule.selectors.includes("body.br-layout .br-inventory__stock-current") && rule.selectors.includes("body.br-layout .br-inventory__difference")) sharedRule = rule;
    });
    assert.ok(sharedRule);
    assert.equal(sharedRule.nodes.find(d => d.prop === "font-size").value, "var(--br-stock-quantity-font-size)");
});
