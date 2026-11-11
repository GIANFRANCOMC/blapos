<?php

use App\Http\Controllers\System\Sales\{SaleController};
use Illuminate\Support\Facades\{Route};

$entity = "sales";

Route::get("", [SaleController::class, "index"])->name("$entity.index");
Route::get("/initParams", [SaleController::class, "initParams"])->name("$entity.initParams");
Route::get("/list", [SaleController::class, "list"])->name("$entity.list");
Route::get("/create", [SaleController::class, "create"])->name("$entity.create");
Route::get("/pos", [SaleController::class, "pos"])->name("$entity.pos");
Route::get("/page/deliveries", [SaleController::class, "deliveriesPage"])->name("$entity.deliveries.index");
Route::get("/deliveries", [SaleController::class, "deliveries"])->name("$entity.deliveries");
Route::post("", [SaleController::class, "store"])->name("$entity.store");
Route::patch("/deliveries/{id}", [SaleController::class, "deliver"])->name("$entity.deliveries.deliver");
Route::patch("/cancel/{id}", [SaleController::class, "cancel"])->name("$entity.cancel");
