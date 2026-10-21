<?php

use App\Http\Controllers\System\Warehouses\{StockManagementController};
use Illuminate\Support\Facades\{Route};

$entity = "stocks_management";

Route::get("", [StockManagementController::class, "index"])->name("$entity.index");
Route::get("/initParams", [StockManagementController::class, "initParams"])->name("$entity.initParams");
Route::get("/list", [StockManagementController::class, "list"])->name("$entity.list");
Route::get("/summary", [StockManagementController::class, "summary"])->name("$entity.summary");
Route::get("/movements", [StockManagementController::class, "movements"])->name("$entity.movements");
Route::get("/alerts", [StockManagementController::class, "alerts"])->name("$entity.alerts");
Route::get("/physical-count/template", [StockManagementController::class, "physicalCountTemplate"])->name("$entity.physicalCount.template");
Route::post("/physical-count/preview", [StockManagementController::class, "previewPhysicalCount"])->name("$entity.physicalCount.preview");
Route::get("/guides", [StockManagementController::class, "guides"])->name("$entity.guides");
Route::post("/guides", [StockManagementController::class, "storeGuide"])->name("$entity.guides.store");
Route::post("/movements", [StockManagementController::class, "storeMovement"])->name("$entity.movements.store");
Route::post("/operations", [StockManagementController::class, "storeOperations"])->name("$entity.operations.store");
Route::post("/transfers", [StockManagementController::class, "storeTransfer"])->name("$entity.transfers.store");
Route::get("/export", [StockManagementController::class, "export"])->name("$entity.export");
