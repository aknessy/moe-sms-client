<?php

namespace Database\Seeders;

use App\Models\BasicInformation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class StaffSeeder extends Seeder
{
    public function run(): void
    {
        $staff = [
            ['INEC/CRS/0001', 'Adeyemi', 'Oluwaseun', 'Tunde', '1968-03-14', '08031234501', 'Cross River', 'Calabar Municipal', 'Voter Education & Publicity', 'Calabar HQ', 'Chief Administrative Officer', 'GL 14'],
            ['INEC/CRS/0002', 'Okon', 'Grace', 'Eno', '1979-11-02', '08031234502', 'Cross River', 'Akamkpa', 'Administration & Finance', 'Akamkpa LGA Office', 'Administrative Officer I', 'GL 09'],
            ['INEC/CRS/0003', 'Bassey', 'Emmanuel', 'Effiong', '1966-07-21', '08031234503', 'Cross River', 'Odukpani', 'Operations', 'Odukpani LGA Office', 'Assistant Chief Electoral Officer', 'GL 13'],
            ['INEC/CRS/0004', 'Ibrahim', 'Aisha', 'Hauwa', '1985-01-09', '08031234504', 'Kano', 'Nassarawa', 'ICT & Voter Registry', 'Calabar HQ', 'Senior Data Officer', 'GL 10'],
            ['INEC/CRS/0005', 'Nwachukwu', 'Chidi', 'Obinna', '1990-05-30', '08031234505', 'Anambra', 'Awka South', 'ICT & Voter Registry', 'Calabar HQ', 'Data Officer II', 'GL 08'],
            ['INEC/CRS/0006', 'Edet', 'Mfon', 'Asuquo', '1972-09-17', '08031234506', 'Akwa Ibom', 'Uyo', 'Legal & Litigation', 'Calabar HQ', 'Principal Legal Officer', 'GL 12'],
            ['INEC/CRS/0007', 'Agbor', 'Patience', 'Ene', '1964-12-05', '08031234507', 'Cross River', 'Ogoja', 'Administration & Finance', 'Ogoja LGA Office', 'Chief Accountant', 'GL 14'],
            ['INEC/CRS/0008', 'Yakubu', 'Musa', 'Sani', '1988-04-23', '08031234508', 'Kaduna', 'Zaria', 'Operations', 'Calabar HQ', 'Electoral Officer', 'GL 09'],
        ];

        foreach ($staff as [$computer, $surname, $first, $other, $dob, $phone, $state, $lga, $department, $division, $designation, $gradeLevel]) {
            // The ulid is the foreign key every Registry record points at, so it is
            // assigned once on create and never rewritten by a re-run.
            $record = BasicInformation::firstOrNew(['computer_number' => $computer]);
            $record->ulid = $record->ulid ?: (string) Str::ulid();

            $record->fill(
                [
                    'surname' => $surname,
                    'firstname' => $first,
                    'othernames' => $other,
                    'department' => $department,
                    'division' => $division,
                    'designation' => $designation,
                    'grade_level' => $gradeLevel,
                    'date_of_birth' => $dob,
                    'phone_number' => $phone,
                    'email' => strtolower($first . '.' . $surname) . '@inec.gov.ng',
                    'state_of_origin' => $state,
                    'lga_of_origin' => $lga,
                    'pfa' => 'Stanbic IBTC Pension',
                    'rsa' => 'PEN' . random_int(100000000, 999999999),
                    'nhis' => random_int(10000000, 99999999),
                    'address' => 'No. ' . random_int(1, 90) . ' Marian Road, Calabar',
                ]
            )->save();
        }
    }
}
