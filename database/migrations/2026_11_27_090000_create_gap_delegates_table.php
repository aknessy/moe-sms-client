<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The officers HOD GAP has designated as Registry Officers: they work
 * alongside the head, but neither sign nor approve on their behalf. The role
 * they held before is kept so revoking puts them back where they were.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gap_delegates', function (Blueprint $table) {
            $table->id();
            $table->string('basic_information_ulid', 26)->unique();
            $table->string('designated_by', 26);
            $table->unsignedBigInteger('previous_role_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gap_delegates');
    }
};
