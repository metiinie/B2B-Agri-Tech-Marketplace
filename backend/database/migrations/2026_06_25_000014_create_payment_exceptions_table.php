<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Doc: "Admin handles only exceptions, disputes, and audit review" and
        // "No manual payment confirmation capability." This table is
        // deliberately separate from `payments` - resolving a row here never
        // writes payments.status directly; it only records the dispute
        // workflow (e.g. triggering a refund via the Chapa API, which itself
        // would arrive back through a webhook).
        Schema::create('payment_exceptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('raised_by')->nullable()->constrained('users')->nullOnDelete();

            $table->enum('type', ['dispute', 'mismatch', 'failed_payment_review', 'refund_request', 'other']);
            $table->text('description');

            $table->enum('status', ['open', 'investigating', 'resolved', 'rejected'])->default('open');
            $table->text('resolution_notes')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();

            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_exceptions');
    }
};
