<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * An officer applying to go on leave.
 *
 * Distinct from the `leaves` table, which holds the leave history captured
 * against a staff record when it is first opened. That is a record of what
 * has already happened; this is a request waiting to be acted on.
 *
 * The resumption date is stored rather than worked out on the fly so the roster
 * can be queried and sorted by it. It is set from the effective date and the
 * number of days as the application is saved, so the two cannot contradict.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_applications', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->string('basic_information_ulid')->index();

            $table->string('leave_type');
            $table->date('effective_date');
            $table->unsignedSmallInteger('days');
            $table->date('resumption_date');

            $table->text('reason')->nullable();
            $table->string('status')->default('pending');   // pending | approved | declined
            $table->text('remarks')->nullable();

            $table->timestamps();

            // The list is newest first.
            $table->index('created_at');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_applications');
    }
};
