<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The department's name in full. It was on record as "Operations", which is
 * what people call it rather than what it is called — and the letters the
 * system now raises print it, where the short form reads wrong.
 *
 * Department is free text on a staff record, and the same string is what ties
 * a department to the role that heads it, so every column holding it moves
 * together or the tie breaks.
 */
return new class extends Migration
{
    private const WAS = 'Operations';

    private const NOW = 'Department of Electoral Operations';

    /** Every column that holds a department by name. */
    private const COLUMNS = [
        'basic_informations' => ['department'],
        'department_heads' => ['department'],
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
