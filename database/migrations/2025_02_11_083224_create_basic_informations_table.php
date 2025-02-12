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
        Schema::create('basic_informations', function (Blueprint $table) {
            $table->id();
            $table->ulid('uuid')->unique();
            $table->string('computer_number');
            $table->string('surname');
            $table->string('firstname');
            $table->string('othernames');
            $table->date('date_of_birth');
            $table->string('phone_number');
            $table->string('email')->nullable();
            $table->integer('state_of_origin');
            $table->integer('lga_of_origin');
            $table->string('pfa')->nullable();
            $table->string('rsa')->nullable();
            $table->bigInteger('nhis')->nullable();
            $table->text('address')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('basic_informations');
    }
};
