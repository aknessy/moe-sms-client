<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The Sanctions section of an officer's appraisal, answered only where the
 * officer has no caution or query already on file: whether disciplinary
 * action was taken against them during the period covered, and what it was
 * for where it was.
 */
class Sanction extends Model
{
    protected $fillable = [
        'basic_information_ulid', 'taken', 'details',
    ];

    protected $casts = [
        'taken' => 'boolean',
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(BasicInformation::class, 'basic_information_ulid', 'ulid');
    }
}
