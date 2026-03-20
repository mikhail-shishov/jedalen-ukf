<?php

use Illuminate\Http\Request;
use App\Http\Controllers\Api\MenuController;
use App\Http\Controllers\Api\ArticleController;
use Illuminate\Support\Facades\Route;

Route::get('/meals', function () {
    return response()->json([
        ['id' => 1, 'name' => 'test', 'price' => 4.30],
        ['id' => 2, 'name' => 'test2', 'price' => 3.80],
    ]);
});

Route::get('/menu', [MenuController::class, 'index']);
Route::get('/canteens', [MenuController::class, 'canteens']);
Route::get('/articles', [ArticleController::class, 'index']);
Route::get('/articles/{slug}', [ArticleController::class, 'show']);

Route::middleware('auth')->get('/user', function (Request $request) {
    return $request->user();
});