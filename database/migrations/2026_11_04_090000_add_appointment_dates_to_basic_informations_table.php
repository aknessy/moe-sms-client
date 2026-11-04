<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Two dates an appraisal asks for that the record had nowhere to hold.
 *
 * The day an officer took their present appointment can be guessed at from
 * their last promotion, and the Career History section offers that guess —
 * but a guess is not a record, so the column stays empty until someone says
 * it is right. Acting appointments are not derivable from anything: an
 * officer either holds one or does not.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('basic_informations', function (Blueprint $table) {
            $table->date('date_of_present_appointment')->nullable()->after('date_of_confirmation');
            // Null for the many officers holding no acting appointment.
            $table->date('date_of_acting_appointment')->nullable()->after('date_of_present_appointment');
        });
    }

    public function down(): void
    {
        Schema::table('basic_informations', function (Blueprint $table) {
            $table->dropColumn(['date_of_present_appointment', 'date_of_acting_appointment']);
        });
    }
};
