<?php

use App\Http\Controllers\BookCategoryController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InventoryController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::resource('books', BookController::class)->except(['create', 'show', 'edit']);
    Route::resource('book-categories', BookCategoryController::class)->except(['create', 'show', 'edit']);
    Route::get('inventory', [InventoryController::class, 'index'])->name('inventory.index');
    Route::get('inventory/movements', [InventoryController::class, 'movements'])->name('inventory.movements');
    Route::get('inventory/{book}', [InventoryController::class, 'show'])->name('inventory.show');
    Route::post('inventory/{book}/restock', [InventoryController::class, 'restock'])->name('inventory.restock');
    Route::post('inventory/{book}/adjust', [InventoryController::class, 'adjust'])->name('inventory.adjust');
});

require __DIR__.'/settings.php';
