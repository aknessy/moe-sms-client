<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * An officer is identified by a file number where one exists, and by the
 * establishment/computer number otherwise. Either may stand alone, so both are
 * nullable and the application requires one of the two.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('basic_informations', function (Blueprint $table) {
            $table->string('file_number')->nullable()->after('ulid');
        });

        Schema::table('basic_informations', function (Blueprint $table) {
            // The computer number is no longer mandatory now that a file
            // number can identify the officer on its own.
            $table->string('computer_number')->nullable()->change();
        });

        Schema::table('basic_informations', function (Blueprint $table) {
            // Both identify a single officer. SQLite and MySQL both allow
            // repeated NULLs under a unique index, which is what lets an
            // officer carry only one of the two.
            $table->unique('file_number');
            $table->unique('computer_number');
        });
    }

    public function down(): void
    {
        Schema::table('basic_informations', function (Blueprint $table) {
            $table->dropUnique(['file_number']);
            $table->dropUnique(['computer_number']);
            $table->dropColumn('file_number');
        });
    }
};
