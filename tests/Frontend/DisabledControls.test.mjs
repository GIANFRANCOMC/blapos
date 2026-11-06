import assert from "node:assert/strict";
import {readFileSync} from "node:fs";
import test from "node:test";
import {initializeDisabledControlFocus} from "../../resources/js/Shared/disabled-controls.js";

const read = path => readFileSync(new URL(`../../${path}`, import.meta.url), "utf8");

test("el estado disabled global oculta el cursor en campos nativos, Vue Select y Select2", () => {
    const css = read("resources/css/Shared/disabled-controls.css");
    const layout = read("resources/js/System/Layout/main.js");
    const guest = read("resources/views/Guest/layouts/main.blade.php");
    const auth = read("resources/views/System/layouts/guests/auth.blade.php");
    const platform = read("resources/views/Platform/layouts/app.blade.php");
    const platformCss = read("resources/css/Platform/app.css");
    const cssBuilder = read("scripts/build-system-css.mjs");

    assert.match(css, /:is\(input, textarea, select\):disabled,[\s\S]*?\.v-select\.vs--disabled \.vs__search,[\s\S]*?\.select2-container--disabled \.select2-search__field[\s\S]*?caret-color: transparent !important;/);
    assert.match(css, /\.select2-container--disabled \* \{\s*cursor: not-allowed !important;/);
    assert.match(layout, /import "\.\.\/\.\.\/Shared\/disabled-controls\.js"/);
    for(const view of [guest, auth, platform]) {
        assert.match(view, /@vite\('resources\/js\/Shared\/disabled-controls\.js'\)/);
    }
    assert.match(platformCss, /@import "\.\.\/Shared\/disabled-controls\.css"/);
    assert.match(cssBuilder, /sharedFiles: \[[^\]]*"resources\/css\/Shared\/disabled-controls\.css"[^\]]*\]/);
});

test("un campo enfocado pierde el foco al deshabilitarse, también dentro de Vue Select", () => {
    const previous = {
        document: globalThis.document,
        HTMLElement: globalThis.HTMLElement,
        MutationObserver: globalThis.MutationObserver
    };
    const listeners = new Map();
    let observer;
    let observerCount = 0;

    class Element {
        constructor(customSelect = null) {
            this.customSelect = customSelect;
            this.parentElement = customSelect;
            this.isSelectRoot = false;
            this.isFieldset = false;
            this.disabled = false;
            this.ariaDisabled = false;
            this.blurred = false;
        }

        matches(selector) {
            if(selector.startsWith("input, textarea")) return !this.isSelectRoot;
            if(selector.startsWith(":disabled")) return this.disabled || this.parentElement?.isFieldset && this.parentElement.disabled;
            if(selector.startsWith(".v-select")) return this.isSelectRoot;
            if(selector === "fieldset") return this.isFieldset;
            return false;
        }

        closest(selector) {
            for(let parent = this.parentElement; parent; parent = parent.parentElement) {
                if(selector.startsWith(".vs--disabled") && (parent.isSelectRoot && parent.disabled || parent.ariaDisabled)) return parent;
            }
            return null;
        }

        blur() {
            this.blurred = true;
        }
    }

    globalThis.HTMLElement = Element;
    globalThis.MutationObserver = class {
        constructor(callback) {
            this.callback = callback;
            this.targets = [];
            observer = this;
            observerCount++;
        }

        observe(target) {
            this.targets.push(target);
        }

        disconnect() {
            this.targets = [];
        }
    };
    globalThis.document = {
        activeElement: null,
        addEventListener(name, callback) {
            listeners.set(name, callback);
        }
    };

    try {
        initializeDisabledControlFocus();

        const input = new Element();
        document.activeElement = input;
        listeners.get("focusin")();
        input.disabled = true;
        observer.callback();
        assert.equal(input.blurred, true);

        const select = new Element();
        select.isSelectRoot = true;
        const search = new Element(select);
        document.activeElement = search;
        listeners.get("focusin")();
        assert.deepEqual(observer.targets, [search, select]);
        select.disabled = true;
        observer.callback();
        assert.equal(search.blurred, true);

        const container = new Element();
        const nestedInput = new Element(container);
        document.activeElement = nestedInput;
        listeners.get("focusin")();
        container.ariaDisabled = true;
        observer.callback();
        assert.equal(nestedInput.blurred, true);

        const fieldset = new Element();
        fieldset.isFieldset = true;
        const field = new Element(fieldset);
        document.activeElement = field;
        listeners.get("focusin")();
        fieldset.disabled = true;
        observer.callback();
        assert.equal(field.blurred, true);
        assert.equal(observerCount, 1);
    } finally {
        globalThis.document = previous.document;
        globalThis.HTMLElement = previous.HTMLElement;
        globalThis.MutationObserver = previous.MutationObserver;
    }
});
