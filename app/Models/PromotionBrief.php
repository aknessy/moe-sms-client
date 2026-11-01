<?php

namespace App\Models;

use App\Support\PromotionDue;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An officer listed on a year's promotion brief, with what they stood at when
 * the list was drawn.
 */
class PromotionBrief extends Model
{
    use HasUlids;

    protected $table = 'promotion_briefs';

    protected $fillable = [
        'year', 'basic_information_ulid',
        'designation', 'grade_level', 'step',
        'last_moved_on', 'due_on', 'status', 'remarks', 'on_brief_at',
    ];

    protected $casts = [
        'year' => 'integer',
        'grade_level' => 'integer',
        'step' => 'integer',
        'on_brief_at' => 'datetime',
    ];

    /** Where an entry stands once the exercise has been held. */
    public const STATUSES = [
        'pending' => 'Pending',
        'promoted' => 'Promoted',
        'deferred' => 'Deferred',
    ];

    /**
     * Taken off the list. Kept rather than deleted, so that reopening the
     * year does not quietly stage them all over again.
     */
    public const EXCLUDED = 'excluded';

    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(BasicInformation::class, 'basic_information_ulid', 'ulid');
    }

    public function scopeForYear($query, int $year)
    {
        return $query->where('year', $year);
    }

    /** Longest-standing first, which is the order a brief is read in. */
    public function scopeMostOverdueFirst($query)
    {
        return $query->orderByRaw('due_on IS NULL')->orderBy('due_on')->orderBy('id');
    }

    /** Worked up but not yet put up. */
    public function scopeStaged($query)
    {
        return $query->whereNull('on_brief_at')->where('status', '!=', self::EXCLUDED);
    }

    /** On the brief itself. */
    public function scopeOnBrief($query)
    {
        return $query->whereNotNull('on_brief_at')->where('status', '!=', self::EXCLUDED);
    }

    public function getIsOnBriefAttribute(): bool
    {
        return $this->on_brief_at !== null;
    }

    public function getGradeStepAttribute(): string
    {
        return trim(($this->grade_level === null
                ? '—'
                : 'GL ' . str_pad((string) $this->grade_level, 2, '0', STR_PAD_LEFT))
            . ($this->step === null ? '' : ' / Step ' . $this->step));
    }

    /** Due in a year before the one the brief covers: still waiting. */
    public function getIsOverdueAttribute(): bool
    {
        return filled($this->due_on) && (int) substr($this->due_on, 0, 4) < $this->year;
    }

    /**
     * Put an officer on a year's list, with their standing as it is now.
     *
     * Staged rather than on the brief: the list is looked over before it is
     * put up. An officer already there is left as they were — including one
     * taken off, who stays off, so that reopening the year does not stage
     * them all over again.
     */
    public static function stage(int $year, BasicInformation $staff): self
    {
        return static::firstOrCreate(
            ['year' => $year, 'basic_information_ulid' => $staff->ulid],
            [
                'designation' => $staff->designation,
                'grade_level' => $staff->grade_level,
                'step' => $staff->step,
                'last_moved_on' => PromotionDue::lastMovedOn($staff)?->toDateString(),
                'due_on' => PromotionDue::dueOn($staff)?->toDateString(),
                'status' => 'pending',
            ],
        );
    }

    /**
     * Stage everyone the records say is due in a year. Idempotent: an officer
     * already staged, already on the brief or already taken off is left as
     * they are, so this may be run as often as the year is opened.
     */
    public static function stageEveryoneDue(int $year): int
    {
        $staged = 0;

        foreach (PromotionDue::forYear($year) as $row) {
            if (static::forYear($year)
                ->where('basic_information_ulid', $row['staff']->ulid)->exists()) {
                continue;
            }

            static::stage($year, $row['staff']);
            $staged++;
        }

        return $staged;
    }

    /** Put the worked-up list up as the brief. */
    public function putOnBrief(): void
    {
        if ($this->on_brief_at === null) {
            $this->update(['on_brief_at' => now()]);
        }
    }

    /**
     * Take an officer off. The row stays, marked, so reopening the year does
     * not stage them again — the decision to leave them off is itself a
     * thing that was decided.
     */
    public function takeOff(): void
    {
        $this->update(['status' => self::EXCLUDED, 'on_brief_at' => null]);
    }
}
