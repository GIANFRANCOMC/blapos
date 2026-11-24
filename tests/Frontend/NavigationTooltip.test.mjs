import assert from "node:assert/strict";
import {readFileSync} from "node:fs";
import test from "node:test";

test("los tooltips del menú conservan su clase al reconstruirse", async () => {

    const {tooltipOptionsFor} = await import("../../resources/js/System/Helpers/TooltipOptions.js");
    const body = {};

    for(const customClass of ["fg-tooltip", "fg-tooltip fg-tooltip--danger"]) {

        const options = tooltipOptionsFor({dataset: {bsCustomClass: customClass}}, body);

        assert.equal(options.customClass, customClass);
        assert.equal(options.container, body);

    }

    const regular = tooltipOptionsFor({dataset: {}}, body);

    assert.equal(regular.customClass, "fg-tooltip");
    assert.equal(regular.container, body);
    assert.equal(regular.trigger, "hover focus");
    assert.equal(tooltipOptionsFor({dataset: {bsTrigger: "hover"}}, body).trigger, "hover");

    const alerts = readFileSync(new URL("../../resources/js/System/Helpers/Alerts.js", import.meta.url), "utf8");
    const navigation = readFileSync(new URL("../../resources/js/System/Layout/Menu.js", import.meta.url), "utf8");

    assert.match(alerts, /new Bootstrap\.Tooltip\(element, tooltipOptionsFor\(element\)\)/);
    assert.match(navigation, /window\.bootstrap\?\.Tooltip\?\.getInstance\(moduleButton\)\?\.hide\(\)/);
    assert.match(navigation, /const desktopTooltips = layout.querySelectorAll\("\[data-fg-desktop-tooltip\]"\);\s*const syncRailTooltips = \(\) => desktopTooltips.forEach\(element => createTooltip\(element\)\)/);
    assert.doesNotMatch(navigation, /tooltip\?\.disable\(\)|element\.removeAttribute\("data-bs-toggle"\)/);

});
