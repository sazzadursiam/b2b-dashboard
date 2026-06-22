<?php

use App\Http\Controllers\Api\V1\AnalyticsController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Middleware\SetCurrentBusiness;
use Illuminate\Support\Facades\Route;

Route::post('/v1/login', [AuthController::class, 'login']);

Route::middleware(['auth:sanctum', SetCurrentBusiness::class])->prefix('v1')->group(function () {
    Route::middleware('throttle:analytics')->get('/analytics/dashboard', [AnalyticsController::class, 'dashboard']);

    Route::get('/orders', [OrderController::class, 'index']);
    Route::post('/orders', [OrderController::class, 'store']);
    Route::patch('/orders/{order}/status', [OrderController::class, 'updateStatus']);
});
