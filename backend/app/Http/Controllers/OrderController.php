<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderFulfillment;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class OrderController extends Controller
{
    /**
     * Get all orders for authenticated buyer.
     */
    public function index(Request $request): Response
    {
        $user = auth()->user();
        
        $query = Order::where('buyer_id', $user->id)
            ->with(['fulfillments', 'items', 'payment']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $orders = $query->orderBy('placed_at', 'desc')->paginate(15);
        return response($orders);
    }

    /**
     * Display a specific order.
     */
    public function show(Order $order): Response
    {
        $this->authorize('view', $order);
        
        $order->load([
            'buyer',
            'fulfillments' => function ($query) {
                $query->with(['farmer', 'items.listing']);
            },
            'items',
            'payment',
        ]);
        
        return response($order);
    }

    /**
     * Create a new order from cart (checkout).
     * Handled by OrderService/CheckoutService in practice.
     */
    public function store(Request $request): Response
    {
        return response(['error' => 'Use checkout endpoint'], 400);
    }

    /**
     * Update order status (via payment/fulfillment events).
     */
    public function update(Request $request, Order $order): Response
    {
        $this->authorize('update', $order);

        $validated = $request->validate([
            'status' => 'sometimes|in:pending_payment,payment_confirmed,processing,partially_fulfilled,completed,cancelled',
        ]);

        $order->update($validated);
        return response($order);
    }

    /**
     * Cancel an order (buyer only, before payment confirmed).
     */
    public function cancel(Order $order): Response
    {
        $this->authorize('cancel', $order);

        if ($order->status !== 'pending_payment') {
            return response(['error' => 'Cannot cancel order in ' . $order->status . ' state'], 422);
        }

        $order->update(['status' => 'cancelled']);
        return response(['message' => 'Order cancelled'], 200);
    }

    /**
     * Get order fulfillments (breakdown by farmer).
     */
    public function fulfillments(Order $order): Response
    {
        $this->authorize('view', $order);
        
        $fulfillments = $order->fulfillments()
            ->with(['farmer', 'items.listing'])
            ->get();
        
        return response($fulfillments);
    }

    /**
     * Get order items (all line items).
     */
    public function items(Order $order): Response
    {
        $this->authorize('view', $order);
        
        $items = $order->items()
            ->with(['listing', 'fulfillment.farmer'])
            ->get();
        
        return response($items);
    }

    /**
     * Get payment info for order.
     */
    public function payment(Order $order): Response
    {
        $this->authorize('view', $order);
        
        $payment = $order->payment;
        
        if (!$payment) {
            return response(['error' => 'No payment found'], 404);
        }

        return response($payment);
    }

    /**
     * Get buyer's order history.
     */
    public function history(Request $request): Response
    {
        $user = auth()->user();
        
        $orders = Order::where('buyer_id', $user->id)
            ->with(['fulfillments', 'payment'])
            ->orderBy('placed_at', 'desc')
            ->paginate(20);
        
        return response($orders);
    }
}
