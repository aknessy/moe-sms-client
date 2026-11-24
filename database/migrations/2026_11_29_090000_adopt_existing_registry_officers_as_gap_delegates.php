<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Registry Officers who were already in HOD GAP's department before
 * designations existed were given the role through Users, Roles & Permissions,
 * so they have no designation on record and the Delegates page did not list
 * them. Record them as designated by the head of their department, so they
 * are listed, held to the same limits, and can be withdrawn.
 *
 * Withdrawing puts them back on the ordinary staff role, the one they would
 * have held before being given this one.
 */
return new class extends Migration
{
    public function up(): void
    {
        $registryRole = DB::table('roles')->where('role', 'Registry Officer')->value('id');
        $hodGapRole = DB::table('roles')->where('role', 'HOD GAP')->value('id');
        $staffRole = DB::table('roles')->where('role', 'User')->value('id');

        if (! $registryRole || ! $hodGapRole) {
            return;
        }

        $heads = DB::table('users')
            ->join('basic_informations', 'basic_informations.ulid', '=', 'users.basic_information_ulid')
            ->where('users.role_id', $hodGapRole)
            ->get(['basic_informations.ulid as ulid', 'basic_informations.department as department']);

        foreach ($heads as $head) {
            if (blank($head->department)) {
                continue;
            }

            $officers = DB::table('users')
                ->join('basic_informations', 'basic_informations.ulid', '=', 'users.basic_information_ulid')
                ->where('users.role_id', $registryRole)
                ->where('basic_informations.department', $head->department)
                ->pluck('basic_informations.ulid');

            foreach ($officers as $ulid) {
                DB::table('gap_delegates')->insertOrIgnore([
                    'basic_information_ulid' => $ulid,
                    'designated_by' => $head->ulid,
                    'previous_role_id' => $staffRole,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        // Nothing to undo: the rows are as valid as any made from the page.
    }
};
