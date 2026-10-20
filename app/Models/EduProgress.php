<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EduProgress extends Model
{
    protected $table = 'edu_progresses';

    protected $fillable = [
        'basic_information_ulid',
        'educational_record',
    ];

    protected $casts = [
        'educational_record' => 'array',
    ];

    /**
     * The staff member this educational history belongs to.
     */
    public function staff(): BelongsTo
    {
        // foreign key lives on this model; ulid is the owner key on the staff record.
        return $this->belongsTo(BasicInformation::class, 'basic_information_ulid', 'ulid');
    }
}
