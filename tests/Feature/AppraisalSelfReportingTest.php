<?php

namespace Tests\Feature;

use App\Models\BasicInformation;
use App\Models\SelfReportActivity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The Self Reporting section of the appraisal: what the officer took part in,
 * quarter by quarter, and what the reporting officer made of each of it.
 *
 * The section runs over three years, each over its first three quarters, and
 * a quarter written in is answered with at least four activities.
 */
class AppraisalSelfReportingTest extends TestCase
{
    use RefreshDatabase;

    private function actor(): User
    {
        return User::factory()->create([
            'basic_information_ulid' => BasicInformation::factory()->create()->ulid,
            'role' => 'Administrator',
        ]);
    }

    private function officer(): BasicInformation
    {
        return BasicInformation::factory()->create([
            'surname' => 'Asuquo', 'firstname' => 'Bassey',
        ]);
    }

    private function section(BasicInformation $staff)
    {
        return $this->actingAs($this->actor())->get(route('hr-appraisal', [
            'section' => 'self-reporting', 'staff' => $staff->ulid,
        ]));
    }

    /** Four activities in one quarter, which is the fewest it takes. */
    private function quarter(int $year, int $quarter, int $howMany = 4,
                             array $comments = []): array
    {
        $activities = [];

        foreach (range(1, $howMany) as $n) {
            $activities[] = "Activity {$n} of Q{$quarter} {$year}";
        }

        return [
            "activity" => [$year => [$quarter => $activities]],
            "comment" => [$year => [$quarter => $comments]],
        ];
    }

    private function save(BasicInformation $staff, array $payload)
    {
        return $this->actingAs($this->actor())
            ->post(route('hr-appraisal.self-reporting'), array_merge([
                'staff' => $staff->ulid,
                'section' => 'self-reporting',
            ], $payload));
    }

    /* ------------------------------------------------- what it asks for */

    public function test_the_section_has_a_form_of_its_own(): void
    {
        $this->section($this->officer())
            ->assertOk()
            ->assertDontSee('This section has not been built yet.')
            ->assertSee(route('hr-appraisal.self-reporting'));
    }

    public function test_it_asks_after_three_years(): void
    {
        $response = $this->section($this->officer());

        foreach (range(now()->year - 2, now()->year) as $year) {
            $response->assertSee('data-year-pane="' . $year . '"', false);
        }

        $this->assertSame(3, SelfReportActivity::YEARS);
    }

    public function test_each_year_is_broken_into_its_first_three_quarters(): void
    {
        $response = $this->section($this->officer());

        foreach (range(now()->year - 2, now()->year) as $year) {
            foreach ([1, 2, 3] as $quarter) {
                $response->assertSee('data-quarter="' . $year . '.' . $quarter . '"', false);
            }
        }

        // The fourth is not one the appraisal asks after.
        $this->assertSame([1, 2, 3], array_keys(SelfReportActivity::QUARTERS));
    }

    public function test_every_quarter_offers_four_activities_to_write_in(): void
    {
        $html = $this->section($this->officer())->getContent();

        // Nine quarters, four rows apiece, each row an activity and the
        // reporting officer's comment on it.
        $this->assertSame(36, substr_count($html, 'data-activity-row>'));
        $this->assertSame(36, substr_count($html, 'name="activity['));
        $this->assertSame(36, substr_count($html, 'name="comment['));
    }

    public function test_each_activity_carries_a_reporting_officers_comment(): void
    {
        $this->section($this->officer())->assertSee("Reporting Officer's Comments", false);
    }

    /* ------------------------------------------------------- saving it */

    public function test_a_quarters_activities_are_recorded(): void
    {
        $staff = $this->officer();

        $this->save($staff, $this->quarter(2025, 2))->assertSessionHasNoErrors();

        $held = SelfReportActivity::where('basic_information_ulid', $staff->ulid)
            ->orderBy('position')->get();

        $this->assertCount(4, $held);
        $this->assertSame('Activity 1 of Q2 2025', $held->first()->activity);
        $this->assertSame(2025, $held->first()->year);
        $this->assertSame(2, $held->first()->quarter);
    }

