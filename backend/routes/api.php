<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CapabilityApplicationController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\ChapaWebhookController;
use App\Http\Controllers\ListingController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrderFulfillmentController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PaymentExceptionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
///// Authcontroller //////////////
Route::post('/auth/request-otp', [AuthController::class, 'requestOtp']);
Route::post('/auth/register',    [AuthController::class, 'register']);
Route::post('/auth/login',       [AuthController::class, 'login']);

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


////////// Capability Applications (authenticated users)/////

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/capability-applications',     [CapabilityApplicationController::class, 'store']);
    Route::get('/capability-applications/my',   [CapabilityApplicationController::class, 'my']);
    Route::get('/capability-applications/{id}', [CapabilityApplicationController::class, 'show']);
});

//// Admin — Capability Applications ///////////

Route::middleware('auth:sanctum')->prefix('admin')->group(function () {
    Route::get('/capability-applications',              [CapabilityApplicationController::class, 'index']);
    Route::post('/capability-applications/{id}/approve', [CapabilityApplicationController::class, 'approve']);
    Route::post('/capability-applications/{id}/reject',  [CapabilityApplicationController::class, 'reject']);
});


// Listings — Farmer (authenticated, requires farmer capability)////////


Route::middleware('auth:sanctum')->group(function () {
    Route::get('/listings/my',        [ListingController::class, 'my']);
    Route::post('/listings',          [ListingController::class, 'store']);
    Route::put('/listings/{id}',      [ListingController::class, 'update']);
    Route::delete('/listings/{id}',   [ListingController::class, 'destroy']);
});

//////Listings — Public (browse & search)/////
Route::get('/listings',      [ListingController::class, 'index']);
Route::get('/listings/{id}', [ListingController::class, 'show']);

////// Cart — Buyer (authenticated, requires buyer capability) /////

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/cart',          [CartController::class, 'index']);
    Route::post('/cart',         [CartController::class, 'store']);
    Route::put('/cart/{id}',     [CartController::class, 'update']);
    Route::delete('/cart/clear', [CartController::class, 'clear']);
    Route::delete('/cart/{id}',  [CartController::class, 'destroy']);
});

////// Orders — Buyer (authenticated, requires buyer capability) /////

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/orders',              [OrderController::class, 'index']);
    Route::get('/orders/{id}',         [OrderController::class, 'show']);
    Route::post('/orders/checkout',    [OrderController::class, 'checkout']);
    Route::post('/orders/{id}/cancel', [OrderController::class, 'cancel']);
});

////// Fulfillments — Farmer (authenticated, requires farmer capability) /////

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/fulfillments',                [OrderFulfillmentController::class, 'index']);
    Route::get('/fulfillments/{id}',           [OrderFulfillmentController::class, 'show']);
    Route::post('/fulfillments/{id}/accept',   [OrderFulfillmentController::class, 'accept']);
    Route::post('/fulfillments/{id}/reject',   [OrderFulfillmentController::class, 'reject']);
    Route::post('/fulfillments/{id}/complete', [OrderFulfillmentController::class, 'complete']);
});

////// Payments — Buyer (authenticated, requires buyer capability) /////

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/orders/{id}/pay',     [PaymentController::class, 'initiate']);
    Route::get('/orders/{id}/payment',  [PaymentController::class, 'show']);
});

////// Chapa Webhook (public — no auth, verified by signature) /////

Route::post('/payments/webhook', [ChapaWebhookController::class, 'handle']);

////// Payment Exceptions — Authenticated users (buyer/farmer) /////

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/payment-exceptions',         [PaymentExceptionController::class, 'store']);
    Route::get('/payment-exceptions/my',       [PaymentExceptionController::class, 'my']);
    Route::get('/payment-exceptions/{id}',     [PaymentExceptionController::class, 'show']);
});

////// Admin — Payment Exceptions /////

Route::middleware('auth:sanctum')->prefix('admin')->group(function () {
    Route::get('/payment-exceptions',                    [PaymentExceptionController::class, 'index']);
    Route::post('/payment-exceptions/{id}/investigate',  [PaymentExceptionController::class, 'investigate']);
    Route::post('/payment-exceptions/{id}/resolve',      [PaymentExceptionController::class, 'resolve']);
    Route::post('/payment-exceptions/{id}/reject',       [PaymentExceptionController::class, 'reject']);
});

