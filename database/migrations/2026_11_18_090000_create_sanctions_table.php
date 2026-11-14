<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The Sanctions section of the Annual Performance Evaluation Report, for an
 * officer with no caution or query already on file: whether disciplinary
 * action was taken against them during the period covered, and what it was
 * where it was. An officer with a query on record is shown that instead —
 * this is only asked where there is nothing there to show.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sanctions', function (Blueprint $table) {
            $table->id();
            // One record per officer; a later save corrects this one rather
            // than adding beside it.
            $table->string('basic_information_ulid', 26)->unique();
            $table->boolean('taken');
            $table->text('details')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sanctions');
    }
};
