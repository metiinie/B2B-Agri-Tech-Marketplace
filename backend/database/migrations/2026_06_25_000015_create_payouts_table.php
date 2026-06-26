<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Doc: farmer permission "view payout history." One row per settled
        // fulfillment; can be grouped into batches later without changing
        // this shape if the platform introduces scheduled payout runs.
        Schema::create('payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farmer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('order_fulfillment_id')->constrained('order_fulfillments')->restrictOnDelete();

            $table->decimal('amount', 14, 2);
            $table->enum('status', ['pending', 'processed', 'failed'])->default('pending');
            $table->string('reference')->nullable(); // payout/transfer reference
            $table->timestamp('processed_at')->nullable();

            $table->timestamps();

            $table->index(['farmer_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payouts');
    }
};
