<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminMealController;
use App\Http\Controllers\Admin\AdminOrderController;

Route::prefix('admin')->group(function () {
    Route::get('/', function () {
        return view('admin.dashboard');
    })->name('admin.dashboard');

    Route::get('/meals', [AdminMealController::class, 'index'])->name('admin.meals');
    Route::get('/orders', [AdminOrderController::class, 'index'])->name('admin.orders');

    Route::post('/meals/{id}/enrich', [AdminMealController::class, 'enrich'])->name('admin.meals.enrich');
});

Route::get('/{any}', function () {
    return view('welcome');
})->where('any', '.*');
