<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Derived/active capability grants. capability_applications holds the
        // full request/review history (including past rejections and
        // re-applications); this table holds the current state used by
        // authorization checks ("does this user currently have farmer access?").
        Schema::create('user_capabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('capability_type', ['farmer', 'buyer']);

            $table->foreignId('capability_application_id')
                ->nullable()
                ->constrained('capability_applications')
                ->nullOnDelete();

            $table->enum('status', ['active', 'revoked'])->default('active');
            $table->foreignId('granted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('granted_at')->nullable();
            $table->timestamp('revoked_at')->nullable();

            $table->timestamps();

            // A user holds at most one row per capability type.
            $table->unique(['user_id', 'capability_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_capabilities');
    }
};
