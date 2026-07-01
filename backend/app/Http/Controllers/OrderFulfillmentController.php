<?php

namespace App\Http\Controllers;

use App\Models\OrderFulfillment;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class OrderFulfillmentController extends Controller
{
    /**
     * Get all fulfillments for authenticated farmer.
     */
    public function index(Request $request): Response
    {
        $user = auth()->user();

        $query = OrderFulfillment::where('farmer_id', $user->id)
            ->with(['order.buyer', 'items.listing', 'farmer']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $fulfillments = $query->orderBy('created_at', 'desc')->paginate(15);
        return response($fulfillments);
    }

    /**
     * Display a specific fulfillment (farmer only - scoped to their own).
     */
    public function show(OrderFulfillment $fulfillment): Response
    {
        $this->authorize('view', $fulfillment);

        $fulfillment->load(['order.buyer', 'items.listing', 'farmer']);
        return response($fulfillment);
    }

    /**
     * Accept a fulfillment (farmer action).
     */
    public function accept(OrderFulfillment $fulfillment): Response
    {
        $this->authorize('accept', $fulfillment);

        if ($fulfillment->status !== 'pending') {
            return response(['error' => 'Fulfillment is already ' . $fulfillment->status], 422);
        }

        $fulfillment->update([
            'status' => 'accepted',
            'accepted_at' => now(),
        ]);

        // Audit log
        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'fulfillment.accepted',
            'auditable_type' => OrderFulfillment::class,
            'auditable_id' => $fulfillment->id,
            'new_values' => ['status' => 'accepted', 'accepted_at' => $fulfillment->accepted_at],
            'ip_address' => request()->ip(),
        ]);

        return response([
            'message' => 'Fulfillment accepted',
            'fulfillment' => $fulfillment,
        ]);
    }

    /**
     * Reject a fulfillment (farmer action).
     */
    public function reject(Request $request, OrderFulfillment $fulfillment): Response
    {
        $this->authorize('reject', $fulfillment);

        $validated = $request->validate([
            'farmer_notes' => 'required|string|max:500',
        ]);

        if ($fulfillment->status !== 'pending') {
            return response(['error' => 'Fulfillment is already ' . $fulfillment->status], 422);
        }

        $fulfillment->update([
            'status' => 'rejected',
            'rejected_at' => now(),
            'farmer_notes' => $validated['farmer_notes'],
        ]);

        // Audit log
        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'fulfillment.rejected',
            'auditable_type' => OrderFulfillment::class,
            'auditable_id' => $fulfillment->id,
            'new_values' => [
                'status' => 'rejected',
                'rejected_at' => $fulfillment->rejected_at,
                'farmer_notes' => $validated['farmer_notes'],
            ],
            'ip_address' => request()->ip(),
        ]);

        return response([
            'message' => 'Fulfillment rejected',
            'fulfillment' => $fulfillment,
        ]);
    }

    /**
     * Mark fulfillment as completed (farmer action).
     */
    public function complete(OrderFulfillment $fulfillment): Response
    {
        $this->authorize('complete', $fulfillment);

        if ($fulfillment->status !== 'accepted') {
            return response(['error' => 'Fulfillment must be accepted before marking complete'], 422);
        }

        $fulfillment->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        // Audit log
        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'fulfillment.completed',
            'auditable_type' => OrderFulfillment::class,
            'auditable_id' => $fulfillment->id,
            'new_values' => ['status' => 'completed', 'completed_at' => $fulfillment->completed_at],
            'ip_address' => request()->ip(),
        ]);

        return response([
            'message' => 'Fulfillment marked as completed',
            'fulfillment' => $fulfillment,
        ]);
    }

    /**
     * Get items for a fulfillment.
     */
    public function items(OrderFulfillment $fulfillment): Response
    {
        $this->authorize('view', $fulfillment);

        $items = $fulfillment->items()->with('listing')->get();
        return response($items);
    }

    /**
     * Get pending fulfillments for farmer.
     */
    public function pending(): Response
    {
        $user = auth()->user();

        $fulfillments = OrderFulfillment::where('farmer_id', $user->id)
            ->where('status', 'pending')
            ->with(['order.buyer', 'items.listing'])
            ->orderBy('created_at', 'asc')
            ->get();

        return response($fulfillments);
    }

    /**
     * Get fulfillment summary for farmer.
     */
    public function summary(): Response
    {
        $user = auth()->user();

        $summary = [
            'pending' => OrderFulfillment::where('farmer_id', $user->id)->where('status', 'pending')->count(),
            'accepted' => OrderFulfillment::where('farmer_id', $user->id)->where('status', 'accepted')->count(),
            'completed' => OrderFulfillment::where('farmer_id', $user->id)->where('status', 'completed')->count(),
            'rejected' => OrderFulfillment::where('farmer_id', $user->id)->where('status', 'rejected')->count(),
        ];

        return response($summary);
    }
}
