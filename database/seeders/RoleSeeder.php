<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

/**
 * The roles the system ships with. The list itself lives on the model, which
 * also holds the order they read in.
 */
class RoleSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Role::SHIPPED as $role) {
            // firstOrCreate so a re-run never duplicates a role or disturbs
            // the ulid an existing one is known by. created_at is set by the
            // model; created_by stays null for a role the system ships with.
            Role::firstOrCreate(['role' => $role]);
        }
    }
}
