<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * An officer asking to leave the service early.
 *
 * Deliberately not a row in staff_exits: an exit is a thing that has happened,
 * and recording one marks the officer retired on the spot. This is a request,
 * and it does nothing to their standing until someone approves it — at which
 * point the exit is raised through Pension & Gratuity as it always was.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('voluntary_retirement_requests', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->string('basic_information_ulid', 26);
            // The day they are asking to go.
            $table->date('intended_date');
            $table->text('reason')->nullable();
            $table->string('status')->default('pending');
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->index('basic_information_ulid');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voluntary_retirement_requests');
    }
};
