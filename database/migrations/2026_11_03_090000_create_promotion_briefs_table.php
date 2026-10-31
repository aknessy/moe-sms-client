<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The brief put up for a year's promotion exercise: who was listed, and what
 * they stood at when the list was drawn.
 *
 * Who is due can be worked out from the records at any time, but a brief is a
 * document — it says what was put up, on the day it was put up. Recalculating
 * it a year later would answer a different question, because the officers on
 * it will have moved. So it is recorded rather than only derived.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotion_briefs', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            // The calendar year the exercise covers.
            $table->unsignedSmallInteger('year');
            $table->string('basic_information_ulid', 26);

            // Their standing when the brief was drawn, so the document still
            // reads true after they have moved.
            $table->string('designation')->nullable();
            $table->unsignedSmallInteger('grade_level')->nullable();
            $table->unsignedSmallInteger('step')->nullable();

            // What the due date was worked from, and what it came to.
            $table->date('last_moved_on')->nullable();
            $table->date('due_on')->nullable();

            $table->string('status')->default('pending');
            $table->text('remarks')->nullable();
            $table->timestamps();

            // An officer appears once in a year's brief.
            $table->unique(['year', 'basic_information_ulid'], 'one_entry_per_officer_per_year');
            $table->index('year');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotion_briefs');
    }
};
