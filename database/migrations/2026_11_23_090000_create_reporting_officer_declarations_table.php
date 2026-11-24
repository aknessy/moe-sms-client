<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The Declaration by Reporting Officer section of the Annual Performance
 * Evaluation Report: the reporting officer's own statement that they
 * assessed the officer with the highest sense of responsibility, naming
 * themselves, the post and grade they hold, and the day they signed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reporting_officer_declarations', function (Blueprint $table) {
            $table->id();
            // One declaration per officer reported on; a later save corrects
            // this one rather than adding beside it.
            $table->string('basic_information_ulid', 26)->unique();
            $table->string('officer_name', 160);
            $table->string('post_held', 160);
            $table->string('grade_level', 20);
            $table->date('declared_on');
            $table->string('signature', 160);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reporting_officer_declarations');
    }
};