    public function test_the_order_they_were_written_in_is_kept(): void
    {
        $staff = $this->officer();

        $this->save($staff, $this->quarter(2025, 1));

        $held = SelfReportActivity::where('basic_information_ulid', $staff->ulid)
            ->orderBy('position')->pluck('activity')->all();

        $this->assertSame([
            'Activity 1 of Q1 2025', 'Activity 2 of Q1 2025',
            'Activity 3 of Q1 2025', 'Activity 4 of Q1 2025',
        ], $held);
    }

    public function test_the_reporting_officers_comment_is_kept_beside_its_activity(): void
    {
        $staff = $this->officer();

        $this->save($staff, $this->quarter(2025, 1, 4, [
            'Handled it well', '', 'Needs closer supervision', '',
        ]))->assertSessionHasNoErrors();

        $held = SelfReportActivity::where('basic_information_ulid', $staff->ulid)
            ->orderBy('position')->get();

        $this->assertSame('Handled it well', $held[0]->comment);
        $this->assertNull($held[1]->comment);
        $this->assertSame('Needs closer supervision', $held[2]->comment);
    }

    public function test_more_than_four_activities_are_all_kept(): void
    {
        $staff = $this->officer();

        $this->save($staff, $this->quarter(2025, 3, 7))->assertSessionHasNoErrors();

        $this->assertSame(7, SelfReportActivity::where('basic_information_ulid', $staff->ulid)->count());
    }

    public function test_several_quarters_are_saved_together(): void
    {
        // The whole of the section is on the one form, so one save carries
        // every quarter written in.
        $staff = $this->officer();

        $this->save($staff, [
            'activity' => [
                2024 => [1 => $this->four('a'), 3 => $this->four('b')],
                2026 => [2 => $this->four('c')],
            ],
        ])->assertSessionHasNoErrors();

        $held = SelfReportActivity::where('basic_information_ulid', $staff->ulid)->get();

        $this->assertCount(12, $held);
        $this->assertSame([[2024, 1], [2024, 3], [2026, 2]],
            $held->map(fn ($e) => [$e->year, $e->quarter])->unique()->values()
                ->sortBy(fn ($pair) => $pair)->values()->all());
    }

    private function four(string $tag): array
    {
        return array_map(fn ($n) => "{$tag}{$n}", range(1, 4));
    }

    public function test_saving_carries_on_to_the_next_section(): void
    {
        $staff = $this->officer();

        $this->save($staff, $this->quarter(2025, 1))->assertRedirect(route('hr-appraisal', [
            'section' => 'character-traits', 'staff' => $staff->ulid,
        ]));
    }

    /* -------------------------------------------- at least four of them */

    public function test_a_quarter_written_in_needs_at_least_four_activities(): void
    {
        $staff = $this->officer();

        $this->save($staff, $this->quarter(2025, 1, 3))
            ->assertSessionHasErrors('activity.2025.1');

        // Nothing of a refused quarter is written.
        $this->assertSame(0, SelfReportActivity::count());
    }

    public function test_the_refusal_names_the_quarter_it_is_about(): void
    {
        $staff = $this->officer();

        $this->save($staff, $this->quarter(2024, 3, 1));

        $this->assertStringContainsString('3rd Quarter 2024',
            session('errors')->first('activity.2024.3'));
    }

    public function test_the_refusal_is_shown_against_the_quarter_it_is_about(): void
    {
        $staff = $this->officer();

        // Back to the section, which has to show what was typed and say
        // which quarter it would not take.
        $this->actingAs($this->actor())
            ->from(route('hr-appraisal', ['section' => 'self-reporting', 'staff' => $staff->ulid]))
            ->post(route('hr-appraisal.self-reporting'), array_merge([
                'staff' => $staff->ulid, 'section' => 'self-reporting',
            ], $this->quarter(2025, 2, 2)))
            ->assertRedirect(route('hr-appraisal', [
                'section' => 'self-reporting', 'staff' => $staff->ulid,
            ]));

        $this->followingRedirects();

        $this->actingAs($this->actor())
            ->get(route('hr-appraisal', ['section' => 'self-reporting', 'staff' => $staff->ulid]))
            ->assertOk()
            ->assertSee('Describe at least 4 activities for 2nd Quarter 2025')
            // And what was typed is still there to be added to.
            ->assertSee('Activity 1 of Q2 2025');
    }

