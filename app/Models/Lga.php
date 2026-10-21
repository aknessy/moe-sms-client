<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lga extends Model
{
    /** Reference data: fixed ids, no created_at/updated_at. */
    public $timestamps = false;

    protected $fillable = ['id', 'state_id', 'lga_name'];

    public function state()
    {
        return $this->belongsTo(State::class, 'state_id', 'id');
    }

    public function staff()
    {
        return $this->hasMany(BasicInformation::class, 'lga_of_origin', 'id');
    }
}
