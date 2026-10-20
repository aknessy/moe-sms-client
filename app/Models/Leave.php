<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Leave extends Model
{
    protected $fillable = [
        'basic_information_ulid',
        'leave_record',
    ];

    protected $casts = [
        'leave_record' => 'array',
    ];

    /**
     * The staff member this leave history belongs to.
     */
    public function staff(): BelongsTo
    {
        // foreign key lives on this model; ulid is the owner key on the staff record.
        return $this->belongsTo(BasicInformation::class, 'basic_information_ulid', 'ulid');
    }
}
