<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('capability_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // "Separate application and approval flows for farmer and buyer
            // capabilities" - a user can hold/apply for both.
            $table->enum('capability_type', ['farmer', 'buyer']);
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');

            // References to uploaded verification documents (e.g. ID, business
            // license) - actual files live in storage, this just indexes them.
            $table->json('supporting_documents')->nullable();
            $table->text('rejection_reason')->nullable();

            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'capability_type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('capability_applications');
    }
};
