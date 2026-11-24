<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The Suggestions (By Superior Officer) section of an officer's appraisal:
 * whether the reporting officer suggests the officer for a different job in
 * the same grade, and the kind of job and reasons where they do.
 */
class Suggestion extends Model
{
    protected $fillable = [
        'basic_information_ulid', 'different_job_suggested', 'different_job_details',
        'acted_on', 'signature',
    ];

    protected $casts = [
        'different_job_suggested' => 'boolean',
        'acted_on' => 'date',
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(BasicInformation::class, 'basic_information_ulid', 'ulid');
    }
}
