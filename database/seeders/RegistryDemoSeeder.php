<?php

namespace Database\Seeders;

use App\Models\BasicInformation;
use App\Models\Posting;
use App\Models\StaffExit;
use App\Models\StaffQuery;
use Illuminate\Database\Seeder;

/**
 * Demo Registry activity so the dashboard has something to chart.
 * Idempotent: keyed on the staff member plus the record's date.
 */
class RegistryDemoSeeder extends Seeder
{
    public function run(): void
    {
        $previousYear = now()->year - 1;
        $currentYear = now()->year;

        // Officers are listed below by whichever number they carry.
        $byNumber = fn (string $number) => BasicInformation::whereStaffNumber($number)->value('ulid');

        $exits = [
            ['INEC/CRS/0007', 'retirement', $previousYear . '-12-05', 'paid', 'paid'],
            ['INEC/CRS/0003', 'retirement', $currentYear . '-07-21', 'processing', 'pending'],
            ['INEC/CRS/0006', 'resignation', $currentYear . '-02-28', 'pending', 'pending'],
            // Gives the Deceased module something to list.
            ['INEC/CRS/0005', 'death', $previousYear . '-09-03', 'processing', 'pending'],
        ];

        foreach ($exits as [$number, $type, $date, $gratuity, $pension]) {
            if (! $ulid = $byNumber($number)) {
                continue;
            }

            StaffExit::firstOrCreate(
                ['basic_information_ulid' => $ulid, 'effective_date' => $date],
                [
                    'exit_type' => $type,
                    'reference_number' => 'INEC/CRS/HR/EX/' . substr($date, 0, 4) . '/' . random_int(100, 999),
                    'gratuity_status' => $gratuity,
                    'pension_status' => $pension,
                    'remarks' => 'Seeded demonstration record.',
                ]
            );
        }

        $queries = [
            ['INEC/CRS/0002', $currentYear . '-05-14', 'Lateness to duty', 'caution', 'closed'],
            ['INEC/CRS/0004', $currentYear . '-08-02', 'Unauthorised use of Commission property', 'warning', 'closed'],
            ['INEC/CRS/0005', $currentYear . '-09-19', 'Insubordination', 'suspension', 'responded'],
            ['INEC/CRS/0008', $currentYear . '-10-01', 'Failure to submit returns', null, 'issued'],
        ];

        foreach ($queries as [$number, $date, $offence, $sanction, $status]) {
            if (! $ulid = $byNumber($number)) {
                continue;
            }

            StaffQuery::firstOrCreate(
                ['basic_information_ulid' => $ulid, 'query_date' => $date],
                [
                    'reference_number' => 'INEC/CRS/HR/Q/' . substr($date, 0, 4) . '/' . random_int(100, 999),
                    'offence' => $offence,
                    'sanction' => $sanction,
                    'status' => $status,
                ]
            );
        }

        $postings = [
            ['INEC/CRS/0002', 'Administration & Finance', 'Operations', 'Akamkpa LGA Office', $currentYear . '-03-01'],
            ['INEC/CRS/0004', 'ICT & Voter Registry', 'ICT & Voter Registry', 'Ogoja LGA Office', $currentYear . '-06-15'],
            ['INEC/CRS/0008', 'Operations', 'Voter Education & Publicity', 'Calabar HQ', $previousYear . '-11-20'],
        ];

        foreach ($postings as [$number, $from, $to, $station, $date]) {
            if (! $ulid = $byNumber($number)) {
                continue;
            }

            Posting::firstOrCreate(
                ['basic_information_ulid' => $ulid, 'effective_date' => $date],
                [
                    'posting_type' => 'deployment',
                    'from_department' => $from,
                    'to_department' => $to,
                    'duty_station' => $station,
                    'reference_number' => 'INEC/CRS/HR/P/' . substr($date, 0, 4) . '/' . random_int(100, 999),
                ]
            );
        }
    }
}
