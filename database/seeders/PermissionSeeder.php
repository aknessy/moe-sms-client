<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

/**
 * What each role is allowed to do. Only the roles decided on so far appear
 * here; the rest allow nothing until someone says otherwise.
 */
class PermissionSeeder extends Seeder
{
    public const GRANTS = [
        'Administrator' => ['create', 'read', 'update', 'delete', 'backup-data'],
    ];

    public function run(): void
    {
        foreach (self::GRANTS as $name => $permissions) {
            $role = Role::where('role', $name)->first();

            if ($role === null) {
                $this->command?->warn("No role named {$name}; skipped its permissions.");
                continue;
            }

            $role->grant($permissions);
        }
    }
}
