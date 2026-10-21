import assert from "node:assert/strict";
import {readFileSync} from "node:fs";
import test from "node:test";

const read = (path) => readFileSync(new URL(`../../${path}`, import.meta.url), "utf8");

test("toma física descarga plantilla y revisa el archivo antes de guardar", () => {

    const page = read("resources/js/System/Pages/Warehouses/inventory/InventoryPage.vue");
    const routes = read("routes/System/Warehouses/StockManagement.php");
    const requestRoutes = read("resources/js/System/Helpers/Requests.js");
    const preview = read("app/Imports/System/Warehouses/InventoryOperationPreviewImport.php");
    const controller = read("app/Http/Controllers/System/Warehouses/StockManagementController.php");
    const alerts = read("resources/js/System/Helpers/Alerts.js");
    const picker = read("resources/js/System/Components/Generics/TemplateFilePicker.vue");
    const pickerStyles = read("resources/css/System/br-branding/30-modals-fields-forms.css");
    const inventoryStyles = read("resources/css/System/br-branding/82-inventory.css");

    assert.match(page, /Productos incluidos[\s\S]*?<TemplateFilePicker[\s\S]*?id="operationFile"[\s\S]*?@download="downloadOperationTemplate"[\s\S]*?@select="previewOperation"/);
    assert.match(page, /máximo 100 productos\./);
    assert.doesNotMatch(page, /Completa Saldo contado en la plantilla\./);
    assert.match(inventoryStyles, /\.br-inventory-operation__template \{[\s\S]*?margin-bottom: 0\.45rem;/);
    assert.match(page, /this\.previewingOperation = true;\s*Alerts\.loading\(\{message: "Procesando plantilla"\}\);[\s\S]*?Requests\.post\([\s\S]*?Alerts\.close\(\)/);
    assert.match(picker, /br-template-file-picker__name[\s\S]*?Descargar plantilla[\s\S]*?aria-label="Subir plantilla"[\s\S]*?Subir plantilla/);
    assert.match(picker, /const file = event\.target\.files\?\.\[0\];[\s\S]*?event\.target\.value = "";[\s\S]*?\$emit\("select", file\)/);
    assert.doesNotMatch(page, /Archivo de toma física<\/label>/);
    assert.match(page, /result\.data\.data\.items[\s\S]*?this\.movementForm\.items = importedItems\.map/);
    assert.match(page, /No se pudo procesar el archivo[\s\S]*?Archivo procesado/);
    assert.match(page, /Alerts\.confirmAction\([\s\S]*?Sí, registrar/);
    assert.match(page, /const confirmed = await Alerts\.confirmAction\([\s\S]*?if\(!confirmed\) return;[\s\S]*?Requests\.post\(/);
    assert.match(alerts, /export async function confirmAction\(/);
    assert.match(alerts, /export function showMessage\([\s\S]*?title,[\s\S]*?text,[\s\S]*?icon: type/);
    assert.doesNotMatch(page, /placeholder="Describe por qué se realiza esta operación"/);
    assert.match(pickerStyles, /br-template-file-picker__action:hover,[\s\S]*?text-decoration: none;/);
    assert.match(page, /async saveMovement\(\)[\s\S]*?origin_type: operation\?\.originType/);
    assert.match(routes, /operations\/template[\s\S]*?operations\/preview/);
    assert.match(requestRoutes, /operationTemplate: "operations\/template"[\s\S]*?operationPreview: "operations\/preview"/);
    assert.match(preview, /MAX_ITEMS = 100[\s\S]*?whereIn\("internal_code"/);
    assert.match(controller, /operationTemplate\(Request \$request\)[\s\S]*?previewOperation\(PreviewInventoryOperationRequest \$request\)/);
    assert.doesNotMatch(preview, /InventoryMovementService::apply|warehouse_items.*update/);

});
