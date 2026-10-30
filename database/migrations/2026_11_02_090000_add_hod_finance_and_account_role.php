<?php

use App\Models\Role;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\Migrations\Migration;

/**
 * Finance & Account is its own directorate, with its own head. The roles table
 * had it folded into GAP.
 *
 * The seeder adds whatever is in Role::SHIPPED and skips what is already
 * there, so running it again is all this needs.
 */
return new class extends Migration
{
    private const ADDED = 'HOD Finance & Account';

    public function up(): void
    {
        (new RoleSeeder)->run();
    }

    public function down(): void
    {
        // Only drop it where no account is relying on it.
        Role::where('role', self::ADDED)
            ->get()
            ->each(fn ($role) => $role->users()->doesntExist() ? $role->delete() : null);
    }
};
