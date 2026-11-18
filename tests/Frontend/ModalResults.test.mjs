import assert from "node:assert/strict";
import test from "node:test";
import {prepareModalResult} from "../../resources/js/Shared/modal-results.js";
import {initializeSweetAlertTooltips} from "../../resources/js/Shared/sweetalert-tooltips.js";
import {legibleFormatDate} from "../../resources/js/System/Helpers/DateUtils.js";

const hiddenClass = "br-modal-result-hidden";

function element(classes = []) {
    const node = new EventTarget();
    const names = new Set(classes);
    return Object.assign(node, {
        classList: {contains: name => names.has(name), add: name => names.add(name), remove: name => names.delete(name)},
        style: {display: "block"}, inert: false, isConnected: true,
        querySelectorAll: () => [], contains: target => target === node.input,
        data: {name: "Datos sin guardar"}
    });
}

function environment(count = 1) {
    const modals = Array.from({length: count}, () => element(["modal", "show"]));
    const backdrop = element(["modal-backdrop"]);
    const log = [];
    const instances = new Map(modals.map((node, index) => [node, {
        _isShown: true, _isTransitioning: false,
        _focustrap: {deactivate() {}, activate: () => log.push(`focus:${index}`)},
        hide() {
            log.push(`hide:${index}`);
            this._isShown = false;
            this._isTransitioning = true;
            node.classList.remove("show");
        }
    }]));
    const input = {isConnected: true, blur() {}, focus: () => log.push("focus:input")};
    modals.at(-1).input = input;
    const document = {
        body: {}, activeElement: input, addEventListener() {},
        querySelectorAll: selector => selector === ".modal" ? modals : selector === ".modal-backdrop" ? [backdrop] : []
    };
    const bootstrap = {Modal: {getInstance: node => instances.get(node)}};
    const Swal = {fire: options => { log.push("alert"); return options; }};
    initializeSweetAlertTooltips({Swal, document, bootstrap});
    return {
        modals, backdrop, log, instances, document, bootstrap, Swal,
        finishHide(index = 0) {
            instances.get(modals[index])._isTransitioning = false;
            modals[index].style.display = "none";
            modals[index].dispatchEvent(new Event("hidden.bs.modal"));
        }
    };
}

test("un resultado exitoso espera el cierre completo de la modal antes de mostrar el Swal", async () => {
    const env = environment();
    const pending = env.Swal.fire({icon: "success", title: "Guardado"});
    assert.deepEqual(env.log, ["hide:0"]);
    assert.ok(env.modals[0].classList.contains(hiddenClass));
    assert.ok(env.backdrop.classList.contains(hiddenClass));
    env.finishHide();
    const options = await pending;
    assert.deepEqual(env.log, ["hide:0", "alert"]);
    assert.equal(options.target, env.document.body);
    options.didDestroy();
    assert.ok(!env.modals[0].classList.contains(hiddenClass));
});

test("un error muestra únicamente el Swal y devuelve el formulario con sus datos y foco al entenderlo", async () => {
    const env = environment();
    const options = await env.Swal.fire({icon: "error", title: "Revisa los campos"});
    assert.deepEqual(env.log, ["alert"]);
    assert.ok(env.modals[0].classList.contains(hiddenClass));
    assert.equal(options.returnFocus, false);
    options.didDestroy();
    assert.ok(env.modals[0].classList.contains("show"));
    assert.ok(!env.modals[0].classList.contains(hiddenClass));
    assert.equal(env.modals[0].data.name, "Datos sin guardar");
    assert.equal(env.log.at(-1), "focus:input");
});

test("también espera una modal cuyo cierre fue iniciado por el módulo", async () => {
    const env = environment();
    env.instances.get(env.modals[0]).hide();
    const pending = env.Swal.fire({icon: "success"});
    assert.deepEqual(env.log, ["hide:0"]);
    env.finishHide();
    const options = await pending;
    assert.deepEqual(env.log, ["hide:0", "alert"]);
    options.didDestroy();
});

test("el éxito en una modal apilada conserva el formulario padre", async () => {
    const env = environment(2);
    env.modals[0].inert = true;
    const pending = env.Swal.fire({icon: "success"});
    assert.deepEqual(env.log, ["hide:1"]);
    env.modals[0].inert = false; // The stacked modal restores its parent on hidden.
    env.finishHide(1);
    const options = await pending;
    options.didDestroy();
    assert.ok(env.modals[0].classList.contains("show"));
    assert.equal(env.modals[0].data.name, "Datos sin guardar");
    assert.ok(!env.modals[0].classList.contains(hiddenClass));
    assert.equal(env.log.at(-1), "focus:0");
});

test("confirmaciones, loaders y formularios Swal conservan su modal", () => {
    const env = environment();
    for(const options of [
        {icon: "warning"}, {icon: "error", showCancelButton: true},
        {icon: "success", showConfirmButton: false}, {icon: "error", input: "textarea"}
    ]) assert.equal(prepareModalResult({...env, options}), null);
    assert.ok(!env.modals[0].classList.contains(hiddenClass));
});

test("resultados consecutivos no restauran una modal que otro mensaje mantiene oculta", () => {
    const env = environment();
    const first = prepareModalResult({...env, options: {icon: "error"}});
    const second = prepareModalResult({...env, options: {icon: "error"}});
    first.restore();
    assert.ok(env.modals[0].classList.contains(hiddenClass));
    second.restore();
    assert.ok(!env.modals[0].classList.contains(hiddenClass));
});

test("el formato reutilizado para traslados incluye ceros iniciales y no agrega coma", () => {
    assert.equal(legibleFormatDate({dateString: "2026-11-02 09:03:00"}), "02/11/2026 09:03 AM");
    assert.equal(legibleFormatDate({dateString: "2026-11-02 13:04:00"}), "02/11/2026 01:04 PM");
});
