<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class BasicInformation extends Model
{
    protected $fillable = [
      'uuid',
      'computer_number',
      'surname',
      'firstname',
      'othernames',
      'date_of_birth',
      'phone_number',
      'email',
      'state_of_origin',
      'lga_of_origin',
      'pfa',
      'rsa',
      'nhis',
      'address',
      'created_at',
      'updated_at'
    ];

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
