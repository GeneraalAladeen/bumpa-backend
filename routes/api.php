<?php

use App\Http\Controllers\AdminLoyaltyController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\LoyaltyController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', fn (Request $request) => $request->user());

    Route::get('/users/{user}/achievements', [LoyaltyController::class, 'show']);
    Route::post('/purchases', [LoyaltyController::class, 'purchase']);

    Route::middleware('admin')->prefix('admin')->group(function () {
        Route::get('/users/achievements', [AdminLoyaltyController::class, 'index']);
    });
});
