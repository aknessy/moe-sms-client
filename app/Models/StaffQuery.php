<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffQuery extends Model
{
    use HasUlids;

    protected $table = 'staff_queries';

    protected $fillable = ['basic_information_ulid', 'query_date', 'reference_number', 'offence', 'details', 'response_date', 'response', 'sanction', 'status', 'decision', 'decision_date', 'decision_by', 'decision_remarks'];

    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(BasicInformation::class, 'basic_information_ulid', 'ulid');
    }
}