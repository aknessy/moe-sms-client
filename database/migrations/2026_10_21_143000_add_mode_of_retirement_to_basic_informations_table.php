<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Which of the two routes out of the service an officer leaves by: 35 years
 * of service, or age 60. It decides the date they fall due, so it belongs on
 * the staff record rather than only on the exit that eventually records it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('basic_informations', function (Blueprint $table) {
            // years-of-service | age
            $table->string('mode_of_retirement')->nullable()->after('is_retired');
        });
    }

    public function down(): void
    {
        Schema::table('basic_informations', function (Blueprint $table) {
            $table->dropColumn('mode_of_retirement');
        });
    }
};