    public function test_a_quarter_left_empty_is_not_asked_after(): void
    {
        // An officer cannot report on a quarter they have not reached.
        $staff = $this->officer();

        $this->save($staff, [
            'activity' => [
                2025 => [1 => $this->four('a'), 2 => ['', '', '', ''], 3 => []],
            ],
        ])->assertSessionHasNoErrors();

        $this->assertSame(4, SelfReportActivity::count());
    }

    public function test_blank_rows_among_the_four_do_not_count_towards_them(): void
    {
        $staff = $this->officer();

        $this->save($staff, [
            'activity' => [2025 => [1 => ['Something', '', 'Something else', '']]],
        ])->assertSessionHasErrors('activity.2025.1');
    }

    public function test_the_whole_section_may_be_left_alone(): void
    {
        $staff = $this->officer();

        $this->save($staff, [])->assertSessionHasNoErrors();

        $this->assertSame(0, SelfReportActivity::count());
    }

    /* --------------------------------------------- showing it back again */

    public function test_what_was_saved_is_shown_again(): void
    {
        $staff = $this->officer();

        $this->save($staff, $this->quarter(now()->year, 1, 4, ['Handled it well']));

        $this->section($staff)
            ->assertSee('Activity 1 of Q1 ' . now()->year)
            ->assertSee('Handled it well');
    }

    public function test_saving_again_replaces_what_was_there(): void
    {
        // What the form showed is the whole of it, so what it sends back is
        // the whole of it — which is how an activity is taken off.
        $staff = $this->officer();

        $this->save($staff, $this->quarter(2025, 1, 6));
        $this->save($staff, $this->quarter(2025, 1, 4));

        $this->assertSame(4, SelfReportActivity::count());
    }

    public function test_one_officers_report_is_not_anothers(): void
    {
        $staff = $this->officer();
        $other = BasicInformation::factory()->create(['surname' => 'Edem']);

        $this->save($staff, $this->quarter(2025, 1));
        $this->save($other, $this->quarter(2025, 2));

        // Saving one must not clear the other.
        $this->assertSame(4, SelfReportActivity::where('basic_information_ulid', $staff->ulid)->count());
        $this->assertSame(4, SelfReportActivity::where('basic_information_ulid', $other->ulid)->count());
    }

    /* -------------------------------------------- the years on the form */

    public function test_a_year_already_written_in_is_still_offered(): void
    {
        // Otherwise a report opened later would quietly drop what was
        // recorded before the three years it now covers.
        $staff = $this->officer();

        SelfReportActivity::create([
            'basic_information_ulid' => $staff->ulid,
            'year' => 2019, 'quarter' => 1, 'position' => 0,
            'activity' => 'Something long ago',
        ]);

        $this->assertContains(2019, SelfReportActivity::yearsFor($staff));

        $this->section($staff)
            ->assertSee('data-year-pane="2019"', false)
            ->assertSee('Something long ago');
    }

    public function test_the_years_read_in_order(): void
    {
        $staff = $this->officer();

        SelfReportActivity::create([
            'basic_information_ulid' => $staff->ulid,
            'year' => 2019, 'quarter' => 1, 'position' => 0, 'activity' => 'Long ago',
        ]);

        $years = SelfReportActivity::yearsFor($staff);

        $this->assertSame($years, collect($years)->sort()->values()->all());
        $this->assertSame(2019, $years[0]);
    }

    public function test_an_officer_with_nothing_on_file_is_offered_the_three_years(): void
    {
        $this->assertSame(range(now()->year - 2, now()->year),
                          SelfReportActivity::yearsFor($this->officer()));
    }

    /* ------------------------------------------------ nothing without one */

    public function test_no_officer_means_no_form(): void
    {
        $this->actingAs($this->actor())
            ->get(route('hr-appraisal', ['section' => 'self-reporting']))
            ->assertOk()
            ->assertSee('Choose the officer this report is on.')
            ->assertDontSee('Add Activity');
    }

    public function test_an_officer_cannot_report_on_somebody_else(): void
    {
        $officer = User::factory()->create([
            'basic_information_ulid' => BasicInformation::factory()->create()->ulid,
            'role' => 'User',
        ]);

        $this->actingAs($officer)->post(route('hr-appraisal.self-reporting'), [
            'staff' => $this->officer()->ulid,
            'section' => 'self-reporting',
        ])->assertForbidden();
    }
}
