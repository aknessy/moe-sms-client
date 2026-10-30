<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Trainings the command is running, and the officers who have put their names
 * down for them.
 *
 * Indicating interest is not the same as being nominated: it says an officer
 * would like to attend, and leaves the choosing to whoever runs the exercise.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trainings', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('venue')->nullable();
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            // Shut once the list is drawn up; a closed training still shows,
            // so an officer can see what they put their name down for.
            $table->boolean('is_open')->default(1);
            $table->timestamps();
        });

        Schema::create('training_interests', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->foreignId('training_id')->constrained()->cascadeOnDelete();
            $table->string('basic_information_ulid', 26);
            $table->timestamps();

            // An officer's name goes down once, however many times they click.
            $table->unique(['training_id', 'basic_information_ulid'], 'one_interest_per_officer');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_interests');
        Schema::dropIfExists('trainings');
    }
};
