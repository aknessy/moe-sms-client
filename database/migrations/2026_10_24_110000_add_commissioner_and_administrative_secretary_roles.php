<?php

use App\Models\Role;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\Migrations\Migration;

/**
 * Two senior roles the first pass missed. The seeder adds whatever is in
 * Role::SHIPPED and skips what is already there, so running it again is all
 * this needs.
 */
return new class extends Migration
{
    private const ADDED = ['Commissioner', 'Administrative Secretary'];

    public function up(): void
    {
        (new RoleSeeder)->run();
    }

    public function down(): void
    {
        // Only drop them where no account is relying on them.
        Role::whereIn('role', self::ADDED)
            ->get()
            ->each(fn ($role) => $role->users()->doesntExist() ? $role->delete() : null);
    }
};
