<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CapabilityApplicationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
///// Authcontroller /////////
Route::post('/auth/request-otp', [AuthController::class, 'requestOtp']);
Route::post('/auth/register',    [AuthController::class, 'register']);
Route::post('/auth/login',       [AuthController::class, 'login']);

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

/*
|--------------------------------------------------------------------------
| Capability Applications (authenticated users)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/capability-applications',     [CapabilityApplicationController::class, 'store']);
    Route::get('/capability-applications/my',   [CapabilityApplicationController::class, 'my']);
    Route::get('/capability-applications/{id}', [CapabilityApplicationController::class, 'show']);
});

/*
|--------------------------------------------------------------------------
| Admin — Capability Applications
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->prefix('admin')->group(function () {
    Route::get('/capability-applications',              [CapabilityApplicationController::class, 'index']);
    Route::post('/capability-applications/{id}/approve', [CapabilityApplicationController::class, 'approve']);
    Route::post('/capability-applications/{id}/reject',  [CapabilityApplicationController::class, 'reject']);
});
