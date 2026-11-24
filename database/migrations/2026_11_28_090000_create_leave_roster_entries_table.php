<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A department's Leave Roster: when each officer is planned to be away. Not an
 * application — nobody decides on it — so it carries no status, only the
 * dates and who put them there.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_roster_entries', function (Blueprint $table) {
            $table->id();
            $table->char('ulid', 26)->unique();
            $table->string('basic_information_ulid', 26)->index();
            // The department the roster is for, as it stood when this was put on.
            $table->string('department');
            $table->string('leave_type');
            $table->date('start_date');
            $table->unsignedSmallInteger('days');
            $table->date('resumption_date');
            $table->string('remarks', 255)->nullable();
            $table->string('created_by', 26);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_roster_entries');
    }
};
