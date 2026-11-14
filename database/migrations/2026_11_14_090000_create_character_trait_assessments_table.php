<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The Character Traits section of the Annual Performance Evaluation Report:
 * a grade, Poor to Outstanding, for each of dependability, appearance,
 * attendance and resource utilization — and the reporting officer's own
 * words for why, covering all four together rather than one apiece.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('character_trait_assessments', function (Blueprint $table) {
            $table->id();
            // One assessment per officer; a later save corrects this one
            // rather than adding beside it.
            $table->string('basic_information_ulid', 26)->unique();
            $table->string('dependability_grade', 20);
            $table->string('appearance_grade', 20);
            $table->string('attendance_grade', 20);
            $table->string('resource_utilization_grade', 20);
            $table->text('justification');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('character_trait_assessments');
    }
};
