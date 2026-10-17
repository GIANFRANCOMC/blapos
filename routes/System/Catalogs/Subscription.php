<?php

declare(strict_types=1);

use App\Http\Controllers\System\Catalogs\{SubscriptionController};
use Illuminate\Support\Facades\{Route};

$entity = "subscriptions";

Route::get("", [SubscriptionController::class, "index"])->name("$entity.index");
Route::get("/initParams", [SubscriptionController::class, "initParams"])->name("$entity.initParams");
Route::get("/list", [SubscriptionController::class, "list"])->name("$entity.list");
Route::get("/export", [SubscriptionController::class, "export"])->name("$entity.export");
Route::get("/import", [SubscriptionController::class, "importTemplate"])->name("$entity.import.template");
Route::post("/import", [SubscriptionController::class, "import"])->name("$entity.import");
Route::post("", [SubscriptionController::class, "store"])->name("$entity.store");
Route::patch("/{id}", [SubscriptionController::class, "update"])->name("$entity.update");
