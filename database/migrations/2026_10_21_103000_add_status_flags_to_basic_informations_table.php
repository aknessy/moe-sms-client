<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Whether an officer has died or retired is a standing fact about them, so it
 * belongs on the staff record rather than being inferred from the exit log
 * every time it is needed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('basic_informations', function (Blueprint $table) {
            $table->integer('is_deceased')->default(0)->after('address');
            $table->integer('is_retired')->default(0)->after('is_deceased');

            // Both are filtered on constantly by the pension listings.
            $table->index('is_deceased');
            $table->index('is_retired');
        });

        // Exits already on file say which officers these are.
        foreach ([['death', 'is_deceased'], ['retirement', 'is_retired']] as [$exitType, $column]) {
            $ulids = DB::table('staff_exits')->where('exit_type', $exitType)
                ->pluck('basic_information_ulid');

            if ($ulids->isNotEmpty()) {
                DB::table('basic_informations')->whereIn('ulid', $ulids)->update([$column => 1]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('basic_informations', function (Blueprint $table) {
            $table->dropIndex(['is_deceased']);
            $table->dropIndex(['is_retired']);
            $table->dropColumn(['is_deceased', 'is_retired']);
        });
    }
};
