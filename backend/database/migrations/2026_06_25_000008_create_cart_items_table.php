<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // No separate "carts" table needed - a buyer has at most one open
        // cart, represented simply as their own rows here.
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('buyer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('listing_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity', 12, 3);

            // Display-only snapshot; checkout always re-validates against the
            // listing's CURRENT price_per_unit before creating order_items.
            $table->decimal('price_snapshot', 12, 2);

            $table->timestamps();

            $table->unique(['buyer_id', 'listing_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cart_items');
    }
};
