<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Order;
use App\Models\PaymentException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PaymentController extends Controller
{
    /**
     * Get payment for an order.
     */
    public function show(Order $order): Response
    {
        $this->authorize('viewPayment', $order);

        $payment = $order->payment;

        if (!$payment) {
            return response(['error' => 'No payment found for this order'], 404);
        }

        $payment->load('webhookEvents', 'exceptions');
        return response($payment);
    }

    /**
     * Initiate payment (create pending payment record).
     * In practice, this is called by a CheckoutService after order creation.
     */
    public function initiate(Order $order): Response
    {
        $this->authorize('initiatePayment', $order);

        // Check if payment already initiated
        if ($order->payment) {
            return response(['error' => 'Payment already initiated for this order'], 422);
        }

        // Generate unique transaction reference
        $txRef = 'ORD-' . $order->id . '-' . time();

        $payment = Payment::create([
            'order_id' => $order->id,
            'chapa_tx_ref' => $txRef,
            'amount' => $order->total_amount,
            'currency' => $order->currency,
            'status' => 'pending',
        ]);

        return response([
            'message' => 'Payment initiated',
            'payment' => $payment,
            'tx_ref' => $txRef,
        ], 201);
    }

    /**
     * Get checkout URL for Chapa payment.
     * This should be called after payment is initiated.
     */
    public function checkoutUrl(Order $order): Response
    {
        $this->authorize('viewPayment', $order);

        $payment = $order->payment;

        if (!$payment) {
            return response(['error' => 'Payment not initiated'], 404);
        }

        if ($payment->status !== 'pending') {
            return response(['error' => 'Payment is already ' . $payment->status], 422);
        }

        // Return checkout URL (built by ChapaService or similar)
        return response([
            'checkout_url' => $payment->chapa_checkout_url,
            'tx_ref' => $payment->chapa_tx_ref,
        ]);
    }

    /**
     * Get payment status for an order.
     */
    public function status(Order $order): Response
    {
        $this->authorize('viewPayment', $order);

        $payment = $order->payment;

        if (!$payment) {
            return response(['error' => 'No payment found'], 404);
        }

        return response([
            'order_id' => $order->id,
            'tx_ref' => $payment->chapa_tx_ref,
            'amount' => $payment->amount,
            'currency' => $payment->currency,
            'status' => $payment->status,
            'confirmed_at' => $payment->confirmed_at,
        ]);
    }

    /**
     * Get payment webhook events (for debugging/audit).
     */
    public function webhookEvents(Order $order): Response
    {
        $this->authorize('viewPayment', $order);

        $payment = $order->payment;

        if (!$payment) {
            return response(['error' => 'No payment found'], 404);
        }

        $events = $payment->webhookEvents()->orderBy('created_at', 'desc')->get();
        return response($events);
    }

    /**
     * Raise a payment exception/dispute (buyer or admin).
     */
    public function raiseException(Request $request, Order $order): Response
    {
        $this->authorize('raisePaymentException', $order);

        $validated = $request->validate([
            'type' => 'required|in:dispute,mismatch,failed_payment_review,refund_request,other',
            'description' => 'required|string|max:1000',
        ]);

        $payment = $order->payment;

        if (!$payment) {
            return response(['error' => 'No payment found for this order'], 404);
        }

        $exception = PaymentException::create([
            'payment_id' => $payment->id,
            'order_id' => $order->id,
            'raised_by' => auth()->id(),
            'type' => $validated['type'],
            'description' => $validated['description'],
            'status' => 'open',
        ]);

        return response([
            'message' => 'Payment exception raised',
            'exception' => $exception,
        ], 201);
    }

    /**
     * Get payment exceptions for an order (admin only).
     */
    public function exceptions(Order $order): Response
    {
        $this->authorize('viewPaymentExceptions', $order);

        $exceptions = PaymentException::where('order_id', $order->id)
            ->with(['payment', 'raisedBy', 'resolvedBy'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response($exceptions);
    }

    /**
     * Get all payment exceptions (admin dashboard).
     */
    public function allExceptions(Request $request): Response
    {
        $query = PaymentException::with(['payment', 'order.buyer', 'raisedBy', 'resolvedBy']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $exceptions = $query->orderBy('created_at', 'desc')->paginate(20);
        return response($exceptions);
    }

    /**
     * Update exception status (admin only).
     */
    public function updateException(Request $request, PaymentException $exception): Response
    {
        $this->authorize('updatePaymentException', $exception);

        $validated = $request->validate([
            'status' => 'sometimes|in:open,investigating,resolved,rejected',
            'resolution_notes' => 'sometimes|string|max:500',
        ]);

        $exception->update($validated);

        if (isset($validated['status']) && $validated['status'] === 'resolved') {
            $exception->update([
                'resolved_by' => auth()->id(),
                'resolved_at' => now(),
            ]);
        }

        return response([
            'message' => 'Exception updated',
            'exception' => $exception,
        ]);
    }
}
