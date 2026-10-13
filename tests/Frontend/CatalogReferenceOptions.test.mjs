import assert from "node:assert/strict";
import test from "node:test";
import {upsertCatalogOption} from "../../resources/js/System/Helpers/CatalogReferenceOptions.js";

test("agrega y ordena categorías compartidas por el catálogo", () => {

    const options = {categories: {records: [{id: 2, name: "Zumba"}]}};

    const selected = upsertCatalogOption(options, "categories", {id: 1, name: "Aeróbicos"});

    assert.deepEqual(options.categories.records.map(record => record.id), [1, 2]);
    assert.deepEqual(selected, {code: 1, label: "Aeróbicos", data: {id: 1, name: "Aeróbicos"}});

});

test("actualiza la opción creada sin duplicarla", () => {

    const options = {categories: {records: [{id: 1, name: "Anterior"}]}};

    upsertCatalogOption(options, "categories", {id: 1, name: "Actualizada"});

    assert.equal(options.categories.records.length, 1);
    assert.equal(options.categories.records[0].name, "Actualizada");

});

test("inicializa el catálogo cuando aún no tiene opciones", () => {

    const options = {};

    upsertCatalogOption(options, "categories", {id: 3, name: "General"});

    assert.equal(options.categories.records[0].id, 3);
    assert.equal(upsertCatalogOption(options, "categories", null), null);

});
