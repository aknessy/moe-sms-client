<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * An account's role becomes a reference to the roles table rather than a name
 * copied onto the account, so the two can no longer disagree.
 *
 * The key sits on users: roles.role is unique, so a user_id column there
 * could only ever record one account per role, and Registry Officer is
 * already held by two.
 *
 * is_admin follows from the role, and is set here for records already on file.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable()->after('id')
                ->constrained('roles')->nullOnDelete();
        });

        foreach (DB::table('roles')->get() as $role) {
            DB::table('users')->where('role', $role->role)->update([
                'role_id' => $role->id,
                // The one role that carries administrative rights.
                'is_admin' => $role->role === 'Administrator' ? 1 : 0,
            ]);
        }

        Schema::table('users', function (Blueprint $table) {
            // Superseded by role_id; the name is read back through it.
            $table->dropColumn('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->nullable();
        });

        foreach (DB::table('roles')->get() as $role) {
            DB::table('users')->where('role_id', $role->id)->update(['role' => $role->role]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropColumn('role_id');
        });
    }
};
