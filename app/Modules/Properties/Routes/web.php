<?php

use App\Modules\Properties\Http\Controllers\BuildingController;
use App\Modules\Properties\Http\Controllers\PropertyController;
use App\Modules\Properties\Http\Controllers\UnitController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function (): void {
    Route::resource('properties', PropertyController::class);

    Route::get('properties/{property}/buildings/create', [BuildingController::class, 'create'])->name('buildings.create');
    Route::post('properties/{property}/buildings', [BuildingController::class, 'store'])->name('buildings.store');
    Route::get('buildings/{building}', [BuildingController::class, 'show'])->name('buildings.show');
    Route::get('buildings/{building}/edit', [BuildingController::class, 'edit'])->name('buildings.edit');
    Route::put('buildings/{building}', [BuildingController::class, 'update'])->name('buildings.update');
    Route::delete('buildings/{building}', [BuildingController::class, 'destroy'])->name('buildings.destroy');

    Route::get('buildings/{building}/units/create', [UnitController::class, 'create'])->name('units.create');
    Route::post('buildings/{building}/units', [UnitController::class, 'store'])->name('units.store');
    Route::get('buildings/{building}/units/create-multiple', [UnitController::class, 'createMultiple'])->name('units.create_multiple');
    Route::post('buildings/{building}/units/multiple', [UnitController::class, 'storeMultiple'])->name('units.store_multiple');
    Route::get('units/{unit}', [UnitController::class, 'show'])->name('units.show');
    Route::get('units/{unit}/edit', [UnitController::class, 'edit'])->name('units.edit');
    Route::put('units/{unit}', [UnitController::class, 'update'])->name('units.update');
    Route::delete('units/{unit}', [UnitController::class, 'destroy'])->name('units.destroy');
});
