<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The General Remarks section of an officer's appraisal: the reporting
 * officer's own words drawing attention to any particular strength or
 * weakness.
 */
class GeneralRemark extends Model
{
    protected $fillable = [
        'basic_information_ulid', 'remarks',
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(BasicInformation::class, 'basic_information_ulid', 'ulid');
    }
}
