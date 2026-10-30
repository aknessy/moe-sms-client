<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Things about this command that belong to the installation rather than the
 * code — read by name, written from the Settings page.
 */
class Setting extends Model
{
    protected $table = 'settings';

    protected $fillable = ['key', 'value'];

    /** Read on nearly every page, written rarely, so it is held in memory. */
    private const CACHE_KEY = 'settings.all';

    /**
     * The organisation's own details, as they head a letter. The defaults are
     * what the system prints before anyone has filled the form in — enough for
     * the page to read properly rather than show gaps.
     */
    public const ORGANISATION = [
        'organisation.name' => 'Independent National Electoral Commission',
        'organisation.command' => '',
        'organisation.address' => '',
        'organisation.telephone' => '',
        'organisation.email' => '',
        // Who an application is finally addressed to.
        'organisation.commissioner_title' => 'The Resident Electoral Commissioner',
    ];

    protected static function booted(): void
    {
        // Any write makes what is held stale, whichever way it was written.
        static::saved(fn () => Cache::forget(self::CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::CACHE_KEY));
    }

    /** Every setting, keyed by name. */
    public static function all($columns = ['*'])
    {
        return Cache::rememberForever(self::CACHE_KEY,
            fn () => static::query()->pluck('value', 'key'));
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        $value = self::all()[$key] ?? null;

        // A setting recorded as empty is not a setting: fall back, so a
        // cleared field reads the same as one never filled in. A fallback
        // that is itself empty reads as nothing at all rather than as a
        // blank string, which a letter would print a label in front of.
        $value = filled($value) ? $value : ($default ?? self::ORGANISATION[$key] ?? null);

        return filled($value) ? $value : null;
    }

    public static function put(string $key, ?string $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }

    /** Write several at once, as the Settings form submits them. */
    public static function putMany(array $values): void
    {
        foreach ($values as $key => $value) {
            self::put($key, $value);
        }
    }

    /** The organisation's details, filled in with the defaults. */
    public static function organisation(): array
    {
        $details = [];

        foreach (array_keys(self::ORGANISATION) as $key) {
            $details[$key] = self::get($key);
        }

        return $details;
    }
}
