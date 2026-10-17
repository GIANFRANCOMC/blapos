<?php

use App\Http\Controllers\System\Warehouses\{InventoryPageController};
use Illuminate\Support\Facades\{Route};

Route::get("/stock", [InventoryPageController::class, "stock"])->name("stocks_management.stock.index");
Route::get("/transfers", [InventoryPageController::class, "transfers"])->name("stocks_management.transfers.index");
Route::get("/guides", [InventoryPageController::class, "guides"])->name("stocks_management.guides.index");
Route::get("/kardex", [InventoryPageController::class, "kardex"])->name("stocks_management.kardex.index");
Route::get("/valued-kardex", [InventoryPageController::class, "valuedKardex"])->name("stocks_management.valued.index");
