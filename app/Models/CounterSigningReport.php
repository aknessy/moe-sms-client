<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The Countersigning Officer's Report section of an officer's appraisal.
 */
class CounterSigningReport extends Model
{
    protected $fillable = [
        'basic_information_ulid', 'comments', 'served_years', 'served_months',
        'post_held', 'grade_level', 'officer_name', 'declared_on', 'signature',
    ];

    protected function casts(): array
    {
        return ['declared_on' => 'date'];
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(BasicInformation::class, 'basic_information_ulid', 'ulid');
    }
}
