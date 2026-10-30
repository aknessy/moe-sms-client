<?php

namespace Tests\Feature;

use App\Models\BasicInformation;
use App\Models\StaffExit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

/**
 * Fetching the deceased register by year of death, and downloading it.
 */
class DeceasedRegisterTest extends TestCase
{
    use RefreshDatabase;

    private function actor(): User
    {
        return User::factory()->create(['role' => 'Administrator']);
    }

    /** An officer who died on the given date, with the exit that records it. */
    private function diedOn(string $surname, ?string $date): BasicInformation
    {
        $officer = BasicInformation::factory()->create([
            'surname' => $surname,
            'is_deceased' => 1,
        ]);

        if ($date !== null) {
            StaffExit::create([
                'basic_information_ulid' => $officer->ulid,
                'exit_type' => 'death',
                'effective_date' => $date,
                'gratuity_status' => 'pending',
                'pension_status' => 'pending',
            ]);
        }

        return $officer;
    }

    private function listed(array $query = []): array
    {
        return $this->actingAs($this->actor())
            ->get(route('deceased', $query))
            ->viewData('deceased')
            ->getCollection()
            ->pluck('surname')
            ->all();
    }

    public function test_the_register_offers_each_year_a_death_was_recorded(): void
    {
        $this->diedOn('Older', '2023-04-11');
        $this->diedOn('Recent', '2025-09-03');
        $this->diedOn('AlsoRecent', '2025-01-20');

        $years = $this->actingAs($this->actor())->get(route('deceased'))->viewData('years');

        // Newest first, listed once each.
        $this->assertSame([2025, 2023], $years->all());
    }

    public function test_the_register_can_be_fetched_by_year(): void
    {
        $this->diedOn('Older', '2023-04-11');
        $this->diedOn('Recent', '2025-09-03');

        $this->assertSame(['Recent'], $this->listed(['year' => 2025]));
        $this->assertSame(['Older'], $this->listed(['year' => 2023]));
    }

    public function test_without_a_year_the_whole_register_is_shown(): void
    {
        $this->diedOn('Older', '2023-04-11');
        $this->diedOn('Recent', '2025-09-03');
        $this->diedOn('Undated', null);

        $this->assertEqualsCanonicalizing(
            ['Older', 'Recent', 'Undated'],
            $this->listed()
        );
    }

    public function test_an_officer_with_no_date_of_death_is_outside_every_year(): void
    {
        $this->diedOn('Dated', '2025-09-03');
        $this->diedOn('Undated', null);

        // Nothing says which year they belong to, so they cannot be claimed
        // by one. They remain visible under "All years".
        $this->assertSame(['Dated'], $this->listed(['year' => 2025]));
        $this->assertContains('Undated', $this->listed());
    }

    public function test_a_year_nobody_died_in_returns_nothing_rather_than_everything(): void
    {
        $this->diedOn('Recent', '2025-09-03');

        $response = $this->actingAs($this->actor())->get(route('deceased', ['year' => 1999]));

        // 1999 is not an offered year, so it falls back to the whole register
        // rather than silently filtering to an empty list.
        $this->assertNull($response->viewData('year'));
    }

    public function test_a_nonsense_year_is_ignored(): void
    {
        $this->diedOn('Recent', '2025-09-03');

        $response = $this->actingAs($this->actor())->get(route('deceased', ['year' => 'notayear']));

        $response->assertOk();
        $this->assertNull($response->viewData('year'));
        $this->assertSame(['Recent'], $this->listed(['year' => 'notayear']));
    }

    /** Download the register and read the sheet back out of it. */
    private function downloadRows(array $query = []): array
    {
        $response = $this->actingAs($this->actor())->get(route('deceased.export', $query));
        $response->assertOk();

        $path = tempnam(sys_get_temp_dir(), 'xlsx') . '.xlsx';
        file_put_contents($path, $response->streamedContent());

        $rows = IOFactory::load($path)->getActiveSheet()->toArray();
        unlink($path);

        return $rows;
    }

    public function test_the_register_downloads_as_a_spreadsheet(): void
    {
        $this->diedOn('Recent', '2025-09-03');

        $response = $this->actingAs($this->actor())->get(route('deceased.export'));

        $response->assertOk();
        $response->assertHeader(
            'content-type',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        );
    }

    public function test_the_download_is_a_file_excel_can_open(): void
    {
        $this->diedOn('Recent', '2025-09-03');

        $rows = $this->downloadRows();

        // Read back through the reader, so this fails if the file is malformed.
        $this->assertSame('File / Computer No.', $rows[0][0]);
        $this->assertSame('Surname', $rows[0][1]);
        $this->assertSame('Recent', $rows[1][1]);
    }

    public function test_the_download_carries_the_whole_filtered_list(): void
    {
        // More officers than fit on one page of the on-screen listing.
        for ($i = 0; $i < 18; $i++) {
            $this->diedOn(sprintf('Officer%02d', $i), '2025-09-03');
        }

        $onScreen = $this->actingAs($this->actor())->get(route('deceased'))->viewData('deceased');
        $rows = $this->downloadRows();

        $this->assertCount(15, $onScreen->items(), 'the screen listing still pages');
        // 18 officers plus the heading row: a register must not stop at 15.
        $this->assertCount(19, $rows);
    }

    public function test_the_download_honours_the_year_filter(): void
    {
        $this->diedOn('Older', '2023-04-11');
        $this->diedOn('Recent', '2025-09-03');

        $surnames = collect($this->downloadRows(['year' => 2025]))->skip(1)->pluck(1)->values();

        $this->assertSame(['Recent'], $surnames->all());
    }

    public function test_the_download_carries_the_details_the_register_shows(): void
    {
        $officer = $this->diedOn('Nwachukwu', '2025-09-03');
        $officer->update([
            'firstname' => 'Chidi', 'othernames' => 'Obinna',
            'department' => 'ICT & Voter Registry', 'designation' => 'Data Officer II',
            'grade_level' => 8, 'step' => 1,
        ]);

        $row = $this->downloadRows()[1];

        $this->assertSame('Nwachukwu', $row[1]);
        $this->assertSame('Chidi', $row[2]);
        $this->assertSame('Obinna', $row[3]);
        $this->assertSame('ICT & Voter Registry', $row[4]);
        $this->assertSame('Data Officer II', $row[5]);
        $this->assertSame('GL 08', $row[6]);
        $this->assertSame('Step 1', $row[7]);
        $this->assertStringContainsString('2025-09-03', (string) $row[9]);
    }

    public function test_the_filename_says_what_the_sheet_covers(): void
    {
        $this->diedOn('Recent', '2025-09-03');

        $this->actingAs($this->actor())
            ->get(route('deceased.export', ['year' => 2025]))
            ->assertDownload('deceased-officers-2025-' . now()->format('Y-m-d') . '.xlsx');

        $this->actingAs($this->actor())
            ->get(route('deceased.export'))
            ->assertDownload('deceased-officers-all-years-' . now()->format('Y-m-d') . '.xlsx');
    }

    public function test_an_empty_register_still_downloads_with_its_headings(): void
    {
        $rows = $this->downloadRows();

        $this->assertCount(1, $rows, 'the heading row stands alone');
        $this->assertSame('File / Computer No.', $rows[0][0]);
    }

    public function test_the_download_is_not_served_to_a_guest(): void
    {
        $this->diedOn('Recent', '2025-09-03');

        $this->get(route('deceased.export'))->assertRedirect('/login');
    }
}
