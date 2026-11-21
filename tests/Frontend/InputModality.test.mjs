import assert from 'node:assert/strict';
import test from 'node:test';
import {initializeInputModality} from '../../resources/js/Shared/input-modality.js';

function surface() {
    const listeners = new Map();
    const doc = {
        documentElement: {dataset: {}},
        addEventListener(type, fn) { listeners.set(type, fn); },
        removeEventListener(type, fn) { if(listeners.get(type) === fn) listeners.delete(type); }
    };
    return {doc, listeners, send(type, event = {}) {listeners.get(type)?.(event);}};
}

test('Shift and shortcuts keep pointer feedback; Tab and Shift+Tab reveal keyboard focus without blocking navigation', () => {
    const {doc, send} = surface();
    const stop = initializeInputModality(doc);
    const mode = () => doc.documentElement.dataset.fgInputMode;
    for(const key of ['Shift', 'Control', 'Alt', 'Meta', 'CapsLock']) send('keydown', {key});
    assert.equal(mode(), 'pointer');
    send('keydown', {key: 'Tab', ctrlKey: true});
    assert.equal(mode(), 'pointer');
    let prevented = false;
    send('keydown', {key: 'Tab', shiftKey: true, preventDefault() {prevented = true;}});
    assert.equal(mode(), 'keyboard');
    assert.equal(prevented, false);
    send('pointerdown');
    assert.equal(mode(), 'pointer');
    send('keydown', {key: 'Tab'});
    assert.equal(mode(), 'keyboard');
    stop();
});

test('keyboard activation and roving keys retain focus feedback, composition is ignored and teardown releases listeners', () => {
    const {doc, listeners, send} = surface();
    const stop = initializeInputModality(doc);
    const duplicate = initializeInputModality(doc);
    duplicate();
    assert.equal(listeners.size, 2);
    for(const key of ['ArrowDown', 'Home', 'End', 'PageDown', 'Enter', ' ']) {
        send('pointerdown');
        send('keydown', {key, isComposing: true});
        assert.equal(doc.documentElement.dataset.fgInputMode, 'pointer');
        send('keydown', {key});
        assert.equal(doc.documentElement.dataset.fgInputMode, 'keyboard');
    }
    stop();
    assert.equal(listeners.size, 0);
    assert.equal(doc.documentElement.dataset.fgInputMode, undefined);
});
