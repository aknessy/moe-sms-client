<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The Countersigning Officer's Report section of the Annual Performance
 * Evaluation Report: whether they agree with the reporting officer, how long
 * the officer has served under them, and who they are.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('counter_signing_reports', function (Blueprint $table) {
            $table->id();
            // One report per officer; a later save corrects it.
            $table->string('basic_information_ulid', 26)->unique();
            $table->text('comments');
            $table->unsignedSmallInteger('served_years');
            $table->unsignedSmallInteger('served_months');
            $table->string('post_held', 160);
            $table->string('grade_level', 20);
            $table->string('officer_name', 160);
            $table->date('declared_on');
            $table->string('signature', 160);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('counter_signing_reports');
    }
};
