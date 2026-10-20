<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Career extends Model
{
    protected $fillable = [
        'basic_information_ulid',
        'date_of_employement',
        'rank',
        'grade_level',
        'ministerial_dept',
        'mode_of_employment',
        'address_of_ministerial_dept',
    ];

    /**
     * The staff member this working career record belongs to.
     */
    public function staff(): BelongsTo
    {
        // foreign key lives on this model; ulid is the owner key on the staff record.
        return $this->belongsTo(BasicInformation::class, 'basic_information_ulid', 'ulid');
    }
}
