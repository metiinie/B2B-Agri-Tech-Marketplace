<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique(); // human-friendly reference, e.g. ORD-2026-000123
            $table->foreignId('buyer_id')->constrained('users')->cascadeOnDelete();

            // Aggregate status, derived from payment + all order_fulfillments.
            // pending_payment -> payment_confirmed -> processing
            //   -> completed | partially_fulfilled | cancelled
            $table->enum('status', [
                'pending_payment',
                'payment_confirmed',
                'processing',
                'partially_fulfilled',
                'completed',
                'cancelled',
            ])->default('pending_payment');

            $table->decimal('total_amount', 14, 2);
            $table->string('currency', 3)->default('ETB');
            $table->timestamp('placed_at')->useCurrent();

            $table->timestamps();

            $table->index(['buyer_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
