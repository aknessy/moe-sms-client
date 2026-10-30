<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Training extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = ['title', 'description', 'venue', 'starts_on', 'ends_on', 'is_open'];

    protected $casts = ['is_open' => 'boolean'];

    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    public function interests(): HasMany
    {
        return $this->hasMany(TrainingInterest::class);
    }

    /** Still taking names. */
    public function scopeOpen($query)
    {
        return $query->where('is_open', 1);
    }

    /** Soonest first, with undated ones last — those have yet to be scheduled. */
    public function scopeSoonestFirst($query)
    {
        return $query->orderByRaw('starts_on IS NULL')->orderBy('starts_on');
    }

    /** "2 – 6 March 2027", or what is known of it. */
    public function getWhenAttribute(): string
    {
        if (blank($this->starts_on)) {
            return 'Date to be announced';
        }

        $starts = Carbon::parse($this->starts_on);

        if (blank($this->ends_on) || $this->ends_on === $this->starts_on) {
            return $starts->format('j F Y');
        }

        $ends = Carbon::parse($this->ends_on);

        // Only say the month and year once when both fall inside the same one.
        return $starts->isSameMonth($ends)
            ? $starts->format('j') . ' – ' . $ends->format('j F Y')
            : $starts->format('j M') . ' – ' . $ends->format('j M Y');
    }

    /** Has this officer put their name down? */
    public function wanted(?string $staffUlid): bool
    {
        return $staffUlid !== null
            && $this->interests->contains('basic_information_ulid', $staffUlid);
    }
}
