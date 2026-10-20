<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NextOfKin extends Model
{
    protected $table = 'next_of_kin';

    protected $fillable = [
        'basic_information_ulid',
        'nok_name',
        'nok_relationship',
        'nok_address',
        'nok_phone_number',
    ];

    /**
     * The staff member this next of kin belongs to.
     */
    public function staff(): BelongsTo
    {
        // foreign key lives on this model; ulid is the owner key on the staff record.
        return $this->belongsTo(BasicInformation::class, 'basic_information_ulid', 'ulid');
    }
}
