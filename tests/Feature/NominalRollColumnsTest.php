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
 * The roll is of officers still serving, grouped by standing — senior grade
 * levels first — and carries what the registry needs to read off it at a
 * glance.
 */
class NominalRollColumnsTest extends TestCase
{
    use RefreshDatabase;

    private function actor(): User
    {
        return User::factory()->create(['role' => 'Administrator']);
    }

    private function roll()
    {
        return $this->actingAs($this->actor())->get(route('nominal-roll'));
    }

    /** Surnames as the roll lists them, in order. */
    private function order(): array
    {
        return $this->roll()->viewData('staff')->pluck('surname')->all();
    }

    private function officer(array $attributes = []): BasicInformation
    {
        return BasicInformation::factory()->create($attributes);
    }

    /* ------------------------------------------------- in active service */

    public function test_the_roll_lists_officers_still_serving(): void
    {
        $this->officer(['surname' => 'Serving', 'is_retired' => 0, 'is_deceased' => 0]);

        $this->assertContains('Serving', $this->order());
    }

    public function test_a_retired_officer_is_off_the_roll(): void
    {
        $this->officer(['surname' => 'Retired', 'is_retired' => 1]);

        $this->assertNotContains('Retired', $this->order());
    }

    public function test_a_deceased_officer_is_off_the_roll(): void
    {
        $this->officer(['surname' => 'Departed', 'is_deceased' => 1]);

        $this->assertNotContains('Departed', $this->order());
    }

    public function test_the_count_is_of_those_still_serving(): void
    {
        $this->officer(['is_retired' => 0, 'is_deceased' => 0]);
        $this->officer(['is_retired' => 1]);
        $this->officer(['is_deceased' => 1]);

        // The acting account has a staff record of its own, so count the
        // listing rather than asserting a bare number.
        $total = $this->roll()->viewData('staff')->total();

        $this->assertSame(BasicInformation::inActiveService()->count(), $total);
    }

    /* ------------------------------------------- grouped by standing */

    public function test_the_highest_grade_level_comes_first(): void
    {
        $this->officer(['surname' => 'Junior', 'grade_level' => 7, 'step' => 1]);
        $this->officer(['surname' => 'Senior', 'grade_level' => 14, 'step' => 1]);

        $order = $this->order();

        $this->assertLessThan(array_search('Junior', $order), array_search('Senior', $order));
    }

    public function test_grade_level_is_read_as_a_number_not_as_text(): void
    {
        // Sorted as written, "GL 09" would come above "GL 14".
        $this->officer(['surname' => 'Niner', 'grade_level' => 9, 'step' => 1]);
        $this->officer(['surname' => 'Fourteen', 'grade_level' => 14, 'step' => 1]);

        $order = $this->order();

        $this->assertLessThan(array_search('Niner', $order), array_search('Fourteen', $order));
    }

    public function test_the_highest_step_comes_first_within_a_grade_level(): void
    {
        $this->officer(['surname' => 'StepOne', 'grade_level' => 10, 'step' => 1]);
        $this->officer(['surname' => 'StepNine', 'grade_level' => 10, 'step' => 9]);

        $order = $this->order();

        $this->assertLessThan(array_search('StepOne', $order), array_search('StepNine', $order));
    }

    public function test_the_number_on_record_reads_as_a_grade_level_on_the_roll(): void
    {
        // The column holds 12; the roll prints the standing in full.
        $this->officer(['surname' => 'Bare', 'grade_level' => 12, 'step' => 4]);

        $this->roll()->assertSee('GL 12 / Step 4');
    }

    public function test_officers_of_the_same_standing_are_alphabetical(): void
    {
        $this->officer(['surname' => 'Zulu', 'grade_level' => 10, 'step' => 5]);
        $this->officer(['surname' => 'Alpha', 'grade_level' => 10, 'step' => 5]);

        $order = $this->order();

        $this->assertLessThan(array_search('Zulu', $order), array_search('Alpha', $order));
    }

    public function test_an_officer_with_no_grade_level_sorts_last(): void
    {
        $this->officer(['surname' => 'Ungraded', 'grade_level' => null, 'step' => null]);
        $this->officer(['surname' => 'Graded', 'grade_level' => 7, 'step' => 1]);

        $order = $this->order();

        $this->assertLessThan(array_search('Ungraded', $order), array_search('Graded', $order));
    }

    /* ------------------------------------------------------ the columns */

    public function test_the_roll_heads_every_column_the_registry_reads(): void
    {
        $response = $this->roll();

        foreach (['Gender', 'Rank', 'GL / Step', 'Date of Confirmation', 'Phone Number',
                  'State of Origin', 'LGA of Origin', 'LGA of Posting', 'PFA', 'RSA PIN',
                  'Bank &amp; Account Number', 'Year of Retirement', 'Mode of Retirement'] as $heading) {
            $response->assertSee($heading, false);
        }
    }

    public function test_the_lga_column_says_which_lga_it_means(): void
    {
        // It used to read just "LGA", which did not say origin or posting.
        $this->roll()->assertSee('LGA of Origin', false);
    }

    public function test_the_year_of_retirement_is_shown(): void
    {
        $this->officer(['surname' => 'Retiring', 'date_of_birth' => '1970-06-11',
                        'date_of_employment' => '2000-01-02']);

        // 35 years of service lands in 2035, before age 60 in 2030 — so 2030.
        $staff = BasicInformation::where('surname', 'Retiring')->first();

        $this->assertSame(2030, $staff->retiresOn()->year);
        $this->roll()->assertSee('2030');
    }

    public function test_the_mode_of_retirement_is_shown(): void
    {
        $this->officer(['surname' => 'Serving', 'date_of_birth' => '1970-06-11',
                        'date_of_employment' => '2000-01-02']);

        $this->roll()->assertSee('Retirement by Age');
    }

    public function test_the_lga_of_posting_follows_the_officers_postings(): void
    {
        $lagos = State::where('name', 'Lagos')->firstOrFail();
        $lga = Lga::where('state_id', $lagos->id)->firstOrFail();

        $staff = $this->officer(['surname' => 'Posted']);

        Posting::create([
            'basic_information_ulid' => $staff->ulid,
            'posting_type' => 'transfer',
            'to_state' => $lagos->id,
            'to_lga' => $lga->id,
            'duty_station' => 'Ikeja Office',
            'effective_date' => '2022-01-01',
        ]);

        $context = $this->roll()->viewData('postingContext');

        $this->assertSame($lga->lga_name, $context[$staff->ulid]['lga']);
    }

    public function test_an_officer_never_posted_shows_this_commands_lga(): void
    {
        $staff = $this->officer(['surname' => 'Unposted']);

        $context = $this->roll()->viewData('postingContext');

        $this->assertSame(config('posting.default_lga'), $context[$staff->ulid]['lga']);
    }

    public function test_the_bank_and_account_number_is_left_blank(): void
    {
        // Nothing holds it yet; the column waits on the accounting section.
        $this->roll()->assertSee('Bank &amp; Account Number', false);

        $this->assertFalse(
            \Illuminate\Support\Facades\Schema::hasColumn('basic_informations', 'account_number'),
            'no account number is stored yet'
        );
    }

    /* ------------------------------------------------------- the table */

    public function test_the_table_scrolls_sideways_rather_than_squashing(): void
    {
        $response = $this->roll();

        // Sized to its content inside a scrolling container.
        $response->assertSee('overflow-x-auto', false);
        $response->assertSee('min-w-max', false);
    }

    public function test_the_header_is_a_darker_shade_of_the_theme_green(): void
    {
        $this->roll()->assertSee('bg-[#1B5E20]', false);
    }
}
