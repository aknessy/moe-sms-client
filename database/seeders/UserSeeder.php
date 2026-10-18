<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['admin', 'admin@inec.gov.ng', 'Abua', 'Isaac', 'administrator', 1],
            ['registry', 'registry@inec.gov.ng', 'Okon', 'Grace', 'registry-officer', 1],
            ['disabled', 'disabled@inec.gov.ng', 'Former', 'Staff', 'registry-officer', 0],
        ];

        foreach ($users as [$username, $email, $surname, $firstname, $role, $active]) {
            $user = User::firstOrNew(['username' => $username]);

            $user->fill([
                'email' => $email,
                'surname' => $surname,
                'firstname' => $firstname,
                'othernames' => '',
                'permissions' => json_encode([]),
                'role' => $role,
                'is_active' => $active,
                'ministry' => 'Independent National Electoral Commission',
                'department' => 'Human Resources',
            ]);

            // Cast on the model hashes this; only set it for a new account so a
            // re-run never resets a password that has since been changed.
            if (! $user->exists) {
                $user->password = 'password';
            }

            $user->save();
        }
    }
}
