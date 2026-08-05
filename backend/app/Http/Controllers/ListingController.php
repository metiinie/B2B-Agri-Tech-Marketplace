<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreListingRequest;
use App\Http\Requests\UpdateListingRequest;
use App\Models\Listing;
use App\Models\ListingPriceHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ListingController extends Controller
{
    /**
     * Browse all active listings (public — no auth required).
     *
     * GET /api/listings?category_id=1&search=teff&sort=price_asc
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'category_id' => ['sometimes', 'integer', 'exists:categories,id'],
            'search'      => ['sometimes', 'string', 'max:100'],
            'sort'        => ['sometimes', 'string', 'in:price_asc,price_desc,newest,oldest'],
            'per_page'    => ['sometimes', 'integer', 'min:1', 'max:50'],
        ]);

        $query = Listing::with(['farmer:id,first_name,second_name', 'category:id,name,slug'])
            ->active();

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $sort = $request->input('sort', 'newest');
        match ($sort) {
            'price_asc'  => $query->orderBy('price_per_unit', 'asc'),
            'price_desc' => $query->orderBy('price_per_unit', 'desc'),
            'oldest'     => $query->orderBy('created_at', 'asc'),
            default      => $query->orderBy('created_at', 'desc'),
        };

        $listings = $query->paginate($request->input('per_page', 20));

        return response()->json($listings);
    }

    /**
     * Show a single listing (public — no auth required).
     *
     * GET /api/listings/{id}
     */
    public function show(int $id): JsonResponse
    {
        $listing = Listing::with([
            'farmer:id,first_name,second_name',
            'category:id,name,slug',
            'priceHistory' => fn ($q) => $q->orderByDesc('effective_at')->limit(10),
        ])->findOrFail($id);

        return response()->json([
            'listing' => $listing,
        ]);
    }

    /**
     * Create a new listing (farmer only).
     *
     * POST /api/listings
     * Body: { "category_id": 1, "title": "...", "description": "...", "unit": "kg", "price_per_unit": 50.00, "quantity_available": 100 }
     */
    public function store(StoreListingRequest $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (! $this->hasActiveFarmerCapability($user)) {
            return response()->json([
                'message' => 'You must have an active farmer capability to create listings.',
            ], 403);
        }

        $validated = $request->validated();

        $listing = DB::transaction(function () use ($validated, $user) {
            $listing = Listing::create([
                'farmer_id'          => $user->id,
                'category_id'        => $validated['category_id'] ?? null,
                'title'              => $validated['title'],
                'description'        => $validated['description'] ?? null,
                'unit'               => $validated['unit'],
                'price_per_unit'     => $validated['price_per_unit'],
                'quantity_available' => $validated['quantity_available'],
            ]);

            // Record initial price in history.
            ListingPriceHistory::create([
                'listing_id'    => $listing->id,
                'price_per_unit' => $listing->price_per_unit,
                'changed_by'    => $user->id,
                'effective_at'  => now(),
            ]);

            return $listing;
        });

        return response()->json([
            'message' => 'Listing created successfully.',
            'listing' => $listing->load(['farmer:id,first_name,second_name', 'category:id,name,slug']),
        ], 201);
    }

    /**
     * Update a listing (owner farmer only).
     *
     * PUT /api/listings/{id}
     * Body: { "title": "...", "price_per_unit": 60.00, "quantity_available": 80, ... }
     */
    public function update(UpdateListingRequest $request, int $id): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (! $this->hasActiveFarmerCapability($user)) {
            return response()->json([
                'message' => 'You must have an active farmer capability to update listings.',
            ], 403);
        }

        $listing = Listing::findOrFail($id);

        if ($listing->farmer_id !== $user->id) {
            return response()->json([
                'message' => 'You are not authorized to update this listing.',
            ], 403);
        }

        $validated = $request->validated();

        DB::transaction(function () use ($listing, $validated, $user) {
            // Track price change in history when price_per_unit is updated.
            if (isset($validated['price_per_unit']) && (float) $validated['price_per_unit'] !== (float) $listing->price_per_unit) {
                ListingPriceHistory::create([
                    'listing_id'    => $listing->id,
                    'price_per_unit' => $validated['price_per_unit'],
                    'changed_by'    => $user->id,
                    'effective_at'  => now(),
                ]);
            }

            $listing->update($validated);
        });

        return response()->json([
            'message' => 'Listing updated successfully.',
            'listing' => $listing->fresh()->load(['farmer:id,first_name,second_name', 'category:id,name,slug']),
        ]);
    }

    /**
     * Soft-delete a listing (owner farmer only).
     *
     * DELETE /api/listings/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (! $this->hasActiveFarmerCapability($user)) {
            return response()->json([
                'message' => 'You must have an active farmer capability to remove listings.',
            ], 403);
        }

        $listing = Listing::findOrFail($id);

        if ($listing->farmer_id !== $user->id) {
            return response()->json([
                'message' => 'You are not authorized to remove this listing.',
            ], 403);
        }

        // Prevent deletion when stock is currently reserved by pending orders.
        if ($listing->quantity_reserved > 0) {
            return response()->json([
                'message' => 'Cannot remove this listing while stock is reserved for pending orders.',
            ], 422);
        }

        $listing->delete();

        return response()->json([
            'message' => 'Listing removed successfully.',
        ]);
    }

    /**
     * List the authenticated farmer's own listings.
     *
     * GET /api/listings/my?status=active
     */
    public function my(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (! $this->hasActiveFarmerCapability($user)) {
            return response()->json([
                'message' => 'You must have an active farmer capability to view your listings.',
            ], 403);
        }

        $query = $user->listings()->with('category:id,name,slug');

        if ($request->filled('status')) {
            $request->validate(['status' => ['in:active,inactive,sold_out']]);
            $query->where('status', $request->input('status'));
        }

        $listings = $query->orderByDesc('created_at')
            ->paginate($request->input('per_page', 20));

        return response()->json($listings);
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
