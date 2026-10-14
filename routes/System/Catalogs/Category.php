<?php

declare(strict_types=1);

use App\Http\Controllers\System\Catalogs\{CategoryController};
use Illuminate\Support\Facades\{Route};

$entity = "categories";

Route::get("", [CategoryController::class, "index"])->name("$entity.index");
Route::get("/initParams", [CategoryController::class, "initParams"])->name("$entity.initParams");
Route::get("/list", [CategoryController::class, "list"])->name("$entity.list");
Route::get("/export", [CategoryController::class, "export"])->name("$entity.export");
Route::get("/import", [CategoryController::class, "importTemplate"])->name("$entity.import.template");
Route::post("/import", [CategoryController::class, "import"])->name("$entity.import");
Route::post("", [CategoryController::class, "store"])->name("$entity.store");
Route::patch("/{id}", [CategoryController::class, "update"])->name("$entity.update");
Route::delete("/{id}", [CategoryController::class, "destroy"])->name("{$entity}.destroy");
