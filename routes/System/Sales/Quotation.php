<?php

use App\Http\Controllers\System\Sales\{QuotationController};
use Illuminate\Support\Facades\{Route};

$entity = "quotations";

Route::get("/initParams", [QuotationController::class, "initParams"])->name("$entity.initParams");
Route::get("/list", [QuotationController::class, "list"])->name("$entity.list");
Route::get("", [QuotationController::class, "index"])->name("$entity.index");
Route::get("/create", [QuotationController::class, "create"])->name("$entity.create");
Route::post("", [QuotationController::class, "store"])->name("$entity.store");
Route::get("/{id}", [QuotationController::class, "show"])->name("$entity.show");
Route::get("/{id}/sale-draft", [QuotationController::class, "saleDraft"])->name("$entity.saleDraft");
Route::patch("/{id}/cancel", [QuotationController::class, "cancel"])->name("$entity.cancel");
