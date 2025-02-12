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
        Schema::create('edu_progresses', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('uuid')->constrained()->cascadeOnDelete();
            $table->json('educational_record');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('edu_progresses');
    }
};
