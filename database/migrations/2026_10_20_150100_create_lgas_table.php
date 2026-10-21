<?php

use Database\Seeders\StateLgaSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reference table the basic_informations.lga_of_origin integer points at.
 * Each LGA belongs to exactly one state.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lgas', function (Blueprint $table) {
            $table->integer('id')->primary();
            $table->integer('state_id');
            $table->string('lga_name');

            $table->foreign('state_id')->references('id')->on('states')->cascadeOnDelete();

            // The dependent dropdown always asks for one state's LGAs.
            $table->index('state_id');
            $table->unique(['state_id', 'lga_name']);
        });

        // Fixed national reference data: the application is unusable without
        // it, so it ships with the schema rather than with the demo seeders.
        (new StateLgaSeeder)->run();
    }

    public function down(): void
    {
        Schema::dropIfExists('lgas');
    }
};
