<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\Listing;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CartItemController extends Controller
{
    /**
     * Get authenticated buyer's cart.
     */
    public function index(): Response
    {
        $user = auth()->user();

        $items = CartItem::where('buyer_id', $user->id)
            ->with(['listing.farmer', 'listing.category'])
            ->orderBy('created_at', 'desc')
            ->get();

        $summary = [
            'item_count' => $items->count(),
            'total_value' => $items->sum(function ($item) {
                return $item->quantity * $item->price_snapshot;
            }),
            'items' => $items,
        ];

        return response($summary);
    }

    /**
     * Add item to cart or update quantity.
     */
    public function store(Request $request): Response
    {
        $user = auth()->user();

        $validated = $request->validate([
            'listing_id' => 'required|exists:listings,id',
            'quantity' => 'required|numeric|min:0.001',
        ]);

        $listing = Listing::findOrFail($validated['listing_id']);

        // Validate stock availability
        if ($validated['quantity'] > $listing->quantity_available) {
            return response([
                'error' => 'Insufficient quantity available',
                'available' => $listing->quantity_available,
            ], 422);
        }

        // Check if item already in cart
        $cartItem = CartItem::where('buyer_id', $user->id)
            ->where('listing_id', $listing->id)
            ->first();

        if ($cartItem) {
            // Update quantity
            $cartItem->update([
                'quantity' => $validated['quantity'],
                'price_snapshot' => $listing->price_per_unit,
            ]);
        } else {
            // Create new cart item
            $cartItem = CartItem::create([
                'buyer_id' => $user->id,
                'listing_id' => $listing->id,
                'quantity' => $validated['quantity'],
                'price_snapshot' => $listing->price_per_unit,
            ]);
        }

        return response([
            'message' => 'Item added to cart',
            'item' => $cartItem->load('listing.farmer'),
        ], 201);
    }

    /**
     * Display a specific cart item.
     */
    public function show(CartItem $cartItem): Response
    {
        $this->authorize('view', $cartItem);

        $cartItem->load(['listing.farmer', 'listing.category']);
        return response($cartItem);
    }

    /**
     * Update cart item quantity.
     */
    public function update(Request $request, CartItem $cartItem): Response
    {
        $this->authorize('update', $cartItem);

        $validated = $request->validate([
            'quantity' => 'required|numeric|min:0.001',
        ]);

        $listing = $cartItem->listing;

        if ($validated['quantity'] > $listing->quantity_available) {
            return response([
                'error' => 'Insufficient quantity available',
                'available' => $listing->quantity_available,
            ], 422);
        }

        $cartItem->update([
            'quantity' => $validated['quantity'],
            'price_snapshot' => $listing->price_per_unit,
        ]);

        return response([
            'message' => 'Cart item updated',
            'item' => $cartItem,
        ]);
    }

    /**
     * Remove item from cart.
     */
    public function destroy(CartItem $cartItem): Response
    {
        $this->authorize('delete', $cartItem);

        $cartItem->delete();
        return response(['message' => 'Item removed from cart'], 200);
    }

    /**
     * Clear entire cart.
     */
    public function clear(): Response
    {
        $user = auth()->user();

        CartItem::where('buyer_id', $user->id)->delete();
        return response(['message' => 'Cart cleared'], 200);
    }

    /**
     * Get cart items grouped by farmer.
     */
    public function grouped(): Response
    {
        $user = auth()->user();

        $items = CartItem::where('buyer_id', $user->id)
            ->with(['listing.farmer', 'listing.category'])
            ->get();

        $grouped = $items->groupBy('listing.farmer_id');

        return response($grouped);
    }

    /**
     * Get cart subtotal by farmer.
     */
    public function breakdown(): Response
    {
        $user = auth()->user();

        $items = CartItem::where('buyer_id', $user->id)
            ->with(['listing.farmer'])
            ->get();

        $breakdown = $items->groupBy('listing.farmer_id')
            ->map(function ($farmerItems) {
                return [
                    'farmer_id' => $farmerItems->first()->listing->farmer_id,
                    'farmer_name' => $farmerItems->first()->listing->farmer->name,
                    'subtotal' => $farmerItems->sum(function ($item) {
                        return $item->quantity * $item->price_snapshot;
                    }),
                    'item_count' => $farmerItems->count(),
                ];
            });

        return response([
            'breakdown' => $breakdown->values(),
            'total' => $breakdown->sum('subtotal'),
        ]);
    }
}
