<?php

namespace App\Support;

use App\Models\BasicInformation;
use App\Models\Department;
use App\Models\GapDelegate;
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

    /** The head of General Administration & Planning: acts on every officer's report. */
    public const HOD_GAP_ROLE = 'HOD GAP';

    public static function isHodGap(?User $viewer): bool
    {
        return $viewer?->role === self::HOD_GAP_ROLE;
    }

    /**
     * An officer HOD GAP has designated a Registry Officer. They work as HOD
     * GAP does, but sign and approve nothing on the head's behalf.
     */
    public static function isGapDelegate(?User $viewer): bool
    {
        return GapDelegate::holds($viewer);
    }

    /** The head of the department on their own staff record. */
    public static function headsOwnDepartment(?User $viewer): bool
    {
        $role = $viewer?->role;

        return $role !== null && $role === Department::roleFor($viewer->staff?->department);
    }

    /** HOD GAP or their designated officer: the two who share the GAP desk. */
    public static function atGapDesk(?User $viewer): bool
    {
        return self::isHodGap($viewer) || self::isGapDelegate($viewer);
    }

    /**
     * Whether this account may act on the Suggestions (By Superior Officer)
     * section: the desks that report on the officer, and HOD GAP, who acts on
     * every officer's report by signing it and saying the day they did.
     */
    public static function mayActOnSuggestions(?User $viewer, ?BasicInformation $staff): bool
    {
        return self::mayCommentOn($viewer, $staff)
            || ($viewer !== null && $staff !== null && self::isHodGap($viewer));
    }

    /**
     * Whether this account is only reading this report. HOD GAP is a staff
     * member first: their own report is theirs to fill, but on anyone else's
     * they may act on the Suggestions and nothing more.
     */
    public static function readsOnly(?User $viewer, ?BasicInformation $staff): bool
    {
        return self::atGapDesk($viewer)
            && $staff !== null
            && $viewer->basic_information_ulid !== $staff->ulid;
    }

    /** The desks above the reporting officers: they countersign, rather than report. */
    public const COUNTERSIGNING_ROLES = [
        ApplicationRouting::ADMIN_ROLE,
        ApplicationRouting::SECRETARY_ROLE,
        'Commissioner',
    ];

    /**
     * Whether this account may write the Countersigning Officer's Report.
     *
     * The countersigning officer is normally the immediate superior of the
     * reporting officer, so it is the desks above the department heads and
     * Electoral Officers — not those heads themselves — that answer here.
     */
    public static function mayCounterSign(?User $viewer, ?BasicInformation $staff): bool
    {
        return $viewer !== null && $staff !== null
            && in_array($viewer->role, self::COUNTERSIGNING_ROLES, true);
    }

    /**
     * Whether this account may make the Staff Declaration on this officer's
     * report.
     *
     * The declaration is the officer's own certification that they have
     * seen the report, so it is theirs to make and nobody else's — not even
     * the reporting officer who answered for the sections before it. An
     * administrator may still make it on their behalf, the way one may act
     * for an officer elsewhere in the system. Everyone else, including the
     * Electoral Officer or head of department who may comment on the report,
     * only reads what is on record.
     */
    public static function mayDeclare(?User $viewer, ?BasicInformation $staff): bool
    {
        if ($viewer === null || $staff === null) {
            return false;
        }

        if ($viewer->role === ApplicationRouting::ADMIN_ROLE) {
            return true;
        }

        // HOD GAP is a staff member first, so their own report is theirs to
        // declare on too.
        return (in_array($viewer->role, [self::STAFF_ROLE, self::HOD_GAP_ROLE], true)
                || self::isGapDelegate($viewer))
            && $viewer->basic_information_ulid === $staff->ulid;
    }
}
