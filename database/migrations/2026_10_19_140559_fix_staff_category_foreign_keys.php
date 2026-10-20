<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The original migrations used `foreignUlid('basic_information_ulid')->constrained()`.
 * `constrained()` infers the parent table by stripping `_id`, but these columns end
 * in `_ulid`, so it pluralised the whole name and pointed every foreign key at a
 * table called `basic_information_ulids`, which does not exist. With
 * `PRAGMA foreign_keys = 1` that makes any insert into these six tables fail.
 *
 * SQLite cannot drop a foreign key in place, so the tables are rebuilt. They are
 * empty in practice — the staff capture path could never write to them.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('edu_progresses');
        Schema::create('edu_progresses', function (Blueprint $table) {
            $table->id();
            $this->staffKey($table);
            $table->json('educational_record');
            $table->timestamps();
        });

        Schema::dropIfExists('promotions');
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $this->staffKey($table);
            $table->json('promotion_record');
            $table->timestamps();
        });

        Schema::dropIfExists('leaves');
        Schema::create('leaves', function (Blueprint $table) {
            $table->id();
            $this->staffKey($table);
            $table->json('leave_record');
            $table->timestamps();
        });

        Schema::dropIfExists('referees');
        Schema::create('referees', function (Blueprint $table) {
            $table->id();
            $this->staffKey($table);
            $table->json('referees_record');
            $table->timestamps();
        });

        Schema::dropIfExists('careers');
        Schema::create('careers', function (Blueprint $table) {
            $table->id();
            $this->staffKey($table);
            $table->date('date_of_employement');          // misspelling kept from the original schema
            $table->string('rank')->nullable();
            $table->string('grade_level')->nullable();    // was integer; grade levels read "GL 09"
            $table->string('ministerial_dept')->nullable();
            $table->string('mode_of_employment')->nullable();
            $table->text('address_of_ministerial_dept')->nullable();
            $table->timestamps();
        });

        Schema::dropIfExists('next_of_kin');
        Schema::create('next_of_kin', function (Blueprint $table) {
            $table->id();
            $this->staffKey($table);
            $table->string('nok_name');
            $table->string('nok_relationship')->nullable();
            $table->string('nok_address')->nullable();
            $table->string('nok_phone_number')->nullable();
            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
    }

    private function staffKey(Blueprint $table): void
    {
        $table->foreignUlid('basic_information_ulid')
            ->constrained(table: 'basic_informations', column: 'ulid')
            ->cascadeOnDelete();
    }

    public function down(): void
    {
        // The previous state was a broken foreign key; there is nothing worth restoring.
    }
};
