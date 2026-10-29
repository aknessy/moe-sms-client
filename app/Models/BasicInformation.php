<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class BasicInformation extends Model
{
    /** @use HasFactory<\Database\Factories\BasicInformationFactory> */
    use HasFactory;

    // "information" is uncountable, so Eloquent would otherwise look for `basic_information`.
    protected $table = 'basic_informations';

    protected $fillable = [
      // `ulid` is the key every related Registry record points at. There is no
      // `uuid` column on this table, despite the docs routes using that word.
      'ulid',
      'file_number',
      'computer_number',
      'surname',
      'firstname',
      'othernames',
      'gender',
      'department',
      'division',
      // Rank and designation are the same thing; the nominal roll calls it rank.
      'designation',
      'grade_level',
      'step',
      'date_of_birth',
      // Joined their current employer; the 35 years of service run from here.
      'date_of_employment',
      'date_of_confirmation',
      'phone_number',
      'email',
      'state_of_origin',
      'lga_of_origin',
      'pfa',
      'rsa',
      'nhis',
      'address',
      'is_deceased',
      'is_retired',
      'mode_of_retirement',
      'created_at',
      'updated_at'
    ];

    /**
     * Every rank an officer may hold, senior first. "Rank" and "designation"
     * name the same thing — the column is `designation`, the nominal roll
     * reads it as rank.
     */
    public const RANKS = [
        'Director',
        'Assistant Director (AD)',
        'Chief Administrative Officer CAO',
        'Assistant Chief Administrative Officer (ACAO)',
        'Administrative Officer I (AO I)',
        'Administrative Officer II (AO II)',
        'Senior Executive Officer (SEO)',
        'Higher Executive Officer (HEO)',
        'Executive Officer (EO)',
        'Assistant Executive Officer (AEO)',
        'Chief Clerical Officer (CCO)',
        'Gardener',
        'Security Guard',
    ];

    public const GENDERS = ['Male', 'Female'];

    /** The rank an officer holds. The column carries the other name for it. */
    public function getRankAttribute(): ?string
    {
        return $this->designation;
    }

    /** The two routes out of the service. */
    public const RETIREMENT_MODES = [
        'years-of-service' => 'Years of Service',
        'age' => 'Retirement by Age',
    ];

    /** Years of service that carry an officer to retirement. */
    public const SERVICE_YEARS = 35;

    /** The age at which an officer falls due regardless of service. */
    public const RETIREMENT_AGE = 60;

    public function getModeOfRetirementLabelAttribute(): ?string
    {
        return self::RETIREMENT_MODES[$this->mode_of_retirement] ?? null;
    }

    /**
     * The date each route out of the service would fall due on. Either may be
     * null when the date it counts from is not on file.
     *
     * @return array{'years-of-service': ?Carbon, age: ?Carbon}
     */
    public function retirementDueDates(): array
    {
        return [
            'years-of-service' => filled($this->date_of_employment)
                ? Carbon::parse($this->date_of_employment)->startOfDay()->addYears(self::SERVICE_YEARS)
                : null,
            'age' => filled($this->date_of_birth)
                ? Carbon::parse($this->date_of_birth)->startOfDay()->addYears(self::RETIREMENT_AGE)
                : null,
        ];
    }

    /**
     * An officer leaves at 60 years of age or 35 years of service, whichever
     * comes first — so the mode is not a choice, it follows from the two
     * dates. Null when neither is on file.
     */
    public function determineRetirementMode(): ?string
    {
        $due = array_filter($this->retirementDueDates());

        if ($due === []) {
            return null;
        }

        // Whichever falls first is the one that carries them out.
        return array_search(min($due), $due, true) ?: null;
    }

    /**
     * When this officer falls due to leave, by whichever route reaches them
     * first.
     */
    public function retiresOn(): ?Carbon
    {
        $due = array_filter($this->retirementDueDates());

        return $due === [] ? null : min($due);
    }

    /**
     * The mode follows from the dates, so it is kept in step with them rather
     * than entered by hand: set as the record is created, and recalculated if
     * either date is later corrected.
     */
    protected static function booted(): void
    {
        static::saving(function (self $staff) {
            $staff->mode_of_retirement = $staff->determineRetirementMode();
        });
    }

    public function scopeDeceased($query)
    {
        return $query->where('is_deceased', 1);
    }

    public function scopeRetired($query)
    {
        return $query->where('is_retired', 1);
    }

    /**
     * The number an officer is known by: the file number where there is one,
     * and the establishment/computer number otherwise. Read this rather than
     * either column so the fallback is applied consistently.
     */
    public function getStaffNumberAttribute(): ?string
    {
        return $this->file_number ?: $this->computer_number;
    }

    /**
     * Match against whichever number the officer carries.
     */
    public function scopeWhereStaffNumber($query, string $number)
    {
        return $query->where(fn ($q) => $q->where('file_number', $number)
                                          ->orWhere('computer_number', $number));
    }

    /**
     * `state_of_origin` holds the states.id this officer hails from.
     */
    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class, 'state_of_origin', 'id');
    }

    /**
     * `lga_of_origin` holds the lgas.id this officer hails from.
     */
    public function lga(): BelongsTo
    {
        return $this->belongsTo(Lga::class, 'lga_of_origin', 'id');
    }

    /**
     * Get the careers associated with a staff
     */
    public function career(): HasOne
    {
        return $this->hasOne(Career::class, 'basic_information_ulid', 'ulid');
    }

    /**
     * Get the record of educational progress associated with a staff
     */
    public function eduProgress(): HasMany
    {
        return $this->hasMany(EduProgress::class, 'basic_information_ulid', 'ulid');
    }

    /**
     * Get the leave record associated with a staff
     */
    public function leave(): HasOne
    {
        return $this->hasOne(Leave::class, 'basic_information_ulid', 'ulid');
    }

    /**
     * Get the promotions associated with a staff
     */
    public function promotion(): HasMany
    {
        return $this->hasMany(Promotion::class, 'basic_information_ulid', 'ulid');
    }

    /**
     * Get a staff referees
     */
    public function referee(): HasMany
    {
        return $this->hasMany(Referee::class, 'basic_information_ulid', 'ulid');
    }
}
