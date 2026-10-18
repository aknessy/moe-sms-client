<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Posting extends Model
{
    use HasUlids;

    protected $table = 'postings';

    protected $fillable = ['basic_information_ulid', 'posting_type', 'from_department', 'to_department', 'duty_station', 'effective_date', 'reference_number', 'remarks'];

    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(BasicInformation::class, 'basic_information_ulid', 'ulid');
    }
}