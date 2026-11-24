<?php

use App\Http\Controllers\System\Essentials\{HomeController};
use Illuminate\Support\Facades\{Route};

$entity = "favorites";

Route::get("", [HomeController::class, "index"])->name("$entity.index");
Route::get("/initParams", [HomeController::class, "initParams"])->name("$entity.initParams");
Route::patch("/{id}", [HomeController::class, "update"])->whereNumber("id")->name("$entity.update");
