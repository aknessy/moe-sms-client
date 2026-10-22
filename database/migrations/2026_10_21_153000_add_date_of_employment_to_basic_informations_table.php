<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * When the officer joined their current employer. Working career records
 * previous employments and feeds the record of service; this is the date the
 * 35 years of service run from, so it belongs on the staff record itself.
 *
 * Nullable because records already on file predate the field. Where it is
 * missing the officer simply falls due at 60.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('basic_informations', function (Blueprint $table) {
            $table->date('date_of_employment')->nullable()->after('date_of_birth');
        });

        // Records already on file have never had their mode worked out. With
        // no date of employment the only route open to them is age 60.
        DB::table('basic_informations')
            ->whereNull('mode_of_retirement')
            ->whereNotNull('date_of_birth')
            ->update(['mode_of_retirement' => 'age']);
    }

    public function down(): void
    {
        Schema::table('basic_informations', function (Blueprint $table) {
            $table->dropColumn('date_of_employment');
        });
    }
};
