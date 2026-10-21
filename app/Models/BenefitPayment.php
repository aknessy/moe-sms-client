<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BenefitPayment extends Model
{
    use HasUlids;

    protected $table = 'benefit_payments';

    protected $fillable = [
        'basic_information_ulid',
        'benefit_type',
        'amount',
        'payment_date',
        'reference_number',
        'paid_to',
        'receipt_path',
        'receipt_name',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'payment_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    /** What is paid to an officer on retirement. */
    public const RETIREMENT_BENEFITS = [
        'gratuity' => 'Gratuity',
        'pension' => 'Pension',
    ];

    /** What is paid to the next of kin of an officer who died in service. */
    public const DEATH_BENEFITS = [
        'condolence-pause' => 'Condolence Pause',
        'hazard' => 'Hazard',
        'group-life' => 'Group Life',
    ];

    public static function allBenefits(): array
    {
        return self::RETIREMENT_BENEFITS + self::DEATH_BENEFITS;
    }

    public function getBenefitLabelAttribute(): string
    {
        return self::allBenefits()[$this->benefit_type] ?? $this->benefit_type;
    }

    /** Whether a receipt has been filed against this payment. */
    public function getHasReceiptAttribute(): bool
    {
        return filled($this->receipt_path);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(BasicInformation::class, 'basic_information_ulid', 'ulid');
    }

    public function scopeOfType($query, array $types)
    {
        return $query->whereIn('benefit_type', $types);
    }

    public function uniqueIds(): array
    {
        return ['ulid'];
    }
}
