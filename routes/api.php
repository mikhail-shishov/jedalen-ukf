<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/meals', function () {
    return response()->json([
        ['id' => 1, 'name' => 'test', 'price' => 4.30],
        ['id' => 2, 'name' => 'test2', 'price' => 3.80],
    ]);
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});