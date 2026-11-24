<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The Staff Declaration section of an officer's appraisal: the officer's own
 * certification that they have seen the report and discussed it with the
 * reporting officer, with whatever comments they have to make on it.
 */
class StaffDeclaration extends Model
{
    protected $fillable = [
        'basic_information_ulid', 'comments', 'declared_on', 'signature',
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
