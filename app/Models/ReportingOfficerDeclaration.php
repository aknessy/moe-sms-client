<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The Declaration by Reporting Officer section of an officer's appraisal: the
 * reporting officer's statement that they assessed the officer, with the
 * post and grade they hold, the date, and their signature.
 */
class ReportingOfficerDeclaration extends Model
{
    protected $fillable = [
        'basic_information_ulid', 'officer_name', 'post_held',
        'grade_level', 'declared_on', 'signature',
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
