<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('next_of_kin', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('basic_information_ulid')->constrained()->cascadeOnDelete();
            $table->string('nok_name');
            $table->string('nok_relationship');
            $table->string('nok_address');
            $table->string('nok_phone_number');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('next_of_kin');
    }
};
