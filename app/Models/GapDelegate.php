<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An officer HOD GAP has designated a Registry Officer: they work alongside
 * the head but cannot sign or approve on their behalf.
 */
class GapDelegate extends Model
{
    /** The role a designated officer holds while they are one. */
    public const ROLE = 'Registry Officer';

    protected $fillable = ['basic_information_ulid', 'designated_by', 'previous_role_id'];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(BasicInformation::class, 'basic_information_ulid', 'ulid');
    }

    /** Whether this account is a designated delegate (and still holds the role). */
    public static function holds(?User $user): bool
    {
        return $user?->basic_information_ulid !== null
            && $user->actual_role === self::ROLE
            && static::where('basic_information_ulid', $user->basic_information_ulid)->exists();
    }
}
