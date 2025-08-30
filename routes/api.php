<?php

declare(strict_types = 1);

use App\Http\Controllers\Api;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/login', Api\Auth\LoginController::class);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', fn (Request $request) => $request->user());
    Route::post('/logout', Api\Auth\LogoutController::class);

    Route::prefix('admin')->group(function () {
        Route::apiResource('employees', Api\Admin\EmployeesController::class);
    });
});
