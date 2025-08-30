<?php

declare(strict_types = 1);

use App\Http\Controllers\Api;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/login', Api\Auth\LoginController::class);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', fn (Request $request) => $request->user());
    Route::post('/logout', Api\Auth\LogoutController::class);

    Route::apiResource('time-records', Api\Employee\TimeRecordController::class)
        ->only('store')
        ->middleware('throttle:timerecord');

    Route::prefix('admin')->group(function () {
        Route::get('employees/{employee}/time-records', [Api\Admin\EmployeesController::class, 'timeRecords']);
        Route::apiResource('employees', Api\Admin\EmployeesController::class);

        Route::get('time-records/report', [Api\Admin\TimeRecordsController::class, 'report']);
        Route::apiResource('time-records', Api\Admin\TimeRecordsController::class)->only('index');
    });
});
