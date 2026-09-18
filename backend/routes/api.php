<?php

use App\Http\Controllers\Api\Store\BookController;
use Illuminate\Support\Facades\Route;

Route::prefix('store')->name('api.store.')->group(function () {
    Route::get('books', [BookController::class, 'index'])->name('books.index');
    Route::get('books/{slug}', [BookController::class, 'show'])->name('books.show');
});
