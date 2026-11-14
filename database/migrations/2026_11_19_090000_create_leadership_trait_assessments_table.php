<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The Leadership Traits section of the Annual Performance Evaluation
 * Report: a letter grade, A to F, for each of five questions on how the
 * officer leads, and one more for the overall assessment the five of them
 * add up to.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leadership_trait_assessments', function (Blueprint $table) {
            $table->id();
            // One assessment per officer; a later save corrects this one
            // rather than adding beside it.
            $table->string('basic_information_ulid', 26)->unique();
            $table->string('standards_grade', 1);
            $table->string('timeliness_grade', 1);
            $table->string('example_grade', 1);
            $table->string('suggestions_grade', 1);
            $table->string('value_added_grade', 1);
            $table->string('overall_grade', 1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leadership_trait_assessments');
    }
};
