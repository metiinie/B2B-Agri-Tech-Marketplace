<?php

namespace App\Http\Controllers;

use App\Models\Listing;
use App\Models\ListingPriceHistory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ListingController extends Controller
{
    /**
     * Display active listings with optional category/search filter.
     */
    public function index(Request $request): Response
    {
        $query = Listing::with(['farmer', 'category', 'priceHistory'])
            ->where('status', 'active');

        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('search')) {
            $query->where('title', 'like', '%' . $request->search . '%')
                ->orWhere('description', 'like', '%' . $request->search . '%');
        }

        $listings = $query->paginate(20);
        return response($listings);
    }

    /**
     * Create a new listing (farmer only).
     */
    public function store(Request $request): Response
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'unit' => 'required|in:kg,quintal,ton,piece,liter,dozen',
            'price_per_unit' => 'required|numeric|min:0.01',
            'quantity_available' => 'required|numeric|min:0',
        ]);

        $user = auth()->user();
        $validated['farmer_id'] = $user->id;
        $validated['status'] = 'active';

        $listing = Listing::create($validated);

        // Record initial price in history
        ListingPriceHistory::create([
            'listing_id' => $listing->id,
            'price_per_unit' => $validated['price_per_unit'],
            'changed_by' => $user->id,
        ]);

        return response($listing->load('farmer', 'category'), 201);
    }

    /**
     * Display a specific listing.
     */
    public function show(Listing $listing): Response
    {
        $listing->load(['farmer', 'category', 'priceHistory']);
        return response($listing);
    }

    /**
     * Update a listing (farmer only).
     */
    public function update(Request $request, Listing $listing): Response
    {
        $this->authorize('update', $listing);

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string|nullable',
            'price_per_unit' => 'sometimes|numeric|min:0.01',
            'quantity_available' => 'sometimes|numeric|min:0',
            'status' => 'sometimes|in:active,inactive,sold_out',
        ]);

        // Track price history if price changed
        if (isset($validated['price_per_unit']) && $validated['price_per_unit'] != $listing->price_per_unit) {
            ListingPriceHistory::create([
                'listing_id' => $listing->id,
                'price_per_unit' => $validated['price_per_unit'],
                'changed_by' => auth()->id(),
            ]);
        }

        $listing->update($validated);
        return response($listing);
    }

    /**
     * Delete/archive a listing (farmer only).
     */
    public function destroy(Listing $listing): Response
    {
        $this->authorize('delete', $listing);
        $listing->delete();
        return response(['message' => 'Listing archived'], 200);
    }

    /**
     * Get price history for a listing.
     */
    public function priceHistory(Listing $listing): Response
    {
        $history = $listing->priceHistory()->orderBy('effective_at', 'desc')->get();
        return response($history);
    }

    /**
     * Get listings by farmer.
     */
    public function farmerListings(int $farmerId): Response
    {
        $listings = Listing::where('farmer_id', $farmerId)
            ->with(['category', 'priceHistory'])
            ->paginate(20);
        return response($listings);
    }

    /**
     * Get available listings (not sold out).
     */
    public function available(): Response
    {
        $listings = Listing::available()
            ->with(['farmer', 'category'])
            ->paginate(20);
        return response($listings);
    }
}
