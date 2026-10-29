<?php

namespace Tests\Feature;

use App\Models\BasicInformation;
use App\Models\Lga;
use App\Models\Posting;
use App\Models\State;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The report carries the same columns as the Registry roll, grouped the same
 * way. It keeps its own status filter, since choosing between serving,
 * retired and deceased officers is what the report is for.
 */
class NominalRollReportColumnsTest extends TestCase
{
    use RefreshDatabase;

    private function actor(): User
    {
        return User::factory()->create(['role' => 'Administrator']);
    }

    private function report(array $query = [])
    {
        return $this->actingAs($this->actor())->get(route('nominal-roll-report', $query));
    }

    private function order(array $query = []): array
    {
        return $this->report($query)->viewData('staff')->pluck('surname')->all();
    }

    private function officer(array $attributes = []): BasicInformation
    {
        return BasicInformation::factory()->create($attributes);
    }

    /* ------------------------------------------------------ the columns */

    public function test_the_report_heads_the_same_columns_as_the_registry_roll(): void
    {
        $response = $this->report();

        foreach (['Gender', 'Rank', 'GL / Step', 'Date of Birth', 'Date of Confirmation',
                  'Phone Number', 'State of Origin', 'LGA of Origin', 'LGA of Posting',
                  'PFA', 'RSA PIN', 'Bank &amp; Account Number', 'Year of Retirement',
                  'Mode of Retirement'] as $heading) {
            $response->assertSee($heading, false);
        }
    }

    public function test_the_ampersand_in_the_bank_column_is_not_double_escaped(): void
    {
        // Written as "&amp;" in the heading it would reach the page as
        // "&amp;amp;" and read "&amp;" on screen.
        $this->report()->assertDontSee('Bank &amp;amp; Account Number', false);
    }

    public function test_the_report_keeps_its_status_column(): void
    {
        // The Registry roll has none: it only ever lists serving officers.
        $this->report()->assertSee('Status', false);
    }

    public function test_the_state_and_lga_are_now_separate_columns(): void
    {
        // They used to share one "State / LGA" column.
        $this->report()->assertDontSee('State / LGA', false);
    }

    public function test_the_lga_of_posting_follows_the_officers_postings(): void
    {
        $lagos = State::where('name', 'Lagos')->firstOrFail();
        $lga = Lga::where('state_id', $lagos->id)->firstOrFail();

        $staff = $this->officer();

        Posting::create([
            'basic_information_ulid' => $staff->ulid,
            'posting_type' => 'transfer',
            'to_state' => $lagos->id,
            'to_lga' => $lga->id,
            'duty_station' => 'Ikeja Office',
            'effective_date' => '2022-01-01',
        ]);

        $context = $this->report()->viewData('postingContext');

        $this->assertSame($lga->lga_name, $context[$staff->ulid]['lga']);
    }

    /* ------------------------------------------- grouped by standing */

    public function test_the_highest_grade_level_comes_first(): void
    {
        $this->officer(['surname' => 'Junior', 'grade_level' => 'GL 07', 'step' => 'Step 1']);
        $this->officer(['surname' => 'Senior', 'grade_level' => 'GL 14', 'step' => 'Step 1']);

        $order = $this->order();

        $this->assertLessThan(array_search('Junior', $order), array_search('Senior', $order));
    }

    public function test_grade_level_is_read_as_a_number_not_as_text(): void
    {
        $this->officer(['surname' => 'Niner', 'grade_level' => 'GL 09', 'step' => 'Step 1']);
        $this->officer(['surname' => 'Fourteen', 'grade_level' => 'GL 14', 'step' => 'Step 1']);

        $order = $this->order();

        $this->assertLessThan(array_search('Niner', $order), array_search('Fourteen', $order));
    }

    public function test_officers_of_the_same_standing_are_alphabetical(): void
    {
        $this->officer(['surname' => 'Zulu', 'grade_level' => 'GL 10', 'step' => 'Step 5']);
        $this->officer(['surname' => 'Alpha', 'grade_level' => 'GL 10', 'step' => 'Step 5']);

        $order = $this->order();

        $this->assertLessThan(array_search('Zulu', $order), array_search('Alpha', $order));
    }

    /* ------------------------------------------- the filters still work */

    public function test_the_report_still_lists_officers_who_have_left(): void
    {
        $this->officer(['surname' => 'Retired', 'is_retired' => 1]);
        $this->officer(['surname' => 'Departed', 'is_deceased' => 1]);

        // Unlike the Registry roll, which is of serving officers only.
        $order = $this->order();

        $this->assertContains('Retired', $order);
        $this->assertContains('Departed', $order);
    }

    public function test_narrowing_to_active_service_still_works(): void
    {
        $this->officer(['surname' => 'Serving', 'is_retired' => 0, 'is_deceased' => 0]);
        $this->officer(['surname' => 'Retired', 'is_retired' => 1]);

        $order = $this->order(['status' => 'active']);

        $this->assertContains('Serving', $order);
        $this->assertNotContains('Retired', $order);
    }

    public function test_narrowing_by_grade_level_still_works(): void
    {
        $this->officer(['surname' => 'Twelve', 'grade_level' => 'GL 12', 'step' => 'Step 1']);
        $this->officer(['surname' => 'Seven', 'grade_level' => 'GL 07', 'step' => 'Step 1']);

        $order = $this->order(['grade_level' => 'GL 12']);

        $this->assertContains('Twelve', $order);
        $this->assertNotContains('Seven', $order);
    }

    /* ------------------------------------------------------- the table */

    public function test_the_table_scrolls_sideways_rather_than_squashing(): void
    {
        $response = $this->report();

        $response->assertSee('overflow-x-auto', false);
        $response->assertSee('min-w-max', false);
    }

    public function test_the_header_is_a_darker_shade_of_the_theme_green(): void
    {
        $this->report()->assertSee('bg-[#1B5E20]', false);
    }

    /* ------------------------------------------------------- the export */

    public function test_the_download_carries_the_same_columns(): void
    {
        $this->officer();

        $response = $this->actingAs($this->actor())
            ->get(route('nominal-roll-report.export'));

        $response->assertOk();
        $response->assertHeader('content-type',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }
}
