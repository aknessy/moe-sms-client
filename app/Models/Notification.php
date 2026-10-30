<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

/**
 * Something waiting for an account's attention.
 */
class Notification extends Model
{
    use HasUlids;

    protected $table = 'notifications';

    protected $fillable = ['user_id', 'title', 'body', 'url', 'read_at'];

    protected $casts = ['read_at' => 'datetime'];

    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    public function scopeNewestFirst($query)
    {
        return $query->latest('created_at')->latest('id');
    }

    public function scopeFor($query, ?User $user)
    {
        return $query->where('user_id', $user?->id);
    }

    public function getIsReadAttribute(): bool
    {
        return $this->read_at !== null;
    }

    public function markRead(): void
    {
        if ($this->read_at === null) {
            $this->update(['read_at' => now()]);
        }
    }

    /**
     * Tell a number of accounts the same thing. Deduplicated, because the
     * officer who heads a department may also be an administrator, and one
     * notice is enough.
     *
     * @param  Collection<User>|array  $users
     */
    public static function send($users, string $title, ?string $body = null, ?string $url = null): int
    {
        $sent = 0;

        foreach (collect($users)->filter()->unique('id') as $user) {
            static::create([
                'user_id' => $user->id,
                'title' => $title,
                'body' => $body,
                'url' => $url,
            ]);

            $sent++;
        }

        return $sent;
    }
}
