<?php

namespace Database\Seeders;

use App\Models\BasicInformation;
use App\Models\Lga;
use App\Models\State;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class StaffSeeder extends Seeder
{
    /**
     * Origins are written here as names for readability, but the columns hold
     * states.id / lgas.id, so each one is resolved against the lookups. The LGA
     * is resolved within its state because several states share LGA names.
     */
    private function resolveOrigin(string $state, string $lga): array
    {
        $stateId = State::where('name', $state)->value('id');

        if ($stateId === null) {
            throw new \RuntimeException("Seeder references an unknown state: {$state}");
        }

        $lgaId = Lga::where('state_id', $stateId)->where('lga_name', $lga)->value('id');

        if ($lgaId === null) {
            throw new \RuntimeException("Seeder references an unknown LGA: {$lga} ({$state})");
        }

        return [$stateId, $lgaId];
    }

    public function run(): void
    {
        $staff = [
            ['MOE/F/0001', 'INEC/CRS/0001', 'Adeyemi', 'Oluwaseun', 'Tunde', '1968-03-14', '1992-08-01', '08031234501', 'Cross River', 'Calabar Municipal', 'Voter Education & Publicity', 'Calabar HQ', 'Chief Administrative Officer', 'GL 14', 'Step 5'],
            ['MOE/F/0002', 'INEC/CRS/0002', 'Okon', 'Grace', 'Eno', '1979-11-02', '2005-02-14', '08031234502', 'Cross River', 'Akamkpa', 'Administration & Finance', 'Akamkpa LGA Office', 'Administrative Officer I', 'GL 09', 'Step 3'],
            ['', 'INEC/CRS/0003', 'Bassey', 'Emmanuel', 'Effiong', '1966-07-21', '1990-10-01', '08031234503', 'Cross River', 'Odukpani', 'Department of Electoral Operations', 'Odukpani LGA Office', 'Assistant Chief Electoral Officer', 'GL 13', 'Step 7'],
            ['MOE/F/0004', 'INEC/CRS/0004', 'Ibrahim', 'Aisha', 'Hauwa', '1985-01-09', '2012-06-04', '08031234504', 'Kano', 'Nasarawa', 'ICT & Voter Registry', 'Calabar HQ', 'Senior Data Officer', 'GL 10', 'Step 2'],
            ['MOE/F/0005', 'INEC/CRS/0005', 'Nwachukwu', 'Chidi', 'Obinna', '1990-05-30', '2016-09-12', '08031234505', 'Anambra', 'Awka South', 'ICT & Voter Registry', 'Calabar HQ', 'Data Officer II', 'GL 08', 'Step 1'],
            ['', 'INEC/CRS/0006', 'Edet', 'Mfon', 'Asuquo', '1972-09-17', '1996-03-18', '08031234506', 'Akwa Ibom', 'Uyo', 'Legal & Litigation', 'Calabar HQ', 'Principal Legal Officer', 'GL 12', 'Step 6'],
            ['MOE/F/0007', 'INEC/CRS/0007', 'Agbor', 'Patience', 'Ene', '1964-12-05', '1989-01-16', '08031234507', 'Cross River', 'Ogoja', 'Administration & Finance', 'Ogoja LGA Office', 'Chief Accountant', 'GL 14', 'Step 8'],
            ['MOE/F/0008', 'INEC/CRS/0008', 'Yakubu', 'Musa', 'Sani', '1988-04-23', '2014-11-03', '08031234508', 'Kaduna', 'Zaria', 'Department of Electoral Operations', 'Calabar HQ', 'Electoral Officer', 'GL 09', 'Step 4'],
            // Carries a file number but was never issued a computer number.
            ['MOE/F/0009', '', 'Ekanem', 'Idongesit', 'Etim', '1976-08-19', '2001-05-21', '08031234509', 'Akwa Ibom', 'Ikot Ekpene', 'Administration & Finance', 'Calabar HQ', 'Senior Executive Officer', 'GL 10', 'Step 3'],
            // The officers the dashboard accounts in UserSeeder belong to.
            ['MOE/F/0010', 'INEC/CRS/0010', 'Abua', 'Isaac', '', '1982-06-11', '2008-07-07', '08031234510', 'Cross River', 'Calabar Municipal', 'ICT & Voter Registry', 'Calabar HQ', 'System Administrator', 'GL 12', 'Step 2'],
        ];

        foreach ($staff as [$fileNumber, $computer, $surname, $first, $other, $dob, $employedOn, $phone, $state, $lga, $department, $division, $designation, $gradeLevel, $step]) {
            // The ulid is the foreign key every Registry record points at, so it is
            // assigned once on create and never rewritten by a re-run.
            $record = $computer !== ''
                ? BasicInformation::firstOrNew(['computer_number' => $computer])
                : BasicInformation::firstOrNew(['file_number' => $fileNumber]);
            $record->ulid = $record->ulid ?: (string) Str::ulid();

            [$stateId, $lgaId] = $this->resolveOrigin($state, $lga);

            $record->fill(
                [
                    'file_number' => $fileNumber ?: null,
                    'computer_number' => $computer ?: null,
                    'surname' => $surname,
                    'firstname' => $first,
                    'othernames' => $other,
                    'department' => $department,
                    'division' => $division,
                    'designation' => $designation,
                    'grade_level' => $gradeLevel,
                    'step' => $step,
                    'date_of_birth' => $dob,
                    'date_of_employment' => $employedOn,
                    'phone_number' => $phone,
                    'email' => strtolower($first . '.' . $surname) . '@inec.gov.ng',
                    'state_of_origin' => $stateId,
                    'lga_of_origin' => $lgaId,
                    'pfa' => 'Stanbic IBTC Pension',
                    'rsa' => 'PEN' . random_int(100000000, 999999999),
                    'nhis' => random_int(10000000, 99999999),
                    'address' => 'No. ' . random_int(1, 90) . ' Marian Road, Calabar',
                ]
            )->save();
        }
    }
}
