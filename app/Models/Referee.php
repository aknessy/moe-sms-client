<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Referee extends Model
{
    protected $fillable = [
        'uuid',
        'referees_record',
        'created_at',
        'updated_at'
    ];

    /**
     * Referees belong to the associated staff in the basic Information table
     */
    public function staff(): BelongsTo
    {
        return $this->belongsTo(BasicInformation::class, 'uuid', 'uuid');
    }
}
