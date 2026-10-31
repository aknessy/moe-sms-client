<?php

use App\Models\LeaveApplication;
use App\Models\Notification;
use Illuminate\Database\Migrations\Migration;

/**
 * Applications made before anyone was told about them.
 *
 * The notice goes out when an application is created, which does nothing for
 * the ones already on file — and those are exactly the ones the feature exists
 * for: still pending, with nobody aware they are waiting.
 *
 * Only the pending ones. An application already decided needs no one's
 * attention, and a notice about it would be a lie.
 */
return new class extends Migration
{
    public function up(): void
    {
        $waiting = LeaveApplication::with('staff')
            ->where('status', 'pending')
            ->get();

        foreach ($waiting as $application) {
            // Nothing to raise twice: an application someone was already told
            // about is left alone.
            if (Notification::where('url', route('hr-leave.show', $application->ulid))->exists()) {
                continue;
            }

            $application->notifyReviewers();

            // Dated to the application, not to this migration — the notice is
            // as old as the thing it is about.
            Notification::where('url', route('hr-leave.show', $application->ulid))
                ->update(['created_at' => $application->created_at,
                          'updated_at' => $application->created_at]);
        }
    }

    public function down(): void
    {
        // Nothing to undo: a notice read is a notice dealt with, and there is
        // no telling afterwards which were raised here.
    }
};
