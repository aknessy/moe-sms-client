<?php

namespace App\Support;

use App\Models\BasicInformation;
use App\Models\GapDelegate;
use App\Models\Posting;
use App\Models\User;

/**
 * The department HOD GAP answers for, and who is in it — shared by what the
 * head does and what the officers they designate do alongside them.
 */
final class GapDesk
{
    /**
     * The department this account works the desk of: the head's own, or the
     * head's who designated them.
     */
    public static function department(?User $user): ?string
    {
        if ($user === null) {
            return null;
        }

        if (ReportingAuthority::isHodGap($user)) {
            return $user->staff?->department;
        }

        $delegate = GapDelegate::where('basic_information_ulid', $user->basic_information_ulid)->first();

        return $delegate === null
            ? null
            : BasicInformation::where('ulid', $delegate->designated_by)->value('department');
    }

    /** Whether the officer belongs to the department, or was last posted to it. */
    public static function includes(BasicInformation $staff, ?string $department): bool
    {
        if (blank($department)) {
            return false;
        }

        if ($staff->department === $department) {
            return true;
        }

        return Posting::where('basic_information_ulid', $staff->ulid)
            ->whereNotNull('to_department')
            ->orderBy('effective_date')->orderBy('id')
            ->get()->last()?->to_department === $department;
    }
}
