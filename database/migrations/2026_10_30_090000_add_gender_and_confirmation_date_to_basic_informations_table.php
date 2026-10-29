<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Two things the staff record did not carry: the officer's gender, and the
 * date their appointment was confirmed.
 *
 * Both are nullable. Officers already on the roll were filed before either
 * was asked for, and nothing can be invented for them.
 *
 * The confirmations table records a confirmation exercise, with its own
 * status and reference. This column is the plain fact kept on the staff
 * record; the exercise writes its date here when one is confirmed, so the
 * two cannot drift apart.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('basic_informations', function (Blueprint $table) {
            $table->string('gender')->nullable()->after('othernames');
            $table->date('date_of_confirmation')->nullable()->after('date_of_employment');
        });
    }

    public function down(): void
    {
        Schema::table('basic_informations', function (Blueprint $table) {
            $table->dropColumn(['gender', 'date_of_confirmation']);
        });
    }
};
