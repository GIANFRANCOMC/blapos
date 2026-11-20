import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
import test from 'node:test';
import * as Vue from 'vue';
import VueSelect from 'vue-select';
import {positionSelectDropdown} from '../../resources/js/System/Helpers/SelectOverlay.js';
import {renderToString} from 'vue/server-renderer';
import {parse, compileScript} from '@vue/compiler-sfc';
import * as Numbers from '../../resources/js/Shared/Numbers.js';

const root = new URL('../../resources/js/System/Components/', import.meta.url);
const generalConfig = {forms: {inputs: {required: '*', maxlength: 999}, errors: {styles: {default: 'text-danger'}}}};

function component(file, dependencies = {}) {
    const {descriptor, errors} = parse(readFileSync(new URL(file, root), 'utf8'), {filename: file});
    assert.deepEqual(errors, []);
    const compiled = descriptor.scriptSetup ? compileScript(descriptor, {id: file, inlineTemplate: true}) : descriptor.script;
    const scope = {...dependencies};
    const source = compiled.content.replace(/import\s+([\s\S]*?)\s+from\s+['"]([^'"]+)['"];?/g, (_, bindings, module) => {
        if(module === 'vue') {
            for(const binding of bindings.replace(/[{}]/g, '').split(',')) {
                const [original, alias = original] = binding.trim().split(/\s+as\s+/);
                scope[alias] = Vue[original];
            }
        }
        return '';
    }).replace(/^import\s+['"][^'"]+['"];?\s*$/gm, '').replace('export default', 'return');
    const result = new Function(...Object.keys(scope), source)(...Object.values(scope));
    return {result, descriptor};
}

// Ordinary options components use the runtime template compiler; setup components inline their render.
import {compile} from '@vue/compiler-dom';
function options(file, dependencies = {}) {
    const loaded = component(file, dependencies);
    if(!loaded.descriptor.scriptSetup) loaded.result.render = new Function('Vue', compile(loaded.descriptor.template.content, {mode: 'function'}).code)(Vue);
    return loaded.result;
}
const FgField = options('Figma/FgField.vue');
const InputBase = options('InputBase.vue', {generalConfig, FgField});
const InputText = options('InputText.vue', {InputBase});
const InputNumber = options('InputNumber.vue', {generalConfig, FgField, ...Numbers});
const FgSelect = options('Figma/FgSelect.vue', {VueSelect, positionSelectDropdown});

async function render(component, props = {}, slots = {}) {
    const app = Vue.createSSRApp({render: () => Vue.h(component, props, slots)});
    return renderToString(app);
}

test('fg inputs associate the real label, required state and validation error without vendor classes', async () => {
    const html = await render(InputBase, {id: 'product-name', title: 'Nombre', modelValue: '', isRequired: true, hasTextBottom: true, textBottomInfo: ['Escribe el nombre.']});
    assert.match(html, /for="product-name"/);
    assert.match(html, /id="product-name"/);
    assert.match(html, /aria-required="true"/);
    assert.match(html, /aria-invalid="true"/);
    assert.match(html, /aria-describedby="product-name-error"/);
    assert.match(html, /id="product-name-error"[^>]*role="alert"/);
    assert.doesNotMatch(html, /class="[^"]*(?:form-control|input-group|col-xl)/);
});

test('fg text counter includes the code prefix and reserves a single counter inside the control', async () => {
    const html = await render(InputText, {title: 'Código interno', modelValue: 'ABCDEFG', maxlength: 45, counterOffset: 5, showCharCounter: true});
    assert.match(html, /maxlength="45"/);
    assert.match(html, />12<\/span> \/ 50/);
    assert.equal((html.match(/class="fg-field__counter"/g) || []).length, 1);
    assert.doesNotMatch(html, /character-counter-warning/);
});

test('shared fields use FG by default for every module without a theme provider', async () => {
    const html = await render(InputBase, {title: 'Buscar', hasDiv: true, modelValue: 'café'});
    assert.match(html, /fg-field__control/);
    assert.match(html, /value="café"/);
    assert.doesNotMatch(html, /br-form-control-group/);
});

test('fg number input keeps zero, decimal precision, disabled state and empty value distinct', async () => {
    const zero = await render(InputNumber, {title: 'Stock', modelValue: 0, decimals: 2, disabled: true});
    const empty = await render(InputNumber, {title: 'Stock', modelValue: '', decimals: 2, placeholder: 'Stock inicial'});
    assert.match(zero, /value="0\.00"/);
    assert.match(zero, / disabled/);
    assert.match(empty, /value(?:=""|(?=\s|>))/);
    assert.match(empty, /placeholder="Stock inicial"/);
});

test('fg selects retain vue-select models and expose the Spanish field name on the native search input', async () => {
    const html = await render(FgField, {label: 'Marca', inputId: 'product-brand'}, {default: () => Vue.h(FgSelect, {modelValue: {code: 1, label: 'Andes'}, options: [{code: 1, label: 'Andes'}], clearable: false})});
    assert.match(html, /id="product-brand"/);
    assert.match(html, /aria-label="Marca"/);
    assert.match(html, /Andes/);
    assert.doesNotMatch(html, /aria-labelledby="vs\d+__combobox"/);
});
