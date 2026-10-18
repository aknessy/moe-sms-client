<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('postings', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->string('basic_information_ulid')->index();
            $table->string('posting_type');          // transfer | deployment | secondment
            $table->string('from_department')->nullable();
            $table->string('to_department');
            $table->string('duty_station')->nullable();
            $table->date('effective_date');
            $table->string('reference_number')->nullable();
            $table->text('remarks')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('postings');
    }
};
