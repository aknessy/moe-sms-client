<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The Suggestions (By Superior Officer) section of the Annual Performance
 * Evaluation Report: whether the reporting officer suggests the officer for
 * a different job in the same grade, and the kind of job and reasons where
 * they do.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suggestions', function (Blueprint $table) {
            $table->id();
            // One record per officer; a later save corrects this one rather
            // than adding beside it.
            $table->string('basic_information_ulid', 26)->unique();
            $table->boolean('different_job_suggested');
            $table->text('different_job_details')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suggestions');
    }
};
