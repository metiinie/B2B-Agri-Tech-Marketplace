<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Doc: "Signed webhook handling for payment confirmation" +
        // "payment webhooks and order updates processed safely and
        // idempotently." Every inbound Chapa webhook call is logged here
        // BEFORE being acted on, so retries/duplicates can be detected.
        Schema::create('payment_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->nullable()->constrained('payments')->nullOnDelete();

            $table->string('chapa_tx_ref');
            $table->string('event_type');

            // Chapa-provided event id, when present, is the most reliable
            // idempotency key. NOTE: MySQL unique indexes allow multiple NULLs,
            // so if this is ever null for a given gateway event, idempotency
            // for that row must also be checked in application code (e.g.
            // "is there already a processed row for this tx_ref+event_type?")
            // rather than relying on the unique index alone.
            $table->string('chapa_event_id')->nullable();

            $table->json('payload'); // raw webhook body, for audit
            $table->boolean('signature_verified')->default(false);

            $table->enum('processing_status', ['received', 'processed', 'duplicate_ignored', 'failed'])
                ->default('received');
            $table->timestamp('processed_at')->nullable();

            $table->timestamps();

            $table->unique(['chapa_tx_ref', 'event_type', 'chapa_event_id'], 'webhook_idempotency_unique');
            $table->index('processing_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_webhook_events');
    }
};
