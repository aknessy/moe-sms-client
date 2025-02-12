<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EduProgress extends Model
{
    protected $fillable = [
        'uuid',
        'educational_record',
        'created_at',
        'updated_at'
    ];

    /**
     * Get the staff that owns this educational record
     */
    public function staff(): BelongsTo
    {
        return $this->belongsTo(BasicInformation::class, 'uuid', 'uuid');
    }
}
