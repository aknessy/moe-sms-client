<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffExit extends Model
{
    use HasUlids;

    protected $table = 'staff_exits';

    protected $fillable = ['basic_information_ulid', 'exit_type', 'effective_date', 'reference_number', 'gratuity_status', 'pension_status', 'remarks'];

    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(BasicInformation::class, 'basic_information_ulid', 'ulid');
    }
}