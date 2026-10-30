<?php

use App\Models\DepartmentHead;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * A department per head of department.
 *
 * Every HOD role is the head of one directorate — "HOD Finance & Account" is
 * the head of the Department of Finance and Account, as "HOD ICT" is of ICT.
 * The departments on record did not line up with that: they were short forms
 * of convenience, and one of them, "Administration & Finance", was two
 * directorates in a single string, so Finance had no department to head.
 *
 * So the departments are named in full, after the directorates they are, and
 * the combined one is split. Department is free text on a staff record and the
 * same string ties a department to its head, so every column holding it moves
 * together.
 */
return new class extends Migration
{
    /** Every column that holds a department by name. */
    private const COLUMNS = [
        'basic_informations' => ['department'],
        'department_heads' => ['department'],
        'postings' => ['from_department', 'to_department'],
    ];

    /** The short form on record => the directorate's name. */
    private const RENAMED = [
        'ICT & Voter Registry' => 'Department of ICT and Voter Registry',
        'Voter Education & Publicity' => 'Department of Voter Education and Publicity',
    ];

    /**
     * Administration & Finance was two directorates. The officers who belong
     * to Finance are the ones on an accounting rank; the rest are general
     * administration and personnel.
     */
    private const SPLIT = 'Administration & Finance';

    private const FINANCE = 'Department of Finance and Account';

    private const ADMINISTRATION = 'Department of General Administration and Personnel';

    /** Each directorate and the role that heads it. */
    private const HEADS = [
        self::ADMINISTRATION => 'HOD GAP',
        self::FINANCE => 'HOD Finance & Account',
        'Department of Electoral Operations' => 'HOD EOPs',
        'Department of ICT and Voter Registry' => 'HOD ICT',
        'Department of Election and Party Monitoring' => 'HOD EPM',
        'Department of Voter Education and Publicity' => 'HOD VEP',
    ];

    public function up(): void
    {
        foreach (self::RENAMED as $was => $now) {
            $this->rename($was, $now);
        }

        // An accounting rank is what says an officer sits in Finance; there is
        // nothing else on the record to tell the two apart.
        DB::table('basic_informations')
            ->where('department', self::SPLIT)
            ->where('designation', 'like', '%Account%')
            ->update(['department' => self::FINANCE]);

        $this->rename(self::SPLIT, self::ADMINISTRATION);

        // Every directorate has a row, whether or not anyone is posted to it
        // yet, so the Settings page offers them all.
        foreach (self::HEADS as $department => $role) {
            DepartmentHead::updateOrCreate(['department' => $department], ['role' => $role]);
        }
    }

    public function down(): void
    {
        foreach (self::HEADS as $department => $role) {
            DepartmentHead::where('department', $department)->delete();
        }

        // Finance folds back into the department it came out of.
        $this->rename(self::FINANCE, self::SPLIT);
        $this->rename(self::ADMINISTRATION, self::SPLIT);

        foreach (self::RENAMED as $was => $now) {
            $this->rename($now, $was);
        }
    }

    private function rename(string $from, string $to): void
    {
        foreach (self::COLUMNS as $table => $columns) {
            foreach ($columns as $column) {
                // department_heads keys on the name, so a row already under
                // the new name would collide. The old one simply goes.
                if ($table === 'department_heads'
                    && DB::table($table)->where($column, $to)->exists()) {

                    DB::table($table)->where($column, $from)->delete();

                    continue;
                }

                DB::table($table)->where($column, $from)->update([$column => $to]);
            }
        }
    }
};
