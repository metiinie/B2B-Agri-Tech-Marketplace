<?php

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/auth/request-otp', [AuthController::class, 'requestOtp']);
Route::post('/auth/register',    [AuthController::class, 'register']);
Route::post('/auth/login',       [AuthController::class, 'login']);

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

