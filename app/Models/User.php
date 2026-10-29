<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        // The officer this account belongs to; their name, department and the
        // rest of the personal record live on basic_informations.
        'basic_information_ulid',
        'username',
        'email',
        'password',
        'is_active',
        // Accepted as the role's name and stored as role_id; see below.
        'role',
        'role_id',
        'permissions',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'plain_text_password',
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * The officer this account belongs to.
     */
    public function staff(): BelongsTo
    {
        return $this->belongsTo(BasicInformation::class, 'basic_information_ulid', 'ulid');
    }

    /**
     * The role this account holds. Named `roleRecord` so `$user->role` can go
     * on reading as the name it always did.
     */
    public function roleRecord(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    /** The role's name, read through the reference. */
    public function getRoleAttribute(): ?string
    {
        return $this->roleRecord?->role;
    }

    /**
     * Accepts the role by name and stores the reference, so `['role' => 'HOD
     * ICT']` still reads naturally at the call site. An unrecognised name
     * leaves the account without a role rather than inventing one; the form
     * rejects those before they get this far.
     */
    public function setRoleAttribute(?string $name): void
    {
        $this->attributes['role_id'] = $name === null
            ? null
            : Role::where('role', $name)->value('id');

        // The relation was loaded against the old role, if at all.
        $this->unsetRelation('roleRecord');
    }

    /** Administrator is the role that carries administrative rights. */
    public const ADMIN_ROLE = 'Administrator';

    /**
     * is_admin follows from the role rather than being set alongside it, so
     * the two cannot drift: granting any other role withdraws it again.
     */
    protected static function booted(): void
    {
        static::saving(function (self $user) {
            $role = $user->role_id
                ? Role::where('id', $user->role_id)->value('role')
                : null;

            $user->attributes['is_admin'] = $role === self::ADMIN_ROLE ? 1 : 0;
        });
    }

    /*
     * These used to be columns on this table. They are read off the staff
     * record now, so `$user->firstname` still reads the same at the call site.
     */

    public function getSurnameAttribute(): ?string
    {
        return $this->staff?->surname;
    }

    public function getFirstnameAttribute(): ?string
    {
        return $this->staff?->firstname;
    }

    public function getOthernamesAttribute(): ?string
    {
        return $this->staff?->othernames;
    }

    public function getDepartmentAttribute(): ?string
    {
        return $this->staff?->department;
    }

    /**
     * "Okon, Grace Eno" — the officer as the dashboard should address them.
     */
    public function getFullNameAttribute(): ?string
    {
        if ($this->staff === null) {
            return null;
        }

        $given = trim("{$this->staff->firstname} {$this->staff->othernames}");

        return trim("{$this->staff->surname}, {$given}");
    }

    /**
     * An account is only usable once it points at a staff record.
     */
    public function scopeLinked($query)
    {
        return $query->whereNotNull('basic_information_ulid');
    }
}
