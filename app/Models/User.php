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
        'role',
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
