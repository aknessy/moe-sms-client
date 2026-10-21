<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Working career is the one wizard section that may legitimately be left empty:
 * the post being recorded can be the officer's first job, in which case there
 * is no prior appointment and no date of employment to give.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('careers', function (Blueprint $table) {
            // Column name carries the original misspelling from the schema.
            $table->date('date_of_employement')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('careers', function (Blueprint $table) {
            $table->date('date_of_employement')->nullable(false)->change();
        });
    }
};
