<?php

declare(strict_types=1);

use App\Http\Controllers\System\Catalogs\{BrandController};
use Illuminate\Support\Facades\{Route};

$entity = "brands";

Route::get("", [BrandController::class, "index"])->name("$entity.index");
Route::get("/initParams", [BrandController::class, "initParams"])->name("$entity.initParams");
Route::get("/list", [BrandController::class, "list"])->name("$entity.list");
Route::get("/export", [BrandController::class, "export"])->name("$entity.export");
Route::get("/import", [BrandController::class, "importTemplate"])->name("$entity.import.template");
Route::post("/import", [BrandController::class, "import"])->name("$entity.import");
Route::post("", [BrandController::class, "store"])->name("$entity.store");
Route::patch("/{id}", [BrandController::class, "update"])->name("$entity.update");
