import assert from "node:assert/strict";
import test from "node:test";
import {initializeSweetAlertTooltips} from "../../resources/js/Shared/sweetalert-tooltips.js";

test("SweetAlert oculta tooltips durante la apertura y el retorno de foco", async () => {
    const pageTrigger = {};
    const popupTrigger = {};
    const hidden = [];
    const popup = {
        contains: trigger => trigger === popupTrigger,
        querySelectorAll: () => [popupTrigger]
    };
    const document = {
        querySelectorAll: () => [pageTrigger],
        addEventListener(name, handler) {
            assert.equal(name, "inserted.bs.tooltip");
            this.insertTooltip = target => handler({target});
        }
    };
    const tooltips = new Map([pageTrigger, popupTrigger].map(trigger => [trigger, {hide: () => hidden.push(trigger)}]));
    const Swal = {fire: options => options};
    initializeSweetAlertTooltips({Swal, document, bootstrap: {Tooltip: {getInstance: trigger => tooltips.get(trigger)}}});

    let opened;
    let closed;
    let destroyed = false;
    const alert = Swal.fire({
        title: "Confirmación",
        willOpen: element => { opened = element; },
        willClose: element => { closed = element; },
        didDestroy: () => { destroyed = true; }
    });
    alert.willOpen(popup);
    assert.equal(opened, popup);
    assert.deepEqual(hidden.splice(0), [pageTrigger]);

    document.insertTooltip(popupTrigger);
    await Promise.resolve();
    assert.deepEqual(hidden, []);

    alert.willClose(popup);
    assert.equal(closed, popup);
    assert.deepEqual(hidden.splice(0), [popupTrigger]);
    document.insertTooltip(pageTrigger);
    await Promise.resolve();
    assert.deepEqual(hidden.splice(0), [pageTrigger]);

    alert.didDestroy();
    assert.ok(destroyed);
    assert.deepEqual(hidden.splice(0), [popupTrigger]);
    document.insertTooltip(pageTrigger);
    await Promise.resolve();
    assert.deepEqual(hidden, []);

    const positional = Swal.fire("Listo", "Operación completada", "success");
    assert.equal(positional.title, "Listo");
    assert.equal(positional.html, "Operación completada");
    assert.equal(positional.icon, "success");
});
