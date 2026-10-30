<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Grade level and step were free text, and both spellings were in use: "GL 09"
 * alongside a bare "12", "Step 1" alongside "4". Sorting them meant pulling the
 * number back out, and nothing could be worked out from them — leave
 * entitlement follows the grade level, and that needs a number.
 *
 * So the digits become the value. How they read on screen is presentation,
 * and lives on the model.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Strip everything but the digits before the column stops accepting
        // anything else. A value with no digits at all becomes null rather
        // than 0, which would read as a real grade level.
        foreach (DB::table('basic_informations')->get(['id', 'grade_level', 'step']) as $row) {
            DB::table('basic_informations')->where('id', $row->id)->update([
                'grade_level' => self::digits($row->grade_level),
                'step' => self::digits($row->step),
            ]);
        }

        Schema::table('basic_informations', function (Blueprint $table) {
            $table->unsignedSmallInteger('grade_level')->nullable()->change();
            $table->unsignedSmallInteger('step')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('basic_informations', function (Blueprint $table) {
            $table->string('grade_level', 20)->nullable()->change();
            $table->string('step', 20)->nullable()->change();
        });

        // Put the spelling back on, so a rollback reads as it did before.
        DB::table('basic_informations')->whereNotNull('grade_level')
            ->update(['grade_level' => DB::raw("'GL ' || grade_level")]);
        DB::table('basic_informations')->whereNotNull('step')
            ->update(['step' => DB::raw("'Step ' || step")]);
    }

    private static function digits(?string $value): ?int
    {
        $digits = preg_replace('/\D/', '', (string) $value);

        return $digits === '' ? null : (int) $digits;
    }
};
