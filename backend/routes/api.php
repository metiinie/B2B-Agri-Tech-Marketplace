<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CapabilityApplicationController;
use App\Http\Controllers\ListingController;
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

/*
|--------------------------------------------------------------------------
| Listings — Public (browse & search)
|--------------------------------------------------------------------------
*/
Route::get('/listings',      [ListingController::class, 'index']);
Route::get('/listings/{id}', [ListingController::class, 'show']);

/*
|--------------------------------------------------------------------------
| Listings — Farmer (authenticated, requires farmer capability)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/listings/my',        [ListingController::class, 'my']);
    Route::post('/listings',          [ListingController::class, 'store']);
    Route::put('/listings/{id}',      [ListingController::class, 'update']);
    Route::delete('/listings/{id}',   [ListingController::class, 'destroy']);
});
