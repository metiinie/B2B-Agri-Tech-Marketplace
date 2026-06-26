<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farmer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();

            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('unit', ['kg', 'quintal', 'ton', 'piece', 'liter', 'dozen'])->default('kg');

            $table->decimal('price_per_unit', 12, 2);

            // Split into available vs reserved so checkout can hold stock
            // (decrement available / increment reserved inside a locked
            // transaction) without ever losing track of total stock.
            $table->decimal('quantity_available', 12, 3)->default(0);
            $table->decimal('quantity_reserved', 12, 3)->default(0);

            $table->enum('status', ['active', 'inactive', 'sold_out'])->default('active');

            $table->timestamps();
            $table->softDeletes(); // preserve history for past orders if a listing is removed

            $table->index(['status', 'category_id']);
            $table->index('farmer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listings');
    }
};
