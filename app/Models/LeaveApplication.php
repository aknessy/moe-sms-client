<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveApplication extends Model
{
    use HasUlids;

    protected $table = 'leave_applications';

    protected $fillable = [
        'basic_information_ulid', 'leave_type',
        'effective_date', 'days', 'resumption_date',
        'reason', 'status', 'remarks',
    ];

    protected $casts = ['days' => 'integer'];

    /** The kinds of leave an officer may apply for. */
    public const TYPES = [
        'Annual Leave' => 'Annual Leave',
        'Casual Leave' => 'Casual Leave',
        'Sick Leave' => 'Sick Leave',
        'Study Leave' => 'Study Leave',
        'Maternity Leave' => 'Maternity Leave',
        'Retirement Leave' => 'Retirement Leave',
    ];

    /** Where an application stands. */
    public const STATUSES = [
        'pending' => 'Pending',
        'approved' => 'Approved',
        'declined' => 'Declined',
    ];

    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(BasicInformation::class, 'basic_information_ulid', 'ulid');
    }

    /** Newest first, which is how the roster is read. */
    public function scopeNewestFirst($query)
    {
        return $query->latest('created_at')->latest('id');
    }

    /**
     * The day an officer is back at their desk: the leave runs from the
     * effective date for the number of days applied for, so they resume on
     * the day after the last of them.
     */
    public static function resumptionAfter(?string $effectiveDate, ?int $days): ?Carbon
    {
        return filled($effectiveDate) && $days !== null
            ? Carbon::parse($effectiveDate)->startOfDay()->addDays($days)
            : null;
    }

    /**
     * Kept in step with the two fields it follows rather than entered by hand,
     * so a corrected date or day count carries through.
     */
    protected static function booted(): void
    {
        static::saving(function (self $application) {
            $resumption = self::resumptionAfter($application->effective_date, $application->days);

            if ($resumption !== null) {
                $application->resumption_date = $resumption->toDateString();
            }
        });
    }
}
