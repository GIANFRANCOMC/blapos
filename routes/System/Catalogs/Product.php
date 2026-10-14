<?php

declare(strict_types=1);

use App\Http\Controllers\System\Catalogs\{ProductController};
use Illuminate\Support\Facades\{Route};

$entity = "products";

Route::get("", [ProductController::class, "index"])->name("$entity.index");
Route::get("/initParams", [ProductController::class, "initParams"])->name("$entity.initParams");
Route::get("/list", [ProductController::class, "list"])->name("$entity.list");
Route::get("/export", [ProductController::class, "export"])->name("$entity.export");
Route::get("/import", [ProductController::class, "importTemplate"])->name("$entity.import.template");
Route::post("/import", [ProductController::class, "import"])->name("$entity.import");
Route::post("", [ProductController::class, "store"])->name("$entity.store");
Route::patch("/{id}", [ProductController::class, "update"])->name("$entity.update");
