<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Referee extends Model
{
    protected $fillable = [
        'basic_information_ulid',
        'referees_record',
    ];

    protected $casts = [
        'referees_record' => 'array',
    ];

    /**
     * The staff member these referees belong to.
     */
    public function staff(): BelongsTo
    {
        // foreign key lives on this model; ulid is the owner key on the staff record.
        return $this->belongsTo(BasicInformation::class, 'basic_information_ulid', 'ulid');
    }
}
