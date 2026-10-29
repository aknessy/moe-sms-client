<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A posting is recorded differently depending on what kind it is.
 *
 * A transfer moves an officer to another state: it records the state they
 * came from, the state and LGA they go to, and the duty station there.
 *
 * A deployment moves them within the state they are already in: it records
 * the station they came from, the LGA they go to, and what they are deployed
 * as.
 *
 * Neither carries a receiving department any more, so to_department stops
 * being required.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('postings', function (Blueprint $table) {
            // Transfer.
            $table->integer('from_state')->nullable()->after('from_department');
            $table->integer('to_state')->nullable()->after('from_state');
            $table->integer('to_lga')->nullable()->after('to_state');

            // Deployment.
            $table->string('present_station')->nullable()->after('to_lga');
            $table->string('deployed_as')->nullable()->after('present_station');

            $table->foreign('from_state')->references('id')->on('states')->nullOnDelete();
            $table->foreign('to_state')->references('id')->on('states')->nullOnDelete();
            $table->foreign('to_lga')->references('id')->on('lgas')->nullOnDelete();

            $table->index('to_state');
        });

        Schema::table('postings', function (Blueprint $table) {
            $table->string('to_department')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('postings', function (Blueprint $table) {
            $table->dropForeign(['from_state']);
            $table->dropForeign(['to_state']);
            $table->dropForeign(['to_lga']);
            $table->dropIndex(['to_state']);
            $table->dropColumn(['from_state', 'to_state', 'to_lga', 'present_station', 'deployed_as']);
        });
    }
};
