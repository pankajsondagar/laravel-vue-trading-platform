<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TradingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Authentication
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Trading
    Route::get('/profile', [TradingController::class, 'profile']);
    Route::get('/orders', [TradingController::class, 'orders']);
    Route::get('/orderbook', [TradingController::class, 'orderbook']);
    Route::post('/orders', [TradingController::class, 'createOrder']);
    Route::post('/orders/{id}/cancel', [TradingController::class, 'cancelOrder']);
});