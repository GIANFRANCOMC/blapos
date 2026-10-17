<?php

declare(strict_types=1);

use App\Http\Controllers\System\Catalogs\{ServiceController};
use Illuminate\Support\Facades\{Route};

$entity = "services";

Route::get("", [ServiceController::class, "index"])->name("$entity.index");
Route::get("/initParams", [ServiceController::class, "initParams"])->name("$entity.initParams");
Route::get("/list", [ServiceController::class, "list"])->name("$entity.list");
Route::get("/export", [ServiceController::class, "export"])->name("$entity.export");
Route::get("/import", [ServiceController::class, "importTemplate"])->name("$entity.import.template");
Route::post("/import", [ServiceController::class, "import"])->name("$entity.import");
Route::post("", [ServiceController::class, "store"])->name("$entity.store");
Route::patch("/{id}", [ServiceController::class, "update"])->name("$entity.update");
