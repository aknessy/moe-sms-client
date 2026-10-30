<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An officer's request to leave the service early. It changes nothing about
 * their standing on its own — the exit is raised through Pension & Gratuity
 * once someone has approved it.
 */
class VoluntaryRetirementRequest extends Model
{
    use HasUlids;

    protected $table = 'voluntary_retirement_requests';

    protected $fillable = [
        'basic_information_ulid', 'intended_date', 'reason', 'status', 'remarks',
    ];

    /** Where a request stands. */
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

    public function scopeNewestFirst($query)
    {
        return $query->latest('created_at')->latest('id');
    }

    /** A request still being considered; an officer may only have one. */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
