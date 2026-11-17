<?php

use App\Http\Controllers\System\Warehouses\{StockManagementController};
use Illuminate\Support\Facades\{Route};

$entity = "stocks_management";

Route::get("", [StockManagementController::class, "index"])->name("$entity.index");
Route::get("/initParams", [StockManagementController::class, "initParams"])->name("$entity.initParams");
Route::get("/list", [StockManagementController::class, "list"])->name("$entity.list");
Route::get("/summary", [StockManagementController::class, "summary"])->name("$entity.summary");
Route::get("/movements", [StockManagementController::class, "movements"])->name("$entity.movements");
Route::get("/traceability", [StockManagementController::class, "traceability"])->name("$entity.traceability");
Route::get("/alerts", [StockManagementController::class, "alerts"])->name("$entity.alerts");
Route::get("/alerts/export", [StockManagementController::class, "exportAlerts"])->name("$entity.alerts.export");
Route::get("/operations/template", [StockManagementController::class, "operationTemplate"])->name("$entity.operations.template");
Route::post("/operations/preview", [StockManagementController::class, "previewOperation"])->name("$entity.operations.preview");
Route::get("/guides", [StockManagementController::class, "guides"])->name("$entity.guides");
Route::post("/guides", [StockManagementController::class, "storeGuide"])->name("$entity.guides.store");
Route::post("/movements", [StockManagementController::class, "storeMovement"])->name("$entity.movements.store");
Route::post("/operations", [StockManagementController::class, "storeOperations"])->name("$entity.operations.store");
Route::post("/stock-levels", [StockManagementController::class, "updateStockLevels"])->name("$entity.levels.update");
Route::get("/transfers/availability", [StockManagementController::class, "transferAvailability"])->name("$entity.transfers.availability");
Route::get("/transfers", [StockManagementController::class, "transfers"])->name("$entity.transfers.list");
Route::get("/transfers/{transfer}", [StockManagementController::class, "showTransfer"])->whereNumber("transfer")->name("$entity.transfers.show");
Route::get("/transfers/{transfer}/print", [StockManagementController::class, "printTransfer"])->whereNumber("transfer")->name("$entity.transfers.print");
Route::patch("/transfers/{transfer}/status", [StockManagementController::class, "advanceTransfer"])->whereNumber("transfer")->name("$entity.transfers.advance");
Route::post("/transfers", [StockManagementController::class, "storeTransfer"])->name("$entity.transfers.store");
Route::get("/export", [StockManagementController::class, "export"])->name("$entity.export");
