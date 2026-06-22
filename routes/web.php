<?php

use App\Http\Controllers\HealthController;
use App\Http\Controllers\Webhook\PaymentWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/health', HealthController::class);
Route::post('/webhooks/payment', [PaymentWebhookController::class, 'handle']);
