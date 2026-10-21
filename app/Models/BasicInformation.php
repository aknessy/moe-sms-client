<?php

namespace App\Models;

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
      'department',
      'division',
      'designation',
      'grade_level',
      'step',
      'date_of_birth',
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
      'created_at',
      'updated_at'
    ];

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
