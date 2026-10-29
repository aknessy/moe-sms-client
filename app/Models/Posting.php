<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Posting extends Model
{
    use HasUlids;

    protected $table = 'postings';

    protected $fillable = [
        'basic_information_ulid', 'posting_type',
        'from_department', 'to_department',
        // Transfer: out of one state, into another.
        'from_state', 'to_state', 'to_lga',
        // Deployment: within the state they are already in.
        'present_station', 'deployed_as',
        'duty_station', 'effective_date', 'reference_number', 'remarks',
    ];

    /** The two kinds of posting. Secondment is no longer among them. */
    public const TYPES = [
        'transfer' => 'Transfer',
        'deployment' => 'Deployment',
    ];

    /** What an officer may be deployed as. */
    public const DEPLOYMENT_ROLES = [
        'Electoral Officer (EO)' => 'Electoral Officer (EO)',
        'Assistant Electoral Officer (AEO) RAC' => 'Assistant Electoral Officer (AEO) RAC',
        'Assistant Electoral Officer (AEO) Admin' => 'Assistant Electoral Officer (AEO) Admin',
        'Assistant Electoral Officer (AEO) Operations' => 'Assistant Electoral Officer (AEO) Operations',
        'Staff' => 'Staff',
    ];

    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(BasicInformation::class, 'basic_information_ulid', 'ulid');
    }

    public function fromStateRecord(): BelongsTo
    {
        return $this->belongsTo(State::class, 'from_state');
    }

    public function toStateRecord(): BelongsTo
    {
        return $this->belongsTo(State::class, 'to_state');
    }

    public function toLgaRecord(): BelongsTo
    {
        return $this->belongsTo(Lga::class, 'to_lga');
    }

    /** Where this posting sent them, as it reads on screen. */
    public function getDestinationAttribute(): string
    {
        if ($this->posting_type === 'transfer') {
            return collect([$this->toStateRecord?->name, $this->toLgaRecord?->lga_name])
                ->filter()->implode(' / ') ?: ($this->to_department ?: '—');
        }

        return collect([$this->toLgaRecord?->lga_name, $this->deployed_as])
            ->filter()->implode(' — ') ?: ($this->to_department ?: '—');
    }

    /** Where it moved them from. */
    public function getOriginAttribute(): string
    {
        return $this->posting_type === 'transfer'
            ? ($this->fromStateRecord?->name ?: ($this->from_department ?: '—'))
            : ($this->present_station ?: ($this->from_department ?: '—'));
    }
}
