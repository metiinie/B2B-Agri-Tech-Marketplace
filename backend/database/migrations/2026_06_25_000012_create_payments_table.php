<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained()->cascadeOnDelete();

            $table->string('chapa_tx_ref')->unique(); // tx ref we generate and send to Chapa
            $table->string('chapa_checkout_url')->nullable();

            $table->decimal('amount', 14, 2);
            $table->string('currency', 3)->default('ETB');

            // --------------------------------------------------------------
            // MANDATORY CORE DATABASE LOGIC RULE (per doc):
            // pending -> confirmed ("paid") may ONLY be written by the signed
            // Chapa webhook handler. No controller, admin action, or "mark as
            // paid manually" code path is allowed to set this value.
            // This is an application-authorization rule, not something a
            // column type alone can enforce - restrict write access to a
            // single PaymentWebhookService in code, and never expose a
            // PATCH/PUT route that touches this column.
            // --------------------------------------------------------------
            $table->enum('status', ['pending', 'confirmed', 'failed', 'cancelled'])->default('pending');
            $table->timestamp('confirmed_at')->nullable();

            // Non-sensitive gateway metadata only - never card/wallet data
            // (doc: "sensitive payment data never stored in the application").
            $table->json('gateway_metadata')->nullable();

            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
