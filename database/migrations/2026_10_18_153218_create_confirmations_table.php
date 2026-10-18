<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('confirmations', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->string('basic_information_ulid')->index();
            $table->date('appointment_date');
            $table->date('confirmation_date')->nullable();
            $table->string('grade_level')->nullable();
            $table->string('step')->nullable();
            $table->string('reference_number')->nullable();
            $table->string('status')->default('pending');   // pending | confirmed | deferred
            $table->text('remarks')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('confirmations');
    }
};
