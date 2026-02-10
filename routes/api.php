<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PasswordController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\VerificationController;

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
    });
});

Route::prefix('password')->group(function () {
    Route::post('/forgot', [PasswordController::class, 'forgot']);
    Route::post('/reset', [PasswordController::class, 'reset']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/update', [PasswordController::class, 'update']);
    });
});

Route::middleware('auth:sanctum')->prefix('email')->group(function () {
    Route::post('/send-code', [VerificationController::class, 'sendCode']);
    Route::post('/verify-code', [VerificationController::class, 'verifyCode']);
});

Route::post('/contact', [ContactController::class, 'send']);