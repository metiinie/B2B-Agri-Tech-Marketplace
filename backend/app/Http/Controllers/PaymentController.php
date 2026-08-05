<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    /**
     * Initiate payment for a pending order via Chapa hosted checkout.
     *
     * POST /api/orders/{id}/pay
     *
     * Creates a Payment record, calls Chapa's transaction/initialize endpoint,
     * and returns the hosted checkout URL so the buyer can complete payment.
     * The payment status will transition from "pending" to "confirmed" ONLY
     * through the signed Chapa webhook — never from this controller.
     */
    public function initiate(int $id): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (! $this->hasActiveBuyerCapability($user)) {
            return response()->json([
                'message' => 'You must have an active buyer capability to initiate payments.',
            ], 403);
        }

        $order = Order::findOrFail($id);

        if ($order->buyer_id !== $user->id) {
            return response()->json([
                'message' => 'You are not authorized to pay for this order.',
            ], 403);
        }

        if ($order->status !== 'pending_payment') {
            return response()->json([
                'message' => 'This order is not awaiting payment.',
            ], 422);
        }

        // Prevent duplicate payment initiation.
        $existingPayment = Payment::where('order_id', $order->id)
            ->whereIn('status', ['pending', 'confirmed'])
            ->first();

        if ($existingPayment) {
            // If there is already a pending payment with a checkout URL, return it.
            if ($existingPayment->status === 'pending' && $existingPayment->chapa_checkout_url) {
                return response()->json([
                    'message'      => 'Payment already initiated.',
                    'checkout_url' => $existingPayment->chapa_checkout_url,
                    'payment'      => $existingPayment,
                ]);
            }

            if ($existingPayment->status === 'confirmed') {
                return response()->json([
                    'message' => 'Payment has already been confirmed for this order.',
                ], 422);
            }
        }

        // Generate a unique transaction reference for Chapa.
        $txRef = 'TX-' . $order->order_number . '-' . strtoupper(Str::random(6));

        // Build the Chapa initialization payload.
        $chapaPayload = [
            'amount'       => (float) $order->total_amount,
            'currency'     => $order->currency,
            'tx_ref'       => $txRef,
            'callback_url' => config('services.chapa.callback_url'),
            'return_url'   => config('services.chapa.return_url'),
            'first_name'   => $user->first_name,
            'last_name'    => $user->second_name,
            'phone_number' => $user->phone,
            'customization' => [
                'title'       => 'Ethiopian Farmers Market',
                'description' => "Payment for order {$order->order_number}",
            ],
        ];

        try {
            $response = Http::withToken(config('services.chapa.secret_key'))
                ->post('https://api.chapa.co/v1/transaction/initialize', $chapaPayload);

            if (! $response->successful()) {
                return response()->json([
                    'message' => 'Unable to initiate payment with the payment gateway. Please try again.',
                ], 502);
            }

            $chapaData   = $response->json('data');
            $checkoutUrl = $chapaData['checkout_url'] ?? null;

            if (! $checkoutUrl) {
                return response()->json([
                    'message' => 'Payment gateway returned an unexpected response. Please try again.',
                ], 502);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Unable to reach the payment gateway. Please try again later.',
            ], 503);
        }

        // Create the payment record (status starts as "pending").
        $payment = Payment::create([
            'order_id'           => $order->id,
            'chapa_tx_ref'       => $txRef,
            'chapa_checkout_url' => $checkoutUrl,
            'amount'             => $order->total_amount,
            'currency'           => $order->currency,
            'status'             => 'pending',
        ]);

        return response()->json([
            'message'      => 'Payment initiated. Redirect the buyer to the checkout URL.',
            'checkout_url' => $checkoutUrl,
            'payment'      => $payment,
        ], 201);
    }

    /**
     * Show the payment details for a specific order.
     *
     * GET /api/orders/{id}/payment
     */
    public function show(int $id): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (! $this->hasActiveBuyerCapability($user)) {
            return response()->json([
                'message' => 'You must have an active buyer capability to view payment details.',
            ], 403);
        }

        $order = Order::findOrFail($id);

        if ($order->buyer_id !== $user->id) {
            return response()->json([
                'message' => 'You are not authorized to view this payment.',
            ], 403);
        }

        $payment = Payment::where('order_id', $order->id)->first();

        if (! $payment) {
            return response()->json([
                'message' => 'No payment has been initiated for this order.',
            ], 404);
        }

        return response()->json([
            'payment' => $payment,
        ]);
    }

    /**
     * Check whether the given user has an active buyer capability.
     */
    private function hasActiveBuyerCapability(\App\Models\User $user): bool
    {
        return $user->capabilities()
            ->where('capability_type', 'buyer')
            ->where('status', 'active')
            ->exists();
    }
}
