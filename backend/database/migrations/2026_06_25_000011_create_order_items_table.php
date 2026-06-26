<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_fulfillment_id')->constrained('order_fulfillments')->cascadeOnDelete();

            // restrictOnDelete: a listing that has been ordered should never
            // be hard-deleted (listings use soft deletes for this reason).
            $table->foreignId('listing_id')->constrained()->restrictOnDelete();

            $table->decimal('quantity', 12, 3);

            // Snapshot of listings.price_per_unit AT ORDER TIME. Stays fixed
            // even if listing_price_history records later changes.
            $table->decimal('unit_price', 12, 2);
            $table->decimal('subtotal', 14, 2);

            $table->timestamps();

            $table->index('order_id');
            $table->index('order_fulfillment_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
