<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The Commendations section of the Annual Performance Evaluation Report:
 * whether the officer received a written commendation during the year for
 * outstanding performance, and the detail of what it was where they did.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commendations', function (Blueprint $table) {
            $table->id();
            // One record per officer; a later save corrects this one rather
            // than adding beside it.
            $table->string('basic_information_ulid', 26)->unique();
            $table->boolean('received');
            $table->text('details')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commendations');
    }
};
