<?php

namespace App\Http\Controllers;

use App\Http\Requests\ListPaymentExceptionsRequest;
use App\Http\Requests\ResolvePaymentExceptionRequest;
use App\Http\Requests\StorePaymentExceptionRequest;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class PaymentExceptionController extends Controller
{
    /**
     * Raise a payment exception (dispute, refund request, etc.).
     *
     * POST /api/payment-exceptions
     * Body: {
     *   "payment_id": 1,
     *   "type": "dispute",
     *   "description": "I was charged but the order was never fulfilled."
     * }
     *
     * Any authenticated user who is part of the order (buyer or farmer)
     * can raise an exception against a payment.
     */
    public function store(StorePaymentExceptionRequest $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $validated = $request->validated();

        $payment = Payment::with('order')->findOrFail($validated['payment_id']);
        $order   = $payment->order;

        if (! $order) {
            return response()->json([
                'message' => 'The payment is not associated with a valid order.',
            ], 422);
        }

        // Only the buyer or a farmer assigned to a fulfillment on this order may raise an exception.
        if (! $this->isOrderParticipant($user, $order)) {
            return response()->json([
                'message' => 'You are not authorized to raise an exception for this payment.',
            ], 403);
        }

        // Prevent duplicate open exceptions of the same type for the same payment.
        $existingOpen = PaymentException::where('payment_id', $payment->id)
            ->where('type', $validated['type'])
            ->whereIn('status', ['open', 'investigating'])
            ->exists();

        if ($existingOpen) {
            return response()->json([
                'message' => 'An open exception of this type already exists for this payment.',
            ], 409);
        }

        $exception = PaymentException::create([
            'payment_id'  => $payment->id,
            'order_id'    => $order->id,
            'raised_by'   => $user->id,
            'type'        => $validated['type'],
            'description' => $validated['description'],
            'status'      => 'open',
        ]);

        return response()->json([
            'message'           => 'Payment exception raised successfully.',
            'payment_exception' => $exception->load([
                'payment:id,chapa_tx_ref,amount,currency,status',
                'order:id,order_number,status',
                'raisedBy:id,first_name,second_name',
            ]),
        ], 201);
    }

    /**
     * List the authenticated user's own payment exceptions.
     *
     * GET /api/payment-exceptions/my?status=open&per_page=20
     */
    public function my(ListPaymentExceptionsRequest $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $validated = $request->validated();

        $exceptions = PaymentException::where('raised_by', $user->id)
            ->with([
                'payment:id,chapa_tx_ref,amount,currency,status',
                'order:id,order_number,status',
            ])
            ->when(isset($validated['status']), function ($query) use ($validated) {
                $query->where('status', $validated['status']);
            })
            ->orderByDesc('created_at')
            ->paginate($validated['per_page'] ?? 20);

        return response()->json($exceptions);
    }

    /**
     * Show a single payment exception (own or admin).
     *
     * GET /api/payment-exceptions/{id}
     */
    public function show(int $id): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $exception = PaymentException::with([
            'payment:id,chapa_tx_ref,amount,currency,status',
            'order:id,order_number,status,total_amount,currency',
            'raisedBy:id,first_name,second_name,phone',
            'resolvedBy:id,first_name,second_name',
        ])->findOrFail($id);

        // Non-admin users can only view their own exceptions.
        if (! $user->is_admin && $exception->raised_by !== $user->id) {
            return response()->json([
                'message' => 'You are not authorized to view this exception.',
            ], 403);
        }

        return response()->json([
            'payment_exception' => $exception,
        ]);
    }

    /**
     * List all payment exceptions (admin only).
     *
     * GET /api/admin/payment-exceptions?status=open&type=dispute&per_page=20
     */
    public function index(ListPaymentExceptionsRequest $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (! $user->is_admin) {
            return response()->json([
                'message' => 'Unauthorized. Admin access required.',
            ], 403);
        }

        $validated = $request->validated();

        $query = PaymentException::with([
            'payment:id,chapa_tx_ref,amount,currency,status',
            'order:id,order_number,status',
            'raisedBy:id,first_name,second_name,phone',
        ]);

        if (isset($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (isset($validated['type'])) {
            $query->where('type', $validated['type']);
        }

        $exceptions = $query->orderByDesc('created_at')
            ->paginate($validated['per_page'] ?? 20);

        return response()->json($exceptions);
    }

    /**
     * Move an open exception to "investigating" status (admin only).
     *
     * POST /api/admin/payment-exceptions/{id}/investigate
     */
    public function investigate(int $id): JsonResponse
    {
        /** @var \App\Models\User $user */
        $admin = Auth::user();

        if (! $admin->is_admin) {
            return response()->json([
                'message' => 'Unauthorized. Admin access required.',
            ], 403);
        }

        $exception = PaymentException::findOrFail($id);

        if ($exception->status !== 'open') {
            return response()->json([
                'message' => 'Only open exceptions can be moved to investigating.',
            ], 422);
        }

        $exception->update([
            'status' => 'investigating',
        ]);

        return response()->json([
            'message'           => 'Exception is now under investigation.',
            'payment_exception' => $exception->fresh()->load([
                'payment:id,chapa_tx_ref,amount,currency,status',
                'order:id,order_number,status',
                'raisedBy:id,first_name,second_name',
            ]),
        ]);
    }

    /**
     * Resolve a payment exception (admin only).
     *
     * POST /api/admin/payment-exceptions/{id}/resolve
     * Body: { "resolution_notes": "Refund processed via Chapa transfer #XYZ." }
     *
     * NOTE: Resolving an exception NEVER directly mutates payments.status.
     * Any refund/reversal must go through the Chapa API, and the resulting
     * webhook event will handle the payment status transition.
     */
    public function resolve(ResolvePaymentExceptionRequest $request, int $id): JsonResponse
    {
        /** @var \App\Models\User $user */
        $admin = Auth::user();

        if (! $admin->is_admin) {
            return response()->json([
                'message' => 'Unauthorized. Admin access required.',
            ], 403);
        }

        $validated = $request->validated();

        $exception = PaymentException::findOrFail($id);

        if (! in_array($exception->status, ['open', 'investigating'], true)) {
            return response()->json([
                'message' => 'Only open or investigating exceptions can be resolved.',
            ], 422);
        }

        $exception->update([
            'status'           => 'resolved',
            'resolution_notes' => $validated['resolution_notes'],
            'resolved_by'      => $admin->id,
            'resolved_at'      => now(),
        ]);

        return response()->json([
            'message'           => 'Exception resolved.',
            'payment_exception' => $exception->fresh()->load([
                'payment:id,chapa_tx_ref,amount,currency,status',
                'order:id,order_number,status',
                'raisedBy:id,first_name,second_name',
                'resolvedBy:id,first_name,second_name',
            ]),
        ]);
    }

    /**
     * Reject a payment exception (admin only).
     *
     * POST /api/admin/payment-exceptions/{id}/reject
     * Body: { "resolution_notes": "No evidence of mismatch found." }
     */
    public function reject(ResolvePaymentExceptionRequest $request, int $id): JsonResponse
    {
        /** @var \App\Models\User $user */
        $admin = Auth::user();

        if (! $admin->is_admin) {
            return response()->json([
                'message' => 'Unauthorized. Admin access required.',
            ], 403);
        }

        $validated = $request->validated();

        $exception = PaymentException::findOrFail($id);

        if (! in_array($exception->status, ['open', 'investigating'], true)) {
            return response()->json([
                'message' => 'Only open or investigating exceptions can be rejected.',
            ], 422);
        }

        $exception->update([
            'status'           => 'rejected',
            'resolution_notes' => $validated['resolution_notes'],
            'resolved_by'      => $admin->id,
            'resolved_at'      => now(),
        ]);

        return response()->json([
            'message'           => 'Exception rejected.',
            'payment_exception' => $exception->fresh()->load([
                'payment:id,chapa_tx_ref,amount,currency,status',
                'order:id,order_number,status',
                'raisedBy:id,first_name,second_name',
                'resolvedBy:id,first_name,second_name',
            ]),
        ]);
    }

    /**
     * Check whether the given user is a participant in the order
     * (either the buyer or a farmer assigned to a fulfillment).
     */
    private function isOrderParticipant(\App\Models\User $user, Order $order): bool
    {
        // Is the buyer?
        if ($order->buyer_id === $user->id) {
            return true;
        }

        // Is a farmer on one of the fulfillments?
        return $order->fulfillments()
            ->where('farmer_id', $user->id)
            ->exists();
    }
}
