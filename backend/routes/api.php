<?php

use App\Http\Controllers\Api\PayHeroCallbackController;
use App\Http\Controllers\Api\Store\BookController;
use App\Http\Controllers\Api\Store\CartController;
use App\Http\Controllers\Api\Store\CheckoutController;
use App\Http\Controllers\Api\Store\OrderController;
use App\Http\Controllers\Api\Store\PayHeroPaymentController;
use App\Http\Controllers\Api\Store\PickupLocationController;
use Illuminate\Support\Facades\Route;

Route::post('payments/payhero/callback', PayHeroCallbackController::class)->name('payments.payhero.callback');

Route::prefix('store')->name('api.store.')->group(function () {
    Route::get('books', [BookController::class, 'index'])->name('books.index');
    Route::get('books/{slug}', [BookController::class, 'show'])->name('books.show');
    Route::get('pickup-locations', [PickupLocationController::class, 'index'])->name('pickup-locations.index');

    Route::middleware('throttle:store-checkout')->group(function () {
        Route::post('cart/quote', [CartController::class, 'quote'])->name('cart.quote');
        Route::post('checkout', [CheckoutController::class, 'store'])->name('checkout.store');
        Route::get('orders/{orderNumber}', [OrderController::class, 'show'])->name('orders.show');
    });

    Route::middleware('throttle:store-payments')->group(function () {
        Route::post('orders/{orderNumber}/payments/payhero', [PayHeroPaymentController::class, 'store'])->name('orders.payments.payhero.store');
    });
});
