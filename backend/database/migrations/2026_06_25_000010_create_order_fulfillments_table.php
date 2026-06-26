<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Doc: "Per-farmer order fulfillment records with accept, reject, and
        // complete states" + "Each farmer reviews only the fulfillment rows
        // that belong to them." One order -> one row per distinct farmer
        // involved in that order.
        Schema::create('order_fulfillments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('farmer_id')->constrained('users')->cascadeOnDelete();

            $table->enum('status', ['pending', 'accepted', 'rejected', 'completed', 'cancelled'])
                ->default('pending');

            $table->decimal('subtotal_amount', 14, 2); // sum of this farmer's order_items
            $table->text('farmer_notes')->nullable();  // e.g. rejection reason

            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();

            $table->index(['farmer_id', 'status']);
            $table->index('order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_fulfillments');
    }
};
