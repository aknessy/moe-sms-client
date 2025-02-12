<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
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
        return $this->hasOne(Career::class, 'uuid', 'uuid');
    }

    /**
     * Get the record of educational progress associated with a staff
     */
    public function eduProgress(): HasOne
    {
        return $this->hasOne(EduProgress::class, 'uuid', 'uuid');
    }

    /**
     * Get the leave record associated with a staff
     */
    public function leave(): HasOne
    {
        return $this->hasOne(Leave::class, 'uuid', 'uuid');
    }

    /**
     * Get the promotions associated with a staff
     */
    public function promotion(): HasOne
    {
        return $this->hasOne(Promotion::class, 'uuid', 'uuid');
    }

    /**
     * Get a staff referees
     */
    public function referee(): HasOne
    {
        return $this->hasOne(Referee::class, 'uuid', 'uuid');
    }
}
