<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecordAmendment extends Model
{
    use HasUlids;

    protected $table = 'record_amendments';

    protected $fillable = ['basic_information_ulid', 'field', 'current_value', 'requested_value', 'reason', 'supporting_document', 'status', 'reviewed_by', 'reviewed_at'];

    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(BasicInformation::class, 'basic_information_ulid', 'ulid');
    }
}