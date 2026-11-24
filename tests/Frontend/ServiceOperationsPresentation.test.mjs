import assert from "node:assert/strict";
import {readFileSync} from "node:fs";
import test from "node:test";
import {parse} from "@vue/compiler-sfc";

test("servicios en curso conserva sus acciones y distingue su interfaz de restaurante", () => {

    const source = readFileSync(new URL("../../resources/js/System/Pages/Operations/service_operations/main.vue", import.meta.url), "utf8");
    const styles = readFileSync(new URL("../../resources/css/System/fg/sales.css", import.meta.url), "utf8");

    assert.equal(parse(source, {filename: "service_operations/main.vue"}).errors.length, 0);
    assert.match(source, /fg-service-page--sessions/);
    assert.match(source, /this\.isRestaurant \? "menu-parent-sales" : "menu-parent-workspace"/);
    assert.match(source, /class="fg-service-list"/);
    assert.match(source, /class="fg-service-list__table mb-0 fg-table"/);
    assert.match(source, /<TableActionsDropdown/);
    assert.match(source, /<StatusBadge :status="session.status"/);
    assert.match(source, /<CopyButton v-if="session\.reference" :value="session\.reference"/);
    assert.match(source, /<AppTabs[\s\S]*variant="descriptive"/);
    assert.doesNotMatch(source, /\bbr-/);
    assert.match(source, /<FormModal[\s\S]*modal-id="fgServiceSessionModal"/);
    assert.doesNotMatch(source, /config\.forms/);
    assert.match(source, /v-if="isRestaurant" class="fg-badge" :class="preparationStatusClass/);
    assert.match(source, /v-else class="fg-badge" :class="statusClass\(item.status\)"/);
    assert.match(source, /v-if="\['pending', 'in_progress'\]\.includes\(selectedSession.status\)" class="fg-service-add-item"/);
    assert.match(styles, /\.fg-service-toolbar--sessions/);
    assert.match(styles, /\.fg-service-report-grid/);
    assert.match(styles, /\.fg-service-detail__content/);

});
