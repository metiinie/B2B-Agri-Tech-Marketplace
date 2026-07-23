<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCartItemRequest;
use App\Http\Requests\UpdateCartItemRequest;
use App\Models\CartItem;
use App\Models\Listing;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    /**
     * List all items in the authenticated buyer's cart.
     *
     * GET /api/cart
     */
    public function index(): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (! $this->hasActiveBuyerCapability($user)) {
            return response()->json([
                'message' => 'You must have an active buyer capability to access the cart.',
            ], 403);
        }

        $cartItems = $user->cartItems()
            ->with(['listing:id,farmer_id,title,unit,price_per_unit,quantity_available,status', 'listing.farmer:id,first_name,second_name'])
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'cart_items' => $cartItems,
        ]);
    }

    /**
     * Add an item to the cart or update quantity if it already exists.
     *
     * POST /api/cart
     * Body: { "listing_id": 1, "quantity": 10.5 }
     */
    public function store(StoreCartItemRequest $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (! $this->hasActiveBuyerCapability($user)) {
            return response()->json([
                'message' => 'You must have an active buyer capability to add items to the cart.',
            ], 403);
        }

        $validated = $request->validated();

        $listing = Listing::findOrFail($validated['listing_id']);

        // Prevent buying from inactive or sold-out listings.
        if ($listing->status !== 'active') {
            return response()->json([
                'message' => 'This listing is not currently active.',
            ], 422);
        }

        // Prevent self-ordering: a buyer cannot buy from their own listing.
        if ($listing->farmer_id === $user->id) {
            return response()->json([
                'message' => 'You cannot add your own listing to the cart.',
            ], 403);
        }

        // Check requested quantity does not exceed available stock.
        if ((float) $validated['quantity'] > (float) $listing->quantity_available) {
            return response()->json([
                'message' => 'Requested quantity exceeds available stock.',
                'available' => $listing->quantity_available,
            ], 422);
        }

        // Upsert: unique constraint on (buyer_id, listing_id).
        $cartItem = CartItem::updateOrCreate(
            [
                'buyer_id'   => $user->id,
                'listing_id' => $listing->id,
            ],
            [
                'quantity'       => $validated['quantity'],
                'price_snapshot' => $listing->price_per_unit,
            ],
        );

        $cartItem->load(['listing:id,farmer_id,title,unit,price_per_unit,quantity_available,status', 'listing.farmer:id,first_name,second_name']);

        $wasRecentlyCreated = $cartItem->wasRecentlyCreated;

        return response()->json([
            'message'   => $wasRecentlyCreated ? 'Item added to cart.' : 'Cart item quantity updated.',
            'cart_item' => $cartItem,
        ], $wasRecentlyCreated ? 201 : 200);
    }

    /**
     * Update the quantity of an existing cart item.
     *
     * PUT /api/cart/{id}
     * Body: { "quantity": 5 }
     */
    public function update(UpdateCartItemRequest $request, int $id): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (! $this->hasActiveBuyerCapability($user)) {
            return response()->json([
                'message' => 'You must have an active buyer capability to update cart items.',
            ], 403);
        }

        $cartItem = CartItem::findOrFail($id);

        if ($cartItem->buyer_id !== $user->id) {
            return response()->json([
                'message' => 'You are not authorized to update this cart item.',
            ], 403);
        }

        $validated = $request->validated();

        $listing = $cartItem->listing;

        // Revalidate listing is still active.
        if ($listing->status !== 'active') {
            return response()->json([
                'message' => 'This listing is no longer active.',
            ], 422);
        }

        // Check requested quantity does not exceed available stock.
        if ((float) $validated['quantity'] > (float) $listing->quantity_available) {
            return response()->json([
                'message' => 'Requested quantity exceeds available stock.',
                'available' => $listing->quantity_available,
            ], 422);
        }

        $cartItem->update([
            'quantity'       => $validated['quantity'],
            'price_snapshot' => $listing->price_per_unit,
        ]);

        $cartItem->load(['listing:id,farmer_id,title,unit,price_per_unit,quantity_available,status', 'listing.farmer:id,first_name,second_name']);

        return response()->json([
            'message'   => 'Cart item updated.',
            'cart_item' => $cartItem,
        ]);
    }

    /**
     * Remove a single item from the cart.
     *
     * DELETE /api/cart/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (! $this->hasActiveBuyerCapability($user)) {
            return response()->json([
                'message' => 'You must have an active buyer capability to remove cart items.',
            ], 403);
        }

        $cartItem = CartItem::findOrFail($id);

        if ($cartItem->buyer_id !== $user->id) {
            return response()->json([
                'message' => 'You are not authorized to remove this cart item.',
            ], 403);
        }

        $cartItem->delete();

        return response()->json([
            'message' => 'Item removed from cart.',
        ]);
    }

    /**
     * Clear all items from the authenticated buyer's cart.
     *
     * DELETE /api/cart
     */
    public function clear(): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (! $this->hasActiveBuyerCapability($user)) {
            return response()->json([
                'message' => 'You must have an active buyer capability to clear the cart.',
            ], 403);
        }

        $user->cartItems()->delete();

        return response()->json([
            'message' => 'Cart cleared.',
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
