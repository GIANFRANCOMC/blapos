import assert from "node:assert/strict";
import {readFileSync} from "node:fs";
import test from "node:test";

test("los tooltips del menú conservan su clase al reconstruirse", async () => {

    const {tooltipOptionsFor} = await import("../../resources/js/System/Helpers/TooltipOptions.js");
    const body = {};

    for(const customClass of ["br-navigation-tooltip", "br-navigation-logout-tooltip"]) {

        const options = tooltipOptionsFor({dataset: {bsCustomClass: customClass}}, body);

        assert.equal(options.customClass, customClass);
        assert.equal(options.container, body);

    }

    const regular = tooltipOptionsFor({dataset: {}}, body);

    assert.equal(regular.customClass, "br-tooltip");
    assert.equal(regular.container, false);

    const alerts = readFileSync(new URL("../../resources/js/System/Helpers/Alerts.js", import.meta.url), "utf8");
    const navigation = readFileSync(new URL("../../resources/js/System/Layout/main.js", import.meta.url), "utf8");

    assert.match(alerts, /new Bootstrap\.Tooltip\(element, tooltipOptionsFor\(element\)\)/);
    assert.match(navigation, /window\.bootstrap\?\.Tooltip\?\.getInstance\(moduleButton\)\?\.hide\(\)/);
    assert.match(navigation, /navigation\.querySelectorAll\('\[data-bs-toggle="tooltip"\]'\)\.forEach\(function \(element\) \{[\s\S]*?createTooltip\(element, element\.getAttribute\("title"\) \|\| ""\)/);
    assert.doesNotMatch(navigation, /tooltip\?\.disable\(\)|element\.removeAttribute\("data-bs-toggle"\)/);

});
