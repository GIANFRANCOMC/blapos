<?php

use App\Http\Controllers\System\Essentials\{CommercialPerformanceController};
use Illuminate\Support\Facades\{Route};

Route::name("analytics.commercial_performance.")->group(function(): void {
    Route::get("/", [CommercialPerformanceController::class, "index"])->name("index");
    Route::get("/initParams", [CommercialPerformanceController::class, "initParams"])->name("initParams");
    Route::get("/list", [CommercialPerformanceController::class, "list"])->name("list");
});
