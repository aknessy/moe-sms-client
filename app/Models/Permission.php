<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Permission extends Model
{
    protected $table = 'permissions';

    /** Granted once and never edited; it is withdrawn by deleting the row. */
    public const UPDATED_AT = null;

    protected $fillable = ['role_ulid', 'permission'];

    /**
     * Every permission that may be granted, and how it reads on screen. A
     * role is granted some of these; an account is then granted some of its
     * role's, never more.
     */
    public const AVAILABLE = [
        'create' => 'Create',
        'read' => 'Read',
        'update' => 'Modify / Update',
        'delete' => 'Delete',
        'backup-data' => 'Backup Data',
    ];

    public static function label(string $permission): string
    {
        return self::AVAILABLE[$permission] ?? $permission;
    }

    /** The role this permission is granted to. */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_ulid', 'role_ulid');
    }

    public function scopeFor($query, Role $role)
    {
        return $query->where('role_ulid', $role->role_ulid);
    }
}
