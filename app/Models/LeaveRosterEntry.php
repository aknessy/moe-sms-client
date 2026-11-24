<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One officer's planned leave on their department's Leave Roster.
 */
class LeaveRosterEntry extends Model
{
    use HasUlids;

    protected $fillable = [
        'basic_information_ulid', 'department', 'leave_type', 'start_date',
        'days', 'resumption_date', 'remarks', 'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'resumption_date' => 'date',
        'days' => 'integer',
    ];

    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(BasicInformation::class, 'basic_information_ulid', 'ulid');
    }

    /** The resumption follows from the start and the days, the way an application's does. */
    protected static function booted(): void
    {
        static::saving(function (self $entry) {
            $resumption = LeaveApplication::resumptionAfter(
                $entry->start_date?->toDateString(), $entry->days);

            if ($resumption !== null) {
                $entry->resumption_date = $resumption->toDateString();
            }
        });
    }
}
