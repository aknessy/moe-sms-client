<?php

namespace Database\Seeders;

use App\Models\BasicInformation;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Every account belongs to an officer already on the staff register,
        // identified here by the number they are filed under.
        $users = [
            ['admin', 'admin@inec.gov.ng', 'MOE/F/0010', 'administrator', 1],
            ['registry', 'registry@inec.gov.ng', 'INEC/CRS/0002', 'registry-officer', 1],
            ['disabled', 'disabled@inec.gov.ng', 'INEC/CRS/0008', 'registry-officer', 0],
        ];

        foreach ($users as [$username, $email, $staffNumber, $role, $active]) {
            $staff = BasicInformation::whereStaffNumber($staffNumber)->first();

            if ($staff === null) {
                $this->command?->warn("No staff filed under {$staffNumber}; skipped the {$username} account.");
                continue;
            }

            $user = User::firstOrNew(['username' => $username]);

            $user->fill([
                'basic_information_ulid' => $staff->ulid,
                'email' => $email,
                'permissions' => json_encode([]),
                'role' => $role,
                'is_active' => $active,
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
