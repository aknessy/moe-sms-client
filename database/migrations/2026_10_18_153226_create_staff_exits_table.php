<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_exits', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->string('basic_information_ulid')->index();
            $table->string('exit_type');                       // retirement | resignation | dismissal | death | transfer-of-service
            $table->date('effective_date');
            $table->string('reference_number')->nullable();
            $table->string('gratuity_status')->default('pending');  // pending | processing | paid
            $table->string('pension_status')->default('pending');
            $table->text('remarks')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_exits');
    }
};
