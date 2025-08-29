<?php

declare(strict_types = 1);

use App\Http\Controllers\Api;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/login', Api\Auth\LoginController::class);

Route::get('/user', fn (Request $request) => $request->user())->middleware('auth:sanctum');
