import assert from 'node:assert/strict';
import test from 'node:test';
import {readFileSync} from 'node:fs';
import {feedbackOptions, feedbackIconHtml, initializeFeedback} from '../../resources/js/System/Helpers/Feedback.js';
import {positionSelectDropdown} from '../../resources/js/System/Helpers/SelectOverlay.js';

test('feedback removes application theme classes without changing decisions, callbacks or input contracts', async () => {
    const result = {isConfirmed: false, isDismissed: true};
    const calls = [];
    const Swal = {fire(options) { calls.push({receiver: this, options}); return Promise.resolve(result); }};
    let opened;
    const original = {title: '¿Continuar?', showCancelButton: true, customClass: {popup: 'br-swal', confirmButton: 'br-btn-danger'}, input: 'textarea', inputAttributes: {maxlength: '255'}, willOpen(popup) { opened = popup; }};
    initializeFeedback(Swal);
    const wrapped = Swal.fire;
    initializeFeedback(Swal);
    assert.equal(Swal.fire, wrapped);
    assert.equal(await Swal.fire(original), result);
    assert.equal(calls.length, 1);
    assert.equal(calls[0].receiver, Swal);
    const options = calls[0].options;
    assert.equal(options.showCancelButton, true);
    assert.equal(options.input, 'textarea');
    assert.deepEqual(options.inputAttributes, original.inputAttributes);
    assert.equal(options.customClass.confirmButton, 'fg-button fg-button--danger');
    assert.match(options.customClass.popup, /fg-swal--confirm/);
    assert.doesNotMatch(Object.values(options.customClass).join(' '), /br-/);
    assert.equal(original.customClass.popup, 'br-swal');
    const popup = {style: {setProperty() {}}};
    options.willOpen(popup);
    assert.equal(opened, popup);
});

test('feedback uses the unmodified local Figma icons at their original dimensions', () => {
    for(const name of ['success', 'error', 'warning', 'loading']) {
        const html = feedbackIconHtml(name, name === 'loading');
        assert.match(html, /width="32" height="32"/);
        const url = html.match(/src="([^"]+)"/)[1];
        const root = readFileSync(new URL(url), 'utf8').match(/<svg\b[^>]*>/)[0];
        assert.match(root, /width="32"/);
        assert.match(root, /height="32"/);
        assert.doesNotMatch(url, /figma\.com/);
        const options = feedbackOptions({icon: name === 'loading' ? 'info' : name});
        assert.match(options.iconHtml, /fg-feedback-icon/);
    }
    assert.match(feedbackIconHtml('loading', true), /fa-spin/);
    assert.match(feedbackIconHtml('export'), /width="16" height="16"/);
});

test('custom feedback widths and lifecycle hooks remain available', () => {
    const styles = new Map();
    let opened = false;
    const options = feedbackOptions({width: 640, willOpen() { opened = true; }, showConfirmButton: false, customClass: {popup: 'fg-swal--loading'}});
    options.willOpen({style: {setProperty: (key, value) => styles.set(key, value)}});
    assert.equal(styles.get('--fg-swal-width'), '640px');
    assert.equal(opened, true);
    assert.equal(options.showConfirmButton, false);
    assert.match(options.customClass.popup, /fg-swal--loading/);
});

test('FG owns feedback and select focus without competing global theme rules', () => {
    const read = path => readFileSync(new URL('../../' + path, import.meta.url), 'utf8');
    assert.match(read('resources/css/System/fg/index.css'), /sweetalert2\.css" layer\(fg-vendor\)/);
    assert.doesNotMatch(read('resources/views/System/layouts/partials/fg-up.blade.php'), /sweetalert2\.css/);
    assert.doesNotMatch(read('resources/css/System/br-branding/81-core-roles-entities.css'), /\.swal2-modal\.swal2-popup \.swal2-title\s*\{/);
    assert.doesNotMatch(read('resources/css/System/fg/feedback.css'), /!important/);
    const fields = read('resources/css/System/fg/components.css');
    assert.match(fields, /\.vs__search:focus-visible\s*\{\s*outline: none/);
    assert.match(fields, /fg-field__actions > :not\(:first-child\)/);
    const select = read('resources/js/System/Components/Figma/FgSelect.vue');
    assert.match(select, /default: positionSelectDropdown/);
    assert.match(select, /append-to-body :calculate-position="calculatePosition"/);
});

test('select overlay flips near the viewport edge, follows scrolling and releases all observers', () => {
    const previous = {window: globalThis.window, ResizeObserver: globalThis.ResizeObserver};
    const listeners = new Map();
    let callback;
    let disconnected = false;
    let rect = {left: 20, top: 600, bottom: 636, width: 300};
    const rootStyles = new Map();
    const classes = new Set();
    globalThis.window = {innerWidth: 390, innerHeight: 700, addEventListener: (name, fn) => listeners.set(name, fn), removeEventListener: name => listeners.delete(name), requestAnimationFrame: fn => {callback = fn; return 1;}, cancelAnimationFrame() {}};
    globalThis.ResizeObserver = class {observe() {} disconnect() {disconnected = true;}};
    try {
        const dropdown = {scrollHeight: 240, style: {}, dataset: {}, classList: {add: value => classes.add(value)}};
        const select = {$refs: {toggle: {isConnected: true, getBoundingClientRect: () => rect}}, $el: {classList: {contains: () => true}, style: {setProperty: (key, value) => rootStyles.set(key, value), removeProperty: key => rootStyles.delete(key)}}};
        const dispose = positionSelectDropdown(dropdown, select);
        callback();
        assert.equal(dropdown.dataset.placement, 'top');
        assert.ok(parseFloat(dropdown.style.top) + 240 < rect.top);
        assert.ok(classes.has('fg-select-menu'));
        assert.equal(rootStyles.size, 0, 'Opening must not reserve space in the field');
        rect = {...rect, top: 100, bottom: 136};
        listeners.get('scroll')();
        callback();
        assert.equal(dropdown.dataset.placement, 'bottom');
        assert.equal(dropdown.style.top, '140px');
        assert.equal(rootStyles.size, 0, 'Repositioning must not resize the field');
        dispose();
        assert.equal(listeners.size, 0);
        assert.equal(rootStyles.size, 0);
        assert.equal(disconnected, true);
    } finally {
        Object.assign(globalThis, previous);
    }
});
