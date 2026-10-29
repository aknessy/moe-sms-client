<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The working career section is optional: this post may be the officer's
 * first job, in which case there is no prior appointment to record at all.
 *
 * Two things follow. Every column has to be nullable, since an officer may
 * remember an employer but not the date they left it. And skipping the
 * section has to leave something behind — the wizard reads a career row as
 * proof the step was dealt with, so without one the record could never be
 * finished. That row is marked as saying there was no previous employment,
 * rather than being a blank nobody can tell from an oversight.
 */
return new class extends Migration
{
    private const OPTIONAL = [
        'rank', 'ministerial_dept', 'mode_of_employment', 'address_of_ministerial_dept',
    ];

    public function up(): void
    {
        Schema::table('careers', function (Blueprint $table) {
            foreach (self::OPTIONAL as $column) {
                $table->string($column)->nullable()->change();
            }

            $table->integer('grade_level')->nullable()->change();

            $table->boolean('no_previous_employment')->default(false)->after('basic_information_ulid');
        });
    }

    public function down(): void
    {
        Schema::table('careers', function (Blueprint $table) {
            $table->dropColumn('no_previous_employment');
        });
    }
};
