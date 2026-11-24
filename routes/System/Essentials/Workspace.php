<?php

use App\Http\Controllers\System\Essentials\{WorkspaceController};
use Illuminate\Support\Facades\{Route};

Route::get("", [WorkspaceController::class, "index"])->name("home.index");
Route::get("data", [WorkspaceController::class, "data"])->name("home.data");
