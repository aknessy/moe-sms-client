<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * What an officer says they did, quarter by quarter, and what the reporting
 * officer says about each of it.
 *
 * The Self Reporting section of the appraisal runs over three years, and each
 * year over its first three quarters, so an activity only means anything
 * beside the quarter it was performed in. A row per activity rather than a
 * list per quarter: the reporting officer answers them one at a time.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('self_report_activities', function (Blueprint $table) {
            $table->id();
            $table->string('basic_information_ulid', 26);
            // The calendar year, not first-second-third: a report opened two
            // years from now has to still say which year this was.
            $table->year('year');
            $table->unsignedTinyInteger('quarter');
            // The order they were written in, which is the order they read in.
            $table->unsignedTinyInteger('position')->default(0);
            $table->text('activity');
            // Written by the reporting officer, after the officer has said
            // what they did — so it is empty for as long as that takes.
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->index(['basic_information_ulid', 'year', 'quarter'], 'an_officers_quarter');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('self_report_activities');
    }
};
