<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A directorate, the units inside it, and the role that heads it.
 *
 * Department is free text on a staff record, so this is the list that text
 * has to come from: a department nobody heads is one an officer's
 * applications reach nobody from.
 */
class Department extends Model
{
    protected $table = 'departments';

    protected $fillable = ['department', 'role'];

    public function units(): HasMany
    {
        return $this->hasMany(Unit::class);
    }

    /** The role heading a department, or null where none has been set. */
    public static function roleFor(?string $department): ?string
    {
        if (blank($department)) {
            return null;
        }

        return static::where('department', $department)->value('role') ?: null;
    }

    /**
     * Every department on a staff record has a row, so the list offers them
     * all rather than only the ones already answered for.
     */
    public static function syncWithStaffRecords(): void
    {
        $departments = BasicInformation::query()
            ->whereNotNull('department')
            ->distinct()
            ->pluck('department');

        foreach ($departments as $department) {
            static::firstOrCreate(['department' => $department]);
        }
    }

    public static function ordered()
    {
        return static::with('units')->orderBy('department')->get();
    }

    /**
     * The departments an officer may be put in, for a select. This is the
     * reference list: a department typed freehand would be one no head
     * answers for, and an application from it would reach nobody.
     */
    public static function options(): array
    {
        return static::orderBy('department')->pluck('department', 'department')->all();
    }

    public static function names(): array
    {
        return static::pluck('department')->all();
    }

    /**
     * Units grouped by the department they belong to, keyed by the
     * department's name — which is what a staff record holds. Feeds the
     * dependent select, so choosing a department narrows the units without
     * a round trip.
     */
    public static function unitsByDepartment(): array
    {
        return static::with('units')
            ->orderBy('department')
            ->get()
            ->mapWithKeys(fn ($department) => [
                $department->department => $department->units
                    ->sortBy('name')->pluck('name')->values()->all(),
            ])
            ->all();
    }

    /** Is this unit one of the named department's? */
    public static function hasUnit(?string $department, ?string $unit): bool
    {
        if (blank($department) || blank($unit)) {
            return false;
        }

        return static::where('department', $department)
            ->whereHas('units', fn ($q) => $q->where('name', $unit))
            ->exists();
    }
}
