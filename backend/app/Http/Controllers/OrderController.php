<?php

namespace App\Http\Controllers;

use App\Http\Requests\ListOrdersRequest;
use App\Models\CartItem;
use App\Models\Listing;
use App\Models\Order;
use App\Models\OrderFulfillment;
use App\Models\OrderItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    /**
     * List the authenticated buyer's orders.
     *
     * GET /api/orders?status=pending_payment&per_page=15
     */
    public function index(ListOrdersRequest $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (! $this->hasActiveBuyerCapability($user)) {
            return response()->json([
                'message' => 'You must have an active buyer capability to view orders.',
            ], 403);
        }

        $validated = $request->validated();

        $orders = $user->orders()
            ->with(['fulfillments:id,order_id,farmer_id,status,subtotal_amount', 'payment:id,order_id,status'])
            ->when(isset($validated['status']), function ($query) use ($validated) {
                $query->where('status', $validated['status']);
            })
            ->orderByDesc('placed_at')
            ->paginate($validated['per_page'] ?? 20);

        return response()->json($orders);
    }

    /**
     * Show a single order with full details.
     *
     * GET /api/orders/{id}
     */
    public function show(int $id): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (! $this->hasActiveBuyerCapability($user)) {
            return response()->json([
                'message' => 'You must have an active buyer capability to view orders.',
            ], 403);
        }

        $order = Order::with([
            'items.listing:id,farmer_id,title,unit',
            'fulfillments.farmer:id,first_name,second_name',
            'fulfillments.items.listing:id,title,unit',
            'payment:id,order_id,status,amount,currency,confirmed_at',
        ])->findOrFail($id);

        if ($order->buyer_id !== $user->id) {
            return response()->json([
                'message' => 'You are not authorized to view this order.',
            ], 403);
        }

        return response()->json([
            'order' => $order,
        ]);
    }

    /**
     * Checkout: convert the buyer's cart into a new order with concurrency-safe
     * stock reservation.
     *
     * POST /api/orders/checkout
     *
     * This method:
     * 1. Validates the buyer has an active buyer capability.
     * 2. Loads all cart items and their listings.
     * 3. Inside a DB transaction with row-level locks on listings:
     *    a. Re-validates stock availability and listing status.
     *    b. Reserves stock (decrement quantity_available, increment quantity_reserved).
     *    c. Creates the Order, per-farmer OrderFulfillments, and OrderItems.
     * 4. Clears the buyer's cart on success.
     */
    public function checkout(): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (! $this->hasActiveBuyerCapability($user)) {
            return response()->json([
                'message' => 'You must have an active buyer capability to place orders.',
            ], 403);
        }

        // Load cart items with their listings eagerly.
        $cartItems = $user->cartItems()->with('listing')->get();

        if ($cartItems->isEmpty()) {
            return response()->json([
                'message' => 'Your cart is empty.',
            ], 422);
        }

        // Group cart items by farmer (listing owner) for per-farmer fulfillments.
        $grouped = $cartItems->groupBy(fn (CartItem $item) => $item->listing->farmer_id);

        // Prevent self-ordering: this should have been blocked at the cart level,
        // but enforce it here as a safety net.
        if ($grouped->has($user->id)) {
            return response()->json([
                'message' => 'You cannot order from your own listings. Please remove them from your cart.',
            ], 403);
        }

        try {
            $order = DB::transaction(function () use ($user, $cartItems, $grouped) {
                // Collect all listing IDs for a single locked query.
                $listingIds = $cartItems->pluck('listing_id')->unique()->toArray();

                // Lock the rows to prevent concurrent checkouts from over-selling.
                $listings = Listing::whereIn('id', $listingIds)
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                // Re-validate every cart item against the locked listing state.
                foreach ($cartItems as $cartItem) {
                    $listing = $listings->get($cartItem->listing_id);

                    if (! $listing || $listing->status !== 'active') {
                        throw new \RuntimeException(
                            "Listing \"{$cartItem->listing->title}\" is no longer active."
                        );
                    }

                    if ((float) $cartItem->quantity > (float) $listing->quantity_available) {
                        throw new \RuntimeException(
                            "Insufficient stock for \"{$listing->title}\". Available: {$listing->quantity_available}, requested: {$cartItem->quantity}."
                        );
                    }
                }

                // Calculate total order amount.
                $totalAmount = $cartItems->sum(function (CartItem $item) use ($listings) {
                    $listing = $listings->get($item->listing_id);

                    return (float) $item->quantity * (float) $listing->price_per_unit;
                });

                // Create the order.
                $order = Order::create([
                    'order_number' => 'ORD-' . date('Y') . '-' . strtoupper(Str::random(8)),
                    'buyer_id'     => $user->id,
                    'status'       => 'pending_payment',
                    'total_amount' => round($totalAmount, 2),
                    'currency'     => 'ETB',
                    'placed_at'    => now(),
                ]);

                // Create per-farmer fulfillments and order items.
                foreach ($grouped as $farmerId => $farmerCartItems) {
                    $fulfillmentSubtotal = 0;

                    $fulfillment = OrderFulfillment::create([
                        'order_id'        => $order->id,
                        'farmer_id'       => $farmerId,
                        'status'          => 'pending',
                        'subtotal_amount' => 0, // Computed below.
                    ]);

                    foreach ($farmerCartItems as $cartItem) {
                        $listing  = $listings->get($cartItem->listing_id);
                        $unitPrice = (float) $listing->price_per_unit;
                        $quantity  = (float) $cartItem->quantity;
                        $subtotal  = round($unitPrice * $quantity, 2);

                        OrderItem::create([
                            'order_id'             => $order->id,
                            'order_fulfillment_id' => $fulfillment->id,
                            'listing_id'           => $cartItem->listing_id,
                            'quantity'             => $quantity,
                            'unit_price'           => $unitPrice,
                            'subtotal'             => $subtotal,
                        ]);

                        // Reserve stock on the listing.
                        $listing->decrement('quantity_available', $quantity);
                        $listing->increment('quantity_reserved', $quantity);

                        $fulfillmentSubtotal += $subtotal;
                    }

                    // Update the fulfillment subtotal now that all items are created.
                    $fulfillment->update([
                        'subtotal_amount' => round($fulfillmentSubtotal, 2),
                    ]);
                }

                return $order;
            });
        } catch (\RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }

        // Clear the buyer's cart after successful order creation.
        $user->cartItems()->delete();

        // Load relationships for the response.
        $order->load([
            'items.listing:id,title,unit',
            'fulfillments.farmer:id,first_name,second_name',
            'payment:id,order_id,status',
        ]);

        return response()->json([
            'message' => 'Order placed successfully.',
            'order'   => $order,
        ], 201);
    }

    /**
     * Cancel a pending-payment order and release reserved stock.
     *
     * POST /api/orders/{id}/cancel
     *
     * Only orders in "pending_payment" status may be cancelled by the buyer.
     */
    public function cancel(int $id): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (! $this->hasActiveBuyerCapability($user)) {
            return response()->json([
                'message' => 'You must have an active buyer capability to cancel orders.',
            ], 403);
        }

        $order = Order::findOrFail($id);

        if ($order->buyer_id !== $user->id) {
            return response()->json([
                'message' => 'You are not authorized to cancel this order.',
            ], 403);
        }

        if ($order->status !== 'pending_payment') {
            return response()->json([
                'message' => 'Only orders with pending payment can be cancelled.',
            ], 422);
        }

        DB::transaction(function () use ($order) {
            // Load order items to release reserved stock.
            $orderItems = $order->items()->with('listing')->get();

            foreach ($orderItems as $orderItem) {
                $listing = Listing::where('id', $orderItem->listing_id)
                    ->lockForUpdate()
                    ->first();

                if ($listing) {
                    $listing->increment('quantity_available', (float) $orderItem->quantity);
                    $listing->decrement('quantity_reserved', (float) $orderItem->quantity);
                }
            }

            // Cancel all fulfillments.
            $order->fulfillments()->update([
                'status' => 'cancelled',
            ]);

            // Cancel the order itself.
            $order->update([
                'status' => 'cancelled',
            ]);
        });

        return response()->json([
            'message' => 'Order cancelled and reserved stock released.',
            'order'   => $order->fresh(['fulfillments:id,order_id,status', 'payment:id,order_id,status']),
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
