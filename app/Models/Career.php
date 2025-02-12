<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Career extends Model
{
    /**
     * Get the staff that owns this career record
     */
    public function staff(): BelongsTo
    {
        return $this->belongsTo(BasicInformation::class, 'uuid', 'uuid');
    }
}
