<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('first_name')->after('id')->nullable();
            $table->string('second_name')->after('first_name')->nullable();
        });

        // Backfill first_name from existing name column so the NOT NULL constraint won't fail
        DB::table('users')->whereNull('first_name')->update([
            'first_name' => DB::raw('name'),
            'second_name' => '',
        ]);

        Schema::table('users', function (Blueprint $table) {
            $table->string('first_name')->nullable(false)->change();
            $table->string('second_name')->nullable(false)->change();
            $table->string('password')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('name')->nullable(false)->change();
            $table->string('password')->nullable()->change();
            $table->dropColumn(['first_name', 'second_name']);
        });
    }
};
