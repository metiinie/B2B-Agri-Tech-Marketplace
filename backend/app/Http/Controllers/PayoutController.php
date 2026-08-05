<?php

namespace App\Http\Controllers;

use App\Models\OrderFulfillment;
use App\Models\Payout;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PayoutController extends Controller
{
    /**
     * List the authenticated farmer's own payouts.
     *
     * GET /api/payouts?status=pending&per_page=20
     *
     * Doc: farmer permission "view payout history."
     * Each payout corresponds to a completed fulfillment for this farmer.
     */
    public function index(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (! $this->hasActiveFarmerCapability($user)) {
            return response()->json([
                'message' => 'You must have an active farmer capability to view payouts.',
            ], 403);
        }

        $request->validate([
            'status'   => ['sometimes', 'string', 'in:pending,processed,failed'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $payouts = Payout::where('farmer_id', $user->id)
            ->with([
                'fulfillment:id,order_id,status,subtotal_amount,completed_at',
                'fulfillment.order:id,order_number,status,currency',
            ])
            ->when($request->has('status'), function ($query) use ($request) {
                $query->where('status', $request->input('status'));
            })
            ->orderByDesc('created_at')
            ->paginate($request->input('per_page', 20));

        return response()->json($payouts);
    }

    /**
     * Show a single payout belonging to the authenticated farmer.
     *
     * GET /api/payouts/{id}
     */
    public function show(int $id): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (! $this->hasActiveFarmerCapability($user)) {
            return response()->json([
                'message' => 'You must have an active farmer capability to view payouts.',
            ], 403);
        }

        $payout = Payout::with([
            'fulfillment:id,order_id,status,subtotal_amount,completed_at',
            'fulfillment.order:id,order_number,status,total_amount,currency',
            'fulfillment.items.listing:id,title,unit,price_per_unit',
        ])->findOrFail($id);

        if ($payout->farmer_id !== $user->id) {
            return response()->json([
                'message' => 'You are not authorized to view this payout.',
            ], 403);
        }

        return response()->json([
            'payout' => $payout,
        ]);
    }

    /**
     * List all payouts across the platform (admin only).
     *
     * GET /api/admin/payouts?status=pending&farmer_id=5&per_page=20
     */
    public function adminIndex(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (! $user->is_admin) {
            return response()->json([
                'message' => 'Unauthorized. Admin access required.',
            ], 403);
        }

        $request->validate([
            'status'    => ['sometimes', 'string', 'in:pending,processed,failed'],
            'farmer_id' => ['sometimes', 'integer', 'exists:users,id'],
            'per_page'  => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $query = Payout::with([
            'farmer:id,first_name,second_name,phone',
            'fulfillment:id,order_id,status,subtotal_amount,completed_at',
            'fulfillment.order:id,order_number,status,currency',
        ]);

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('farmer_id')) {
            $query->where('farmer_id', $request->input('farmer_id'));
        }

        $payouts = $query->orderByDesc('created_at')
            ->paginate($request->input('per_page', 20));

        return response()->json($payouts);
    }

    /**
     * Show a single payout with full details (admin only).
     *
     * GET /api/admin/payouts/{id}
     */
    public function adminShow(int $id): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (! $user->is_admin) {
            return response()->json([
                'message' => 'Unauthorized. Admin access required.',
            ], 403);
        }

        $payout = Payout::with([
            'farmer:id,first_name,second_name,phone',
            'fulfillment:id,order_id,status,subtotal_amount,completed_at',
            'fulfillment.order:id,order_number,status,total_amount,currency',
            'fulfillment.items.listing:id,title,unit,price_per_unit',
        ])->findOrFail($id);

        return response()->json([
            'payout' => $payout,
        ]);
    }

    /**
     * Mark a pending payout as processed (admin only).
     *
     * POST /api/admin/payouts/{id}/process
     * Body: { "reference": "CHAPA-TRF-ABC123" }
     *
     * The admin confirms the payout has been transferred to the farmer
     * (e.g. via Chapa transfer, bank transfer, mobile money).
     */
    public function process(Request $request, int $id): JsonResponse
    {
        /** @var \App\Models\User $user */
        $admin = Auth::user();

        if (! $admin->is_admin) {
            return response()->json([
                'message' => 'Unauthorized. Admin access required.',
            ], 403);
        }

        $validated = $request->validate([
            'reference' => ['required', 'string', 'max:255'],
        ]);

        $payout = Payout::findOrFail($id);

        if ($payout->status !== 'pending') {
            return response()->json([
                'message' => 'Only pending payouts can be marked as processed.',
            ], 422);
        }

        $payout->update([
            'status'       => 'processed',
            'reference'    => $validated['reference'],
            'processed_at' => now(),
        ]);

        return response()->json([
            'message' => 'Payout marked as processed.',
            'payout'  => $payout->fresh()->load([
                'farmer:id,first_name,second_name,phone',
                'fulfillment:id,order_id,status,subtotal_amount',
                'fulfillment.order:id,order_number,currency',
            ]),
        ]);
    }

    /**
     * Mark a pending payout as failed (admin only).
     *
     * POST /api/admin/payouts/{id}/fail
     * Body: { "reference": "CHAPA-TRF-FAILED-XYZ" }  (optional)
     *
     * The admin records that the payout transfer failed.
     * The payout can be retried later by creating a new process attempt.
     */
    public function fail(Request $request, int $id): JsonResponse
    {
        /** @var \App\Models\User $user */
        $admin = Auth::user();

        if (! $admin->is_admin) {
            return response()->json([
                'message' => 'Unauthorized. Admin access required.',
            ], 403);
        }

        $validated = $request->validate([
            'reference' => ['nullable', 'string', 'max:255'],
        ]);

        $payout = Payout::findOrFail($id);

        if ($payout->status !== 'pending') {
            return response()->json([
                'message' => 'Only pending payouts can be marked as failed.',
            ], 422);
        }

        $payout->update([
            'status'       => 'failed',
            'reference'    => $validated['reference'] ?? $payout->reference,
            'processed_at' => now(),
        ]);

        return response()->json([
            'message' => 'Payout marked as failed.',
            'payout'  => $payout->fresh()->load([
                'farmer:id,first_name,second_name,phone',
                'fulfillment:id,order_id,status,subtotal_amount',
                'fulfillment.order:id,order_number,currency',
            ]),
        ]);
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
