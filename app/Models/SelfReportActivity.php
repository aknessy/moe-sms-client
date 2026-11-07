<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

/**
 * One thing an officer took part in, in one quarter, with what the reporting
 * officer made of it.
 *
 * The Self Reporting section runs over three years of three quarters each, so
 * an activity is only ever read beside the quarter it was performed in.
 */
class SelfReportActivity extends Model
{
    protected $table = 'self_report_activities';

    protected $fillable = [
        'basic_information_ulid', 'year', 'quarter', 'position',
        'activity', 'comment',
    ];

    protected $casts = [
        'year' => 'integer',
        'quarter' => 'integer',
        'position' => 'integer',
    ];

    /** The quarters an appraisal asks after. Not the fourth: it does not. */
    public const QUARTERS = [1 => '1st Quarter', 2 => '2nd Quarter', 3 => '3rd Quarter'];

    /** How many years of them a report covers. */
    public const YEARS = 3;

    /** How few activities a quarter can be answered with. */
    public const LEAST_ACTIVITIES = 4;

    public function staff(): BelongsTo
    {
        return $this->belongsTo(BasicInformation::class, 'basic_information_ulid', 'ulid');
    }

    /**
     * The years the section asks after: the three ending with this one.
     *
     * Anything already recorded outside them is carried along, so a report
     * opened in a later year still shows what was written in an earlier one
     * rather than quietly dropping it off the end.
     */
    public static function yearsFor(?BasicInformation $staff): array
    {
        $recent = range(now()->year - (self::YEARS - 1), now()->year);

        if ($staff === null) {
            return $recent;
        }

        return collect($recent)
            ->merge(static::where('basic_information_ulid', $staff->ulid)->pluck('year'))
            ->unique()->sort()->values()->all();
    }

    /**
     * An officer's activities, keyed by year and then quarter, so a quarter's
     * block of the form can read its own off without searching.
     */
    public static function forStaff(?BasicInformation $staff): Collection
    {
        if ($staff === null) {
            return collect();
        }

        return static::where('basic_information_ulid', $staff->ulid)
            ->orderBy('year')->orderBy('quarter')->orderBy('position')->orderBy('id')
            ->get()
            ->groupBy(['year', 'quarter']);
    }

    /** What is held for one quarter, or an empty list where nothing is. */
    public static function inQuarter(Collection $byQuarter, int $year, int $quarter): Collection
    {
        return collect($byQuarter[$year][$quarter] ?? []);
    }
}
