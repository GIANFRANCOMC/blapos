<?php

declare(strict_types=1);

use App\Http\Controllers\System\Organizations\{UserController};
use Illuminate\Support\Facades\{Route};

$entity = "users";

Route::get("", [UserController::class, "index"])->name("$entity.index");
Route::get("/initParams", [UserController::class, "initParams"])->name("$entity.initParams");
Route::get("/list", [UserController::class, "list"])->name("$entity.list");
Route::get("/export", [UserController::class, "export"])->name("$entity.export");
Route::get("/import", [UserController::class, "importTemplate"])->name("$entity.import.template");
Route::post("/import", [UserController::class, "import"])->name("$entity.import");
Route::post("", [UserController::class, "store"])->name("$entity.store");
Route::patch("/{id}", [UserController::class, "update"])->name("$entity.update");
Route::patch("/{id}/password", [UserController::class, "changePassword"])->name("$entity.password.update");
Route::get("/{id}/authentication-events", [UserController::class, "authenticationEvents"])->name("$entity.authentication-events");
Route::post("/{id}/biometric-fingerprints", [UserController::class, "registerBiometricFingerprint"])->name("{$entity}.biometric-fingerprints.store");
