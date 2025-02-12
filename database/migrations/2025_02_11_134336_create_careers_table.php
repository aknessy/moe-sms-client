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
        Schema::create('careers', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('uuid')->constrained()->cascadeOnDelete();
            $table->date('date_of_employement');
            $table->string('rank');
            $table->integer('grade_level');
            $table->string('ministerial_dept');
            $table->string('mode_of_employment');
            $table->json('address_of_ministerial_dept');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('careers');
    }
};
