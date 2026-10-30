<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Which role heads which department. See the migration for why this is a table
 * and not a match expression in code.
 */
class DepartmentHead extends Model
{
    protected $table = 'department_heads';

    protected $fillable = ['department', 'role'];

    /** The role heading a department, or null where none has been set. */
    public static function roleFor(?string $department): ?string
    {
        if (blank($department)) {
            return null;
        }

        return static::where('department', $department)->value('role') ?: null;
    }

    /**
     * Every department on a staff record has a row, so Settings lists them all
     * rather than only the ones already answered for.
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
        return static::orderBy('department')->get();
    }
}
