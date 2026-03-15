<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\GatewayController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\ClientController;

Route::post('/login', [AuthController::class, 'login']);

Route::post('/purchase', [PurchaseController::class, 'purchase']);

Route::middleware('auth:sanctum')->group(function () {

    Route::apiResource('products', ProductController::class);
    Route::get('/transactions', [TransactionController::class, 'index']);
    Route::get('/transactions/{id}', [TransactionController::class, 'show']);
    Route::post('/transactions/{id}/refund', [TransactionController::class, 'refund']);
    Route::get('/clients', [ClientController::class, 'index']);
    Route::get('/clients/{id}', [ClientController::class, 'show']);
    Route::patch('/gateways/{id}/toggle', [GatewayController::class, 'toggle']);
    Route::patch('/gateways/{id}/priority', [GatewayController::class, 'priority']);
});