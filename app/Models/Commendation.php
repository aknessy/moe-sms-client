<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The Commendations section of an officer's appraisal: whether they received
 * a written commendation during the year for outstanding performance, and
 * what it was for where they did.
 */
class Commendation extends Model
{
    protected $fillable = [
        'basic_information_ulid', 'received', 'details',
    ];

    protected $casts = [
        'received' => 'boolean',
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(BasicInformation::class, 'basic_information_ulid', 'ulid');
    }
}
