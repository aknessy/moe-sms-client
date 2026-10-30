<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An officer's name, down for a training. Says they would like to attend, not
 * that they have been picked.
 */
class TrainingInterest extends Model
{
    use HasUlids;

    protected $fillable = ['training_id', 'basic_information_ulid'];

    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    public function training(): BelongsTo
    {
        return $this->belongsTo(Training::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(BasicInformation::class, 'basic_information_ulid', 'ulid');
    }
}
