<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The directorate handles procurement, not personnel. Department is free text
 * on a staff record, and the same string ties a department to the role that
 * heads it and to the units inside it, so every column holding it moves
 * together.
 */
return new class extends Migration
{
    private const WAS = 'Department of General Administration and Personnel';

    private const NOW = 'Department of General Administration and Procurement';

    /** Every column that holds a department by name. */
    private const COLUMNS = [
        'basic_informations' => ['department'],
        'departments' => ['department'],
        'postings' => ['from_department', 'to_department'],
    ];

    public function up(): void
    {
        $this->rename(self::WAS, self::NOW);
    }

    public function down(): void
    {
        $this->rename(self::NOW, self::WAS);
    }

    private function rename(string $from, string $to): void
    {
        foreach (self::COLUMNS as $table => $columns) {
            foreach ($columns as $column) {
                DB::table($table)->where($column, $from)->update([$column => $to]);
            }
        }
    }
};
