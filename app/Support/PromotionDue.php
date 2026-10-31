<?php

namespace App\Support;

use App\Models\BasicInformation;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Who falls due for promotion, and when.
 *
 * An officer becomes due a set number of years after they last moved — their
 * last promotion, or failing that the day their appointment was confirmed, or
 * failing that the day they were employed. Nothing else on a staff record says
 * how long they have stood where they are.
 */
class PromotionDue
{
    /** Years an officer serves on a grade level before falling due. */
    public const YEARS_ON_GRADE = 3;

    /**
     * The day this officer last moved, which is what their next promotion is
     * counted from. Null where the record says nothing about any of it.
     */
    public static function lastMovedOn(BasicInformation $staff): ?Carbon
    {
        $promotions = collect($staff->promotion ?? [])
            ->flatMap(fn ($promotion) => $promotion->promotion_record ?? [])
            ->pluck('effective_from')
            ->filter()
            ->map(fn ($date) => Carbon::parse($date));

        if ($promotions->isNotEmpty()) {
            return $promotions->max();
        }

        // Never promoted: they have stood where they are since they were
        // confirmed, or failing that since they were employed.
        foreach ([$staff->date_of_confirmation, $staff->date_of_employment] as $date) {
            if (filled($date)) {
                return Carbon::parse($date);
            }
        }

        return null;
    }

    /** The day they fall due, or null where there is nothing to count from. */
    public static function dueOn(BasicInformation $staff): ?Carbon
    {
        return self::lastMovedOn($staff)?->copy()->addYears(self::YEARS_ON_GRADE);
    }

    /**
     * An officer already on the top grade level has nowhere to be promoted
     * to, so they never fall due however long they have served.
     */
    public static function canRise(BasicInformation $staff): bool
    {
        return $staff->grade_level !== null
            && $staff->grade_level < BasicInformation::GRADE_LEVELS[1];
    }

    /**
     * Everyone who falls due in a calendar year, longest-standing first — the
     * order a brief is read in, since those are the ones most overdue.
     *
     * Officers who fell due in an earlier year and were never promoted are
     * carried along: they are still waiting, and leaving them off would quietly
     * lose them.
     *
     * @return Collection<array{staff: BasicInformation, last_moved_on: ?Carbon, due_on: Carbon, overdue: bool}>
     */
    public static function forYear(int $year): Collection
    {
        return BasicInformation::query()
            ->inActiveService()
            ->with('promotion')
            ->get()
            ->filter(fn ($staff) => self::canRise($staff))
            ->map(fn ($staff) => [
                'staff' => $staff,
                'last_moved_on' => self::lastMovedOn($staff),
                'due_on' => self::dueOn($staff),
            ])
            ->filter(fn ($row) => $row['due_on'] !== null && $row['due_on']->year <= $year)
            ->map(fn ($row) => $row + ['overdue' => $row['due_on']->year < $year])
            ->sortBy(fn ($row) => $row['due_on']->timestamp)
            ->values();
    }

    /** The years a brief may be drawn for: this one, and a few either side. */
    public static function years(): array
    {
        $thisYear = (int) now()->year;

        return range($thisYear + 2, $thisYear - 3);
    }
}
