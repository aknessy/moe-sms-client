<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Leave extends Model
{
    protected $fillable = [
        'uuid',
        'leave_record',
        'created_at',
        'updated_at'
    ];

    /**
     * Get the staff that owns this leave record
     */
    public function staff(): BelongsTo
    {
        return $this->belongsTo(BasicInformation::class, 'uuid', 'uuid');
    }
}
