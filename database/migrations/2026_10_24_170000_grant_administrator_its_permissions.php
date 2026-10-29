<?php

use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Database\Migrations\Migration;

/**
 * The first role to be decided on: an administrator may create, read, modify,
 * delete and back up data.
 *
 * Accounts already holding the role are given the whole set. An account is
 * normally given a subset of what its role allows, but the administrator the
 * system was set up with would otherwise be left able to do nothing.
 */
return new class extends Migration
{
    public function up(): void
    {
        (new PermissionSeeder)->run();

        $role = Role::where('role', 'Administrator')->first();

        if ($role === null) {
            return;
        }

        $permissions = json_encode($role->permissionNames());

        User::where('role_id', $role->id)->get()
            ->each(fn ($user) => $user->update(['permissions' => $permissions]));
    }

    public function down(): void
    {
        Role::where('role', 'Administrator')->first()
            ?->revoke(PermissionSeeder::GRANTS['Administrator']);
    }
};
