<?php

namespace App\Support;

use App\Models\BasicInformation;
use App\Models\Department;
use App\Models\User;

/**
 * Who may write the reporting officer's comments on a report.
 *
 * An activity is the officer's to describe, but the comment beside it is the
 * reporting officer's — which is why one may write it and another may not.
 * The desks that answer for an officer are the ones who do: the Electoral
 * Officer of the local government they are deployed to, the head of the
 * department they belong to at headquarters, and the administrators, who see
 * everything. The officer themselves reads on.
 */
final class ReportingAuthority
{
    /** The role an ordinary staff account holds: reading, not reporting. */
    public const STAFF_ROLE = 'User';

    /**
     * Whether this account may write the comments on this officer's report.
     *
     * The account's own role does not answer the question on its own: a head
     * of department does not head every department, and an Electoral Officer
     * answers for one local government and not another. It is the desk the
     * account sits at beside the desk the officer was posted to that decides.
     */
    public static function mayCommentOn(?User $viewer, ?BasicInformation $staff): bool
    {
        if ($viewer === null || $staff === null) {
            return false;
        }

        if ($viewer->role === ApplicationRouting::ADMIN_ROLE) {
            return true;
        }

        if (self::answersForTheirGovernment($viewer, $staff)
            || self::headsTheirDepartment($viewer, $staff)) {
            return true;
        }

        return false;
    }

    /**
     * An Electoral Officer may write on the report of anyone deployed to the
     * local government they themselves are posted to. The reverse does not
     * hold: their deputy down the road answers for their own LGA, and the
     * officer at headquarters answers to their head of department.
     */
    private static function answersForTheirGovernment(User $viewer, BasicInformation $staff): bool
    {
        if ($viewer->staff?->designation !== ApplicationRouting::ELECTORAL_OFFICER
            || PostingContext::atHeadquarters($staff)) {
            return false;
        }

        $theirs = PostingContext::all()[$viewer->staff->ulid]['lga_id'] ?? null;
        $theirsOnRecord = PostingContext::for($staff)['lga_id'] ?? null;

        return $theirs !== null && $theirs === $theirsOnRecord;
    }

    /** The head of the department the officer belongs to. */
    private static function headsTheirDepartment(User $viewer, BasicInformation $staff): bool
    {
        return $viewer->role !== null
            && $viewer->role === Department::roleFor($staff->department);
    }
}
