<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Promotion extends Model
{
    protected $fillable = [
        'uuid',
        'promotion_record',
        'created_at',
        'updated_at'
    ];

    /**
     * This promotion record belongs to one staff with the associated Basic Information
     */
    public function staff(): BelongsTo
    {
        return $this->belongsTo(BasicInformation::class, 'uuid', 'uuid');
    }
}
