<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Role extends Model
{
    use HasUlids;

    protected $table = 'roles';

    /** A role is created once and named once; there is nothing to update. */
    public const UPDATED_AT = null;

    protected $fillable = ['role_ulid', 'role', 'created_by'];

    /**
     * The roles the system ships with, in the order they are meant to read —
     * most senior first. This is the order they are listed and offered in,
     * rather than the order they happened to be inserted: a role added to an
     * existing database takes the next id, which says nothing about rank.
     */
    public const SHIPPED = [
        'Commissioner',
        'Administrative Secretary',
        'Administrator',
        'Registry Officer',
        'User',
        'HOD GAP',
        'HOD ICT',
        'HOD EOPs',
        'HOD EPM',
        'HOD VEP',
    ];

    /**
     * Every role, ranked. Anything added later that is not in the shipped
     * list sorts to the end, alphabetically.
     */
    public static function ordered()
    {
        $rank = array_flip(self::SHIPPED);

        return self::all()
            ->sortBy(fn ($role) => [$rank[$role->role] ?? PHP_INT_MAX, $role->role])
            ->values();
    }

    public function uniqueIds(): array
    {
        return ['role_ulid'];
    }

    /** Null for the roles the system ships with. */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function users()
    {
        return $this->hasMany(User::class, 'role_id');
    }

    /** What this role may do. A role may hold several. */
    public function permissions()
    {
        return $this->hasMany(Permission::class, 'role_ulid', 'role_ulid');
    }

    /** Just the names, for reading and comparing. */
    public function permissionNames(): array
    {
        return $this->permissions->pluck('permission')->sort()->values()->all();
    }

    public function grants(string $permission): bool
    {
        return $this->permissions->contains('permission', $permission);
    }

    /**
     * Grant a permission, or several. Granting one a role already holds
     * changes nothing rather than failing.
     */
    public function grant(string|array $permission): void
    {
        foreach ((array) $permission as $name) {
            $this->permissions()->firstOrCreate(['permission' => $name]);
        }

        $this->unsetRelation('permissions');
    }

    public function revoke(string|array $permission): void
    {
        $this->permissions()->whereIn('permission', (array) $permission)->delete();

        $this->unsetRelation('permissions');
    }

    /** Whether holding this role carries administrative rights. */
    public function getIsAdminRoleAttribute(): bool
    {
        return $this->role === User::ADMIN_ROLE;
    }

    /** [role => role] for a select, ranked. */
    public static function options(): array
    {
        return self::ordered()->pluck('role', 'role')->all();
    }
}
