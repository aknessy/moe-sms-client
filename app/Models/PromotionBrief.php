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
        'last_moved_on', 'due_on', 'status', 'remarks',
    ];

    protected $casts = [
        'year' => 'integer',
        'grade_level' => 'integer',
        'step' => 'integer',
    ];

    /** Where an entry stands once the exercise has been held. */
    public const STATUSES = [
        'pending' => 'Pending',
        'promoted' => 'Promoted',
        'deferred' => 'Deferred',
    ];

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
     * Put an officer on a year's brief, with their standing as it is now. An
     * officer already on it is left as they were rather than overwritten —
     * the brief records what was put up, and re-recording should not quietly
     * restate it.
     */
    public static function record(int $year, BasicInformation $staff): self
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
}
