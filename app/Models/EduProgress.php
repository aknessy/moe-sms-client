<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EduProgress extends Model
{
    // "progress" is uncountable, so Eloquent would otherwise look for `edu_progress`.
    protected $table = 'edu_progresses';

    protected $fillable = [
        'basic_information_ulid',
        'educational_record',
        'created_at',
        'updated_at'
    ];

    /**
     * Get the staff that owns this educational record
     */
    public function staff(): BelongsTo
    {
        return $this->belongsTo(BasicInformation::class, 'ulid', 'basic_information_ulid');
    }
}
