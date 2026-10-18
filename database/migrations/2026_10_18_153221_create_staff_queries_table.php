<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_queries', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->string('basic_information_ulid')->index();
            $table->date('query_date');
            $table->string('reference_number')->nullable();
            $table->string('offence');
            $table->text('details')->nullable();
            $table->date('response_date')->nullable();
            $table->text('response')->nullable();
            $table->string('sanction')->nullable();         // none | caution | warning | suspension | demotion | dismissal
            $table->string('status')->default('issued');    // issued | responded | closed
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_queries');
    }
};
