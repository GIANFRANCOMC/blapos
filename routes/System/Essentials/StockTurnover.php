<?php

use App\Http\Controllers\System\Essentials\{StockTurnoverController};
use Illuminate\Support\Facades\{Route};

Route::name("analytics.stock_turnover_replenishment.")->group(function(): void {
    Route::get("/", [StockTurnoverController::class, "index"])->name("index");
    Route::get("/initParams", [StockTurnoverController::class, "initParams"])->name("initParams");
    Route::get("/list", [StockTurnoverController::class, "list"])->name("list");
});
