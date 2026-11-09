<?php

namespace App\Support;

use App\Models\BasicInformation;
use App\Models\SelfReportActivity;
use Carbon\Carbon;

/**
 * The years an officer reports on themselves over.
 *
 * Self Reporting is read beside the promotion brief, which is what says how
 * long an officer serves before falling due: two years for a junior officer,
 * three from GL 7. The appraisal supporting that promotion covers the same
 * span — the last two or the last three years.
 *
 * The last of them, not the ones leading up to the day they fell due: an
 * officer overdue since 2011 is promoted on what they have been doing since,
 * and a report on the service of fifteen years ago would answer nobody.
 */
final class SelfReportWindow
{
    /**
     * Where a record says nothing useful, the senior band's three years. It
     * is the longer of the two, so it asks for no less than the officer owes.
     */
    private const FALLBACK = 3;

    private function __construct(
        /** @var int[] The calendar years, earliest first. */
        public readonly array $years,
        /** How many the grade level asks for, before anything is carried along. */
        public readonly int $length,
        public readonly ?Carbon $dueOn,
        /** Why these years and not others, for the section to say. */
        public readonly string $explanation,
    ) {}

    public static function for(?BasicInformation $staff): self
    {
        $length = $staff === null
            ? self::FALLBACK
            : PromotionDue::yearsOnGradeFor($staff) ?? self::FALLBACK;

        // The last of them, ending with the present year.
        $years = range((int) now()->year - ($length - 1), (int) now()->year);

        if ($staff === null) {
            return new self($years, self::FALLBACK, null,
                sprintf('The last %d years.', self::FALLBACK));
        }

        $dueOn = PromotionDue::dueOn($staff);
        $grade = $staff->grade_level_label;

        $explanation = match (true) {
            $grade === null => sprintf(
                'No grade level is on record, so this covers the last %d years, %d to %d.',
                $length, $years[0], end($years)),

            PromotionDue::yearsOnGradeFor($staff) === null => sprintf(
                '%s has nowhere to rise to, so this covers the last %d years, %d to %d.',
                $grade, $length, $years[0], end($years)),

            default => sprintf(
                // A grade level and a band, but no due date: the record says
                // nothing about when this officer last moved.
                '%s moves up after %d years, so this covers the last %d, %d to %d. '
                . 'Nothing on record says when this officer last moved.',
                $grade, $length, $length, $years[0], end($years)),
        };

        // When the promotion it supports falls, which is what says whether
        // the report is being written ahead of time or long after it was
        // owed.
        if ($dueOn !== null) {
            $explanation .= sprintf(' Promotion %s due on %s.',
                $dueOn->isFuture() ? 'falls' : 'fell', $dueOn->format('j F Y'));
        }

        // A year already written in is kept on the form even though it falls
        // outside the window: a report begun in an earlier year must not
        // quietly drop off the end as the window moves on.
        $carried = self::recorded($staff)->diff($years);

        if ($carried->isNotEmpty()) {
            $explanation .= ' ' . $carried->count() . ' earlier '
                . ($carried->count() === 1 ? 'year is' : 'years are')
                . ' kept alongside it, having already been reported on.';
        }

        return new self(
            $carried->merge($years)->unique()->sort()->values()->all(),
            $length,
            $dueOn,
            $explanation,
        );
    }

    /** Is this a year the section asks after for this officer? */
    public function covers(int $year): bool
    {
        return in_array($year, $this->years, true);
    }

    /** The years this officer has already been reported on over. */
    private static function recorded(BasicInformation $staff)
    {
        return SelfReportActivity::where('basic_information_ulid', $staff->ulid)
            ->distinct()->pluck('year')->map(fn ($year) => (int) $year);
    }
}
