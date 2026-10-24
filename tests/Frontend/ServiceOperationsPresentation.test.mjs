import assert from "node:assert/strict";
import {readFileSync} from "node:fs";
import test from "node:test";
import {parse} from "@vue/compiler-sfc";

test("servicios en curso conserva sus acciones y distingue su interfaz de restaurante", () => {

    const source = readFileSync(new URL("../../resources/js/System/Pages/Operations/service_operations/main.vue", import.meta.url), "utf8");
    const styles = readFileSync(new URL("../../resources/css/System/br-branding/86-service-operations.css", import.meta.url), "utf8");
    const bundledStyles = readFileSync(new URL("../../public/System/assets/css/br-branding.css", import.meta.url), "utf8");

    assert.equal(parse(source, {filename: "service_operations/main.vue"}).errors.length, 0);
    assert.match(source, /br-service-page--sessions/);
    assert.match(source, /class="br-entity-list br-service-list"/);
    assert.match(source, /class="br-table-header-surface"/);
    assert.match(source, /<TableActionsDropdown/);
    assert.match(source, /<StatusBadge :status="session.status"/);
    assert.match(source, /<CopyButton :value="session.reference"/);
    assert.match(source, /<CatalogFormTabs[\s\S]*variant="descriptive"/);
    assert.doesNotMatch(source, /br-service-intro/);
    assert.match(source, /<FormModal[\s\S]*modal-id="brServiceSessionModal"/);
    assert.doesNotMatch(source, /config\.forms/);
    assert.match(source, /v-if="isRestaurant" class="br-status-label" :class="preparationStatusClass/);
    assert.match(source, /v-else class="br-status-label" :class="statusClass\(item.status\)"/);
    assert.match(source, /v-if="\['pending', 'in_progress'\]\.includes\(selectedSession.status\)" class="br-service-add-item"/);
    assert.match(styles, /\.br-service-toolbar--sessions/);
    assert.match(styles, /\.br-service-report-grid/);
    assert.match(styles, /\.br-service-detail__content/);
    assert.match(bundledStyles, /\.br-service-toolbar--sessions/);
    assert.match(bundledStyles, /\.br-service-report-grid/);

});
