<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A desk inside a department. The same name may appear in two of them —
 * "Registry" means a different desk in each — so a unit is only ever read
 * alongside the department it belongs to.
 */
class Unit extends Model
{
    protected $table = 'units';

    protected $fillable = ['department_id', 'name'];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
}
