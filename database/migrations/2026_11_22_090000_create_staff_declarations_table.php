<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The Staff Declaration section of the Annual Performance Evaluation Report:
 * the officer's own certification that they have seen the report and that
 * the reporting officer has discussed it with them, with whatever comments
 * they have to make on it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_declarations', function (Blueprint $table) {
            $table->id();
            // One declaration per officer; a later save corrects this one
            // rather than adding beside it.
            $table->string('basic_information_ulid', 26)->unique();
            $table->text('comments');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_declarations');
    }
};
