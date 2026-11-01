<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A brief is drawn in two steps: the list is worked up first, then put up.
 *
 * So an entry is staged before it is on the brief. Opening the year stages
 * everyone the records say is due; looking them over, taking any off and
 * adding any the rule missed is done while staged; and the brief itself is
 * what the transfer puts there.
 *
 * Entries recorded before this are already on the brief, there having been
 * nowhere else for them to be.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('promotion_briefs', function (Blueprint $table) {
            $table->timestamp('on_brief_at')->nullable()->after('status');
        });

        DB::table('promotion_briefs')->update(['on_brief_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('promotion_briefs', function (Blueprint $table) {
            $table->dropColumn('on_brief_at');
        });
    }
};
