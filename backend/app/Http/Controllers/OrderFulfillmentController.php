<?php

namespace App\Http\Controllers;

use App\Http\Requests\ListFulfillmentsRequest;
use App\Http\Requests\RejectFulfillmentRequest;
use App\Models\Listing;
use App\Models\Order;
use App\Models\OrderFulfillment;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrderFulfillmentController extends Controller
{
    /**
     * List all fulfillments assigned to the authenticated farmer.
     *
     * GET /api/fulfillments?status=pending&per_page=15
     */
    public function index(ListFulfillmentsRequest $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (! $this->hasActiveFarmerCapability($user)) {
            return response()->json([
                'message' => 'You must have an active farmer capability to view fulfillments.',
            ], 403);
        }

        $validated = $request->validated();

        $fulfillments = $user->orderFulfillments()
            ->with([
                'order:id,order_number,buyer_id,status,total_amount,currency,placed_at',
                'order.buyer:id,first_name,second_name',
                'items.listing:id,title,unit',
            ])
            ->when(isset($validated['status']), function ($query) use ($validated) {
                $query->where('status', $validated['status']);
            })
            ->orderByDesc('created_at')
            ->paginate($validated['per_page'] ?? 20);

        return response()->json($fulfillments);
    }

    /**
     * Show a single fulfillment with full details.
     *
     * GET /api/fulfillments/{id}
     */
    public function show(int $id): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (! $this->hasActiveFarmerCapability($user)) {
            return response()->json([
                'message' => 'You must have an active farmer capability to view fulfillments.',
            ], 403);
        }

        $fulfillment = OrderFulfillment::with([
            'order:id,order_number,buyer_id,status,total_amount,currency,placed_at',
            'order.buyer:id,first_name,second_name',
            'items.listing:id,title,unit,price_per_unit',
        ])->findOrFail($id);

        if ($fulfillment->farmer_id !== $user->id) {
            return response()->json([
                'message' => 'You are not authorized to view this fulfillment.',
            ], 403);
        }

        return response()->json([
            'fulfillment' => $fulfillment,
        ]);
    }

    /**
     * Accept a pending fulfillment.
     *
     * POST /api/fulfillments/{id}/accept
     *
     * The farmer confirms they can fulfill their portion of the order.
     */
    public function accept(int $id): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (! $this->hasActiveFarmerCapability($user)) {
            return response()->json([
                'message' => 'You must have an active farmer capability to accept fulfillments.',
            ], 403);
        }

        $fulfillment = OrderFulfillment::findOrFail($id);

        if ($fulfillment->farmer_id !== $user->id) {
            return response()->json([
                'message' => 'You are not authorized to accept this fulfillment.',
            ], 403);
        }

        if ($fulfillment->status !== 'pending') {
            return response()->json([
                'message' => 'Only pending fulfillments can be accepted.',
            ], 422);
        }

        $fulfillment->update([
            'status'      => 'accepted',
            'accepted_at' => now(),
        ]);

        $this->syncOrderStatus($fulfillment->order_id);

        return response()->json([
            'message'     => 'Fulfillment accepted.',
            'fulfillment' => $fulfillment->fresh([
                'order:id,order_number,status',
                'items.listing:id,title,unit',
            ]),
        ]);
    }

    /**
     * Reject a pending fulfillment and release the reserved stock.
     *
     * POST /api/fulfillments/{id}/reject
     * Body: { "farmer_notes": "Out of stock due to weather damage." }
     *
     * When a farmer rejects a fulfillment the reserved quantities for their
     * items are returned to available stock.
     */
    public function reject(RejectFulfillmentRequest $request, int $id): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (! $this->hasActiveFarmerCapability($user)) {
            return response()->json([
                'message' => 'You must have an active farmer capability to reject fulfillments.',
            ], 403);
        }

        $validated = $request->validated();

        $fulfillment = OrderFulfillment::findOrFail($id);

        if ($fulfillment->farmer_id !== $user->id) {
            return response()->json([
                'message' => 'You are not authorized to reject this fulfillment.',
            ], 403);
        }

        if ($fulfillment->status !== 'pending') {
            return response()->json([
                'message' => 'Only pending fulfillments can be rejected.',
            ], 422);
        }

        DB::transaction(function () use ($fulfillment, $validated) {
            // Release reserved stock for every item in this fulfillment.
            $items = $fulfillment->items()->get();

            foreach ($items as $item) {
                $listing = Listing::where('id', $item->listing_id)
                    ->lockForUpdate()
                    ->first();

                if ($listing) {
                    $listing->increment('quantity_available', (float) $item->quantity);
                    $listing->decrement('quantity_reserved', (float) $item->quantity);
                }
            }

            $fulfillment->update([
                'status'       => 'rejected',
                'farmer_notes' => $validated['farmer_notes'] ?? null,
                'rejected_at'  => now(),
            ]);
        });

        $this->syncOrderStatus($fulfillment->order_id);

        return response()->json([
            'message'     => 'Fulfillment rejected and reserved stock released.',
            'fulfillment' => $fulfillment->fresh([
                'order:id,order_number,status',
                'items.listing:id,title,unit',
            ]),
        ]);
    }

    /**
     * Mark an accepted fulfillment as completed (handoff done).
     *
     * POST /api/fulfillments/{id}/complete
     *
     * The farmer confirms the produce has been handed off to the buyer.
     * Reserved stock is consumed (decremented from quantity_reserved).
     */
    public function complete(int $id): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (! $this->hasActiveFarmerCapability($user)) {
            return response()->json([
                'message' => 'You must have an active farmer capability to complete fulfillments.',
            ], 403);
        }

        $fulfillment = OrderFulfillment::findOrFail($id);

        if ($fulfillment->farmer_id !== $user->id) {
            return response()->json([
                'message' => 'You are not authorized to complete this fulfillment.',
            ], 403);
        }

        if ($fulfillment->status !== 'accepted') {
            return response()->json([
                'message' => 'Only accepted fulfillments can be marked as completed.',
            ], 422);
        }

        DB::transaction(function () use ($fulfillment) {
            // Consume the reserved stock — the produce has been handed off.
            $items = $fulfillment->items()->get();

            foreach ($items as $item) {
                $listing = Listing::where('id', $item->listing_id)
                    ->lockForUpdate()
                    ->first();

                if ($listing) {
                    $listing->decrement('quantity_reserved', (float) $item->quantity);
                }
            }

            $fulfillment->update([
                'status'       => 'completed',
                'completed_at' => now(),
            ]);
        });

        $this->syncOrderStatus($fulfillment->order_id);

        return response()->json([
            'message'     => 'Fulfillment completed.',
            'fulfillment' => $fulfillment->fresh([
                'order:id,order_number,status',
                'items.listing:id,title,unit',
            ]),
        ]);
    }

    /**
     * Synchronise the parent order's aggregate status based on the current
     * state of all its fulfillments.
     *
     * Status logic:
     * - All completed → "completed"
     * - Mix of completed and rejected (none pending/accepted) → "partially_fulfilled"
     * - All rejected → "cancelled"
     * - At least one accepted and none pending → "processing"
     * - Otherwise remains unchanged (still has pending fulfillments).
     */
    private function syncOrderStatus(int $orderId): void
    {
        $order = Order::findOrFail($orderId);

        // Only sync orders that are past payment (not pending_payment or already cancelled).
        if (in_array($order->status, ['pending_payment', 'cancelled'], true)) {
            return;
        }

        $statuses = $order->fulfillments()->pluck('status');

        if ($statuses->isEmpty()) {
            return;
        }

        $allCompleted = $statuses->every(fn ($s) => $s === 'completed');
        $allRejected  = $statuses->every(fn ($s) => $s === 'rejected');
        $hasPending   = $statuses->contains('pending');

        if ($allCompleted) {
            $order->update(['status' => 'completed']);
        } elseif ($allRejected) {
            $order->update(['status' => 'cancelled']);
        } elseif (! $hasPending) {
            // No pending left — either a mix of completed/rejected or all accepted.
            $hasCompleted = $statuses->contains('completed');
            $hasRejected  = $statuses->contains('rejected');

            if ($hasCompleted && $hasRejected) {
                $order->update(['status' => 'partially_fulfilled']);
            } else {
                $order->update(['status' => 'processing']);
            }
        }
        // If there are still pending fulfillments, order status stays as-is.
    }

    /**
     * Check whether the given user has an active farmer capability.
     */
    private function hasActiveFarmerCapability(\App\Models\User $user): bool
    {
        return $user->capabilities()
            ->where('capability_type', 'farmer')
            ->where('status', 'active')
            ->exists();
    }
}
