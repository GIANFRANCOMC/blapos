import assert from "node:assert/strict";
import {readFileSync, readdirSync} from "node:fs";
import path from "node:path";
import test from "node:test";
import postcss from "postcss";

const root = new URL("../../", import.meta.url);
const read = file => readFileSync(new URL(file, root), "utf8");
const sharedPath = "resources/css/Shared/tokens.css";

function files(directory) {
    return readdirSync(new URL(`${directory}/`, root), {withFileTypes: true}).flatMap(entry => {
        const file = `${directory}/${entry.name}`;
        return entry.isDirectory() ? files(file) : [file];
    });
}

test("los estilos propios consumen tamaños reutilizables en CSS y componentes Vue", () => {
    const sources = [...files("resources/css"), ...files("resources/js")];
    const steps = new Set();
    postcss.parse(read(sharedPath)).walkDecls(/^--fg-[\w-]+-size$/, declaration => steps.add(declaration.prop));
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
                for(const [step] of declaration.value.matchAll(/--fg-[\w-]+-size(?=\))/g)) {
                    assert.ok(steps.has(step) || source.includes(step + ":"), `${file}: ${step} debe existir en la escala compartida`);
                }
            });
        }
    }
});

test("los roles de tipografía tienen un único origen y las cantidades de stock comparten tamaño", () => {
    const css = postcss.parse(read(sharedPath));
    const roles = ["--fg-body-size", "--fg-help-size", "--fg-small-size", "--fg-section-size", "--fg-modal-title-size"];
    for (const role of roles) {
        const definitions = [];
        css.walkDecls(role, declaration => definitions.push(declaration.value));
        assert.equal(definitions.length, 1, role);
        assert.match(definitions[0], /^\d+px$/, role);
    }
    const inventory = postcss.parse(read("resources/css/System/fg/inventory.css"));
    let sharedRule;
    inventory.walkRules(rule => {
        if(rule.selectors.includes(".fg-inventory-quantity")) sharedRule = rule;
    });
    assert.ok(sharedRule);
    assert.equal(sharedRule.nodes.find(d => d.prop === "font-size").value, "var(--fg-section-size)");
    for(const file of ["StockDifference.vue", "StockTraceabilityModal.vue"]) {
        const view = read("resources/js/System/Pages/Warehouses/inventory/" + file);
        assert.match(view, /fg-inventory-quantity/);
        assert.doesNotMatch(view, /fw-(?:bold|semibold)/);
    }
});
