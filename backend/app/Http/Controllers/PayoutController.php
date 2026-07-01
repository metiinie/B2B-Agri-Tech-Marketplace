<?php

namespace App\Http\Controllers;

use App\Models\Payout;
use App\Models\OrderFulfillment;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PayoutController extends Controller
{
    /**
     * Get all payouts for authenticated farmer.
     */
    public function index(Request $request): Response
    {
        $user = auth()->user();

        $query = Payout::where('farmer_id', $user->id)
            ->with(['fulfillment.order']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $payouts = $query->orderBy('created_at', 'desc')->paginate(20);
        return response($payouts);
    }

    /**
     * Display a specific payout.
     */
    public function show(Payout $payout): Response
    {
        $this->authorize('view', $payout);

        $payout->load(['fulfillment.order', 'farmer']);
        return response($payout);
    }

    /**
     * Get payout summary for authenticated farmer.
     */
    public function summary(): Response
    {
        $user = auth()->user();

        $payouts = Payout::where('farmer_id', $user->id)->get();

        $summary = [
            'total_earned' => $payouts->sum('amount'),
            'pending' => $payouts->where('status', 'pending')->sum('amount'),
            'processed' => $payouts->where('status', 'processed')->sum('amount'),
            'failed' => $payouts->where('status', 'failed')->sum('amount'),
            'payout_count' => $payouts->count(),
            'pending_count' => $payouts->where('status', 'pending')->count(),
            'processed_count' => $payouts->where('status', 'processed')->count(),
        ];

        return response($summary);
    }

    /**
     * Get pending payouts for a farmer.
     */
    public function pending(): Response
    {
        $user = auth()->user();

        $payouts = Payout::where('farmer_id', $user->id)
            ->where('status', 'pending')
            ->with('fulfillment.order')
            ->orderBy('created_at', 'asc')
            ->get();

        return response($payouts);
    }

    /**
     * Get processed payouts for a farmer.
     */
    public function processed(): Response
    {
        $user = auth()->user();

        $payouts = Payout::where('farmer_id', $user->id)
            ->where('status', 'processed')
            ->with(['fulfillment.order'])
            ->orderBy('processed_at', 'desc')
            ->paginate(20);

        return response($payouts);
    }

    /**
     * Create a payout (admin action, typically triggered by batch job).
     * In practice, this is called by a PayoutService after settlement.
     */
    public function store(Request $request): Response
    {
        $this->authorize('create', Payout::class);

        $validated = $request->validate([
            'farmer_id' => 'required|exists:users,id',
            'order_fulfillment_id' => 'required|exists:order_fulfillments,id',
            'amount' => 'required|numeric|min:0.01',
            'reference' => 'nullable|string|max:255',
        ]);

        // Verify the fulfillment belongs to the farmer
        $fulfillment = OrderFulfillment::findOrFail($validated['order_fulfillment_id']);
        if ($fulfillment->farmer_id != $validated['farmer_id']) {
            return response(['error' => 'Fulfillment does not belong to this farmer'], 422);
        }

        $payout = Payout::create($validated);
        return response($payout, 201);
    }

    /**
     * Update payout status (admin only).
     */
    public function updateStatus(Request $request, Payout $payout): Response
    {
        $this->authorize('update', $payout);

        $validated = $request->validate([
            'status' => 'required|in:pending,processed,failed',
            'reference' => 'sometimes|string|max:255',
        ]);

        $payout->update($validated);

        if ($validated['status'] === 'processed') {
            $payout->update(['processed_at' => now()]);
        }

        return response([
            'message' => 'Payout status updated',
            'payout' => $payout,
        ]);
    }

    /**
     * Get payout history for admin dashboard.
     */
    public function history(Request $request): Response
    {
        $query = Payout::with(['farmer', 'fulfillment.order']);

        if ($request->has('farmer_id')) {
            $query->where('farmer_id', $request->farmer_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $payouts = $query->orderBy('created_at', 'desc')->paginate(20);
        return response($payouts);
    }

    /**
     * Get monthly payout report.
     */
    public function monthlyReport(Request $request): Response
    {
        $validated = $request->validate([
            'month' => 'required|date_format:Y-m',
        ]);

        $farmer = auth()->user();
        $month = $validated['month'];

        $payouts = Payout::where('farmer_id', $farmer->id)
            ->whereYear('created_at', substr($month, 0, 4))
            ->whereMonth('created_at', substr($month, 5, 2))
            ->with('fulfillment.order')
            ->get();

        $report = [
            'month' => $month,
            'total_payouts' => $payouts->count(),
            'total_amount' => $payouts->sum('amount'),
            'processed_amount' => $payouts->where('status', 'processed')->sum('amount'),
            'pending_amount' => $payouts->where('status', 'pending')->sum('amount'),
            'payouts' => $payouts,
        ];

        return response($report);
    }
}
