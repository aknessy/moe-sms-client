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

    /** Exit types that set a standing flag on the staff record. */
    private const FLAGS = [
        'death' => 'is_deceased',
        'retirement' => 'is_retired',
    ];

    /**
     * Recording an exit is what makes an officer deceased or retired, so the
     * flags are kept here rather than in whichever controller or seeder
     * happened to write the exit.
     */
    protected static function booted(): void
    {
        static::saved(function (self $exit) {
            $flag = self::FLAGS[$exit->exit_type] ?? null;

            if ($flag !== null) {
                BasicInformation::where('ulid', $exit->basic_information_ulid)->update([$flag => 1]);
            }
        });
    }

    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(BasicInformation::class, 'basic_information_ulid', 'ulid');
    }
}