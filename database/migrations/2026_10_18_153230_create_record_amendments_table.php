<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('record_amendments', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->string('basic_information_ulid')->index();
            $table->string('field');            // which bio-data field the request targets
            $table->string('current_value')->nullable();
            $table->string('requested_value');
            $table->text('reason')->nullable();
            $table->string('supporting_document')->nullable();
            $table->string('status')->default('pending');   // pending | approved | rejected
            $table->string('reviewed_by')->nullable();
            $table->date('reviewed_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('record_amendments');
    }
};
