import assert from 'node:assert/strict';
import test from 'node:test';
import {readFileSync} from 'node:fs';
import {productToday, validateProductExpiry} from '../../resources/js/System/Components/Catalogs/Products/productDate.js';
import {parse} from '@vue/compiler-sfc';

test('new product date uses the business timezone and only creation rejects past dates', () => {
    const today = productToday('America/Lima', new Date('2026-11-21T03:30:00Z'));
    assert.equal(today, '2026-11-20');
    for(const [expires_at, isUpdate, valid] of [['2026-11-19', false, false], ['2026-11-20', false, true], ['2026-11-21', false, true], ['', false, true], ['2026-11-19', true, true]]) {
        const result = {bool: true, errors: {}};
        validateProductExpiry(result, {expires_at}, {isUpdate, today});
        assert.equal(result.bool, valid);
        assert.equal(Boolean(result.errors.expires_at), !valid);
    }
});

test('changing order resets pagination and preserves applied search rather than pending filter edits', async () => {
    const src = readFileSync('resources/js/System/Pages/Catalogs/products/main.vue', 'utf8');
    const script = parse(src).descriptor.script.content.replace(/^import .*;\s*$/gm, '').replace('export default', 'return');
    const imported = [...src.matchAll(/^import ([A-Za-z]\w*) from/gm)].map(match => match[1]);
    const component = new Function(...imported, 'PRODUCT_TEXTS', script)(...imported.map(() => ({})), {});
    const requests = [];
    const ctx = {sortDirection: 'asc', entityList: {filters: {word: 'pendiente'}, appliedFilters: {filter_by: {code: 'name'}, word: 'café'}}, MODULE: {config: {perPage: 10}}, listEntity: async params => requests.push(params)};
    await component.methods.changeSort.call(ctx, 'desc');
    assert.deepEqual(requests, [{}]);
    assert.deepEqual(component.methods.getListFilters.call(ctx), {filter_by: 'name', word: 'café', sort_direction: 'desc'});
    await component.methods.changeSort.call(ctx, 'invalid');
    assert.equal(requests.length, 1);
});
