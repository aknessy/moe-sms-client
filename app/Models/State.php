<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class State extends Model
{
    /** Reference data: fixed ids, no created_at/updated_at. */
    public $timestamps = false;

    protected $fillable = ['id', 'name', 'code'];

    public function lgas()
    {
        return $this->hasMany(Lga::class, 'state_id', 'id')->orderBy('lga_name');
    }

    public function staff()
    {
        return $this->hasMany(BasicInformation::class, 'state_of_origin', 'id');
    }
}
