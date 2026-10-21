<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reference table the basic_informations.state_of_origin integer points at.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('states', function (Blueprint $table) {
            $table->integer('id')->primary();
            $table->string('name')->unique();
            // First three letters of the state name, e.g. Cross River -> CRO.
            $table->string('code', 3)->unique();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('states');
    }
};
