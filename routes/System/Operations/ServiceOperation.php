<?php

declare(strict_types=1);

use App\Http\Controllers\System\Operations\{ServiceOperationController};
use Illuminate\Support\Facades\{Route};

Route::get("/restaurant", [ServiceOperationController::class, "index"])->name("restaurant_pos.index");
Route::get("/services", [ServiceOperationController::class, "index"])->name("service_sessions.index");
Route::get("/initParams", [ServiceOperationController::class, "initParams"])->name("service_operations.initParams");
Route::get("/board", [ServiceOperationController::class, "board"])->name("service_operations.board");
Route::get("/floors", [ServiceOperationController::class, "floors"])->name("service_operations.floors");
Route::post("/floors", [ServiceOperationController::class, "storeFloor"])->name("service_operations.floors.store");
Route::patch("/floors/{id}", [ServiceOperationController::class, "updateFloor"])->name("service_operations.floors.update");
Route::get("/stations", [ServiceOperationController::class, "stations"])->name("service_operations.stations");
Route::post("/stations", [ServiceOperationController::class, "storeStation"])->name("service_operations.stations.store");
Route::patch("/stations/{id}", [ServiceOperationController::class, "updateStation"])->name("service_operations.stations.update");
Route::patch("/stations/{id}/layout", [ServiceOperationController::class, "updateStationLayout"])->name("service_operations.stations.layout");
Route::get("/sessions", [ServiceOperationController::class, "sessions"])->name("service_operations.sessions");
Route::get("/reports", [ServiceOperationController::class, "reports"])->name("service_operations.reports");
Route::post("/sessions", [ServiceOperationController::class, "openSession"])->name("service_operations.sessions.store");
Route::get("/sessions/{id}", [ServiceOperationController::class, "show"])->name("service_operations.sessions.show");
Route::post("/sessions/{id}/items", [ServiceOperationController::class, "addItem"])->name("service_operations.items.store");
Route::patch("/sessions/{id}/start", [ServiceOperationController::class, "startSession"])->name("service_operations.sessions.start");
Route::patch("/sessions/{id}/complete", [ServiceOperationController::class, "completeSession"])->name("service_operations.sessions.complete");
Route::patch("/sessions/{id}/reassign", [ServiceOperationController::class, "reassignSession"])->name("service_operations.sessions.reassign");
Route::post("/sessions/{id}/pause", [ServiceOperationController::class, "pauseSession"])->name("service_operations.sessions.pause");
Route::patch("/sessions/{id}/resume", [ServiceOperationController::class, "resumeSession"])->name("service_operations.sessions.resume");
Route::patch("/sessions/{id}/cancel", [ServiceOperationController::class, "cancelSession"])->name("service_operations.sessions.cancel");
Route::patch("/items/{id}/start", [ServiceOperationController::class, "startItem"])->name("service_operations.items.start");
Route::patch("/items/{id}/complete", [ServiceOperationController::class, "completeItem"])->name("service_operations.items.complete");
Route::patch("/items/{id}/preparation-status", [ServiceOperationController::class, "updatePreparationStatus"])
    ->name("service_operations.items.preparation-status");
