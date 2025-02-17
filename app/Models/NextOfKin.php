<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NextOfKin extends Model
{
    protected $fillable = [
        'basic_information_ulid',
        'nok_name',
        'nok_relationship',
        'nok_address',
        'nok_phone_number',
        'created_at',
        'updated_at'
    ];

    /**
     * This promotion record belongs to one staff with the associated Basic Information
     */
    public function staff(): HasMany
    {
        return $this->hasMany(BasicInformation::class, 'ulid', 'basic_information_ulid');
    }
}
