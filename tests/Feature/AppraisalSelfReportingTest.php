<?php

namespace Tests\Feature;

use App\Models\BasicInformation;
use App\Models\Lga;
use App\Models\Posting;
use App\Models\SelfReportActivity;
use App\Models\State;
use App\Models\User;
use App\Support\ReportingAuthority;
use App\Support\SelfReportWindow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The Self Reporting section of the appraisal: what the officer took part in,
 * quarter by quarter, and what the reporting officer made of each of it.
 *
 * The years are the ones the promotion brief counts before calling the
 * officer due — two for a junior officer, three from GL 7 — each over its
 * first three quarters, and a quarter written in is answered with at least
 * four activities.
 */
class AppraisalSelfReportingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The window is counted back from the present year, so the present year
     * is held still: a test that passes this November and fails next is no
     * test at all.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-11-07');
    }

    private function actor(): User
    {
        return User::factory()->create([
            'basic_information_ulid' => BasicInformation::factory()->create()->ulid,
            'role' => 'Administrator',
        ]);
    }

    /**
     * A senior officer, who moves up in three years. Confirmed on a known
     * day, so the day they fall due is known and the window ends with it.
     */
    private function officer(array $attributes = []): BasicInformation
    {
        return BasicInformation::factory()->create($attributes + [
            'surname' => 'Asuquo', 'firstname' => 'Bassey',
            'grade_level' => 9, 'step' => 4,
            'department' => 'Department of Electoral Operations',
            'date_of_employment' => '2015-01-05',
            'date_of_confirmation' => '2023-06-01',
        ]);
    }

    /** A junior officer, who moves up in two and so reports over two. */
    private function juniorOfficer(array $attributes = []): BasicInformation
    {
        return $this->officer($attributes + ['grade_level' => 4]);
    }

    /** The years the section covers for this officer, earliest first. */
    private function windowOf(BasicInformation $staff): array
    {
        return SelfReportWindow::for($staff)->years;
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

    public function test_a_senior_officer_reports_over_the_last_three_years(): void
    {
        // GL 9 moves up after three years, so the report covers the last
        // three — 2024 to 2026.
        $staff = $this->officer();

        $this->assertSame([2024, 2025, 2026], $this->windowOf($staff));

        $response = $this->section($staff);

        foreach ([2024, 2025, 2026] as $year) {
            $response->assertSee('data-year-pane="' . $year . '"', false);
        }
    }

    public function test_a_junior_officer_reports_over_the_last_two(): void
    {
        $staff = $this->juniorOfficer();

        $this->assertSame([2025, 2026], $this->windowOf($staff));

        $this->section($staff)
            ->assertSee('data-year-pane="2025"', false)
            ->assertSee('data-year-pane="2026"', false)
            ->assertDontSee('data-year-pane="2024"', false);
    }

    public function test_the_window_is_the_last_of_them_not_the_ones_before_the_due_date(): void
    {
        // An officer overdue since 2011 is promoted on what they have been
        // doing since, so the report is on the last three years and not on
        // the three that ran up to the day they fell due.
        $staff = $this->officer([
            'date_of_employment' => '2005-07-07',
            'date_of_confirmation' => '2008-07-07',
        ]);

        $this->assertSame([2024, 2025, 2026], $this->windowOf($staff));

        $this->section($staff)
            ->assertSee('Promotion fell due on 7 July 2011')
            ->assertDontSee('data-year-pane="2011"', false);
    }

    public function test_an_officer_not_yet_due_reads_as_falling_due(): void
    {
        $staff = $this->officer(['date_of_confirmation' => '2025-06-01']);

        $this->section($staff)->assertSee('Promotion falls due on 1 June 2028');
    }

    public function test_the_window_is_the_span_the_promotion_brief_counts(): void
    {
        // The same rule decides both: whatever the brief waits before calling
        // an officer due is what the appraisal reports over.
        foreach ([4 => 2, 6 => 2, 7 => 3, 9 => 3, 16 => 3] as $grade => $years) {
            $staff = $this->officer(['grade_level' => $grade]);

            $this->assertCount($years, $this->windowOf($staff),
                "GL {$grade} should report over {$years} years");
            $this->assertSame($years, SelfReportWindow::for($staff)->length);
        }
    }

    public function test_the_section_says_which_years_and_why(): void
    {
        $this->section($this->officer())
            ->assertSee('GL 09 moves up after 3 years, so this covers the last 3, 2024 to 2026.');

        $this->section($this->juniorOfficer())
            ->assertSee('GL 04 moves up after 2 years, so this covers the last 2, 2025 to 2026.');
    }

    public function test_each_year_is_broken_into_its_first_three_quarters(): void
    {
        $staff = $this->officer();
        $response = $this->section($staff);

        foreach ($this->windowOf($staff) as $year) {
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

        // Three years of three quarters, four rows apiece, each row an
        // activity and the reporting officer's comment on it.
        $this->assertSame(36, substr_count($html, 'data-activity-row>'));
        $this->assertSame(36, substr_count($html, 'name="activity['));
        $this->assertSame(36, substr_count($html, 'name="comment['));
    }

    public function test_a_junior_officer_is_asked_for_six_quarters_not_nine(): void
    {
        $html = $this->section($this->juniorOfficer())->getContent();

        $this->assertSame(24, substr_count($html, 'data-activity-row>'));
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

        $this->save($staff, $this->quarter(2025, 1, 4, ['Handled it well']));

        $this->section($staff)
            ->assertSee('Activity 1 of Q1 2025')
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
        $other = $this->officer(['surname' => 'Edem']);

        $this->save($staff, $this->quarter(2025, 1));
        $this->save($other, $this->quarter(2025, 2));

        // Saving one must not clear the other.
        $this->assertSame(4, SelfReportActivity::where('basic_information_ulid', $staff->ulid)->count());
        $this->assertSame(4, SelfReportActivity::where('basic_information_ulid', $other->ulid)->count());
    }

    /* -------------------------------------------- the years on the form */

    public function test_a_year_already_written_in_is_still_offered(): void
    {
        // A promotion recorded late moves the window; what was reported on
        // before it must not quietly drop off the end.
        $staff = $this->officer();

        $this->writeIn($staff, 2019, 'Something long ago');

        $this->assertContains(2019, $this->windowOf($staff));

        $this->section($staff)
            ->assertSee('data-year-pane="2019"', false)
            ->assertSee('Something long ago')
            ->assertSee('1 earlier year is kept alongside it');
    }

    public function test_the_years_read_in_order(): void
    {
        $staff = $this->officer();

        $this->writeIn($staff, 2019, 'Long ago');

        $years = $this->windowOf($staff);

        $this->assertSame([2019, 2024, 2025, 2026], $years);
    }

    public function test_a_carried_year_may_be_reported_on(): void
    {
        // It is on the form, so it has to be one the form will take back.
        $staff = $this->officer();

        $this->writeIn($staff, 2019, 'Something long ago');

        $this->save($staff, $this->quarter(2019, 2))->assertSessionHasNoErrors();

        $this->assertSame(4, SelfReportActivity::where('year', 2019)->count());
    }

    public function test_a_year_the_report_does_not_cover_is_refused(): void
    {
        // The section only ever offered the window, so anything else came
        // from somewhere other than the form.
        $staff = $this->officer();

        $this->save($staff, $this->quarter(2008, 1))
            ->assertSessionHasErrors('activity.2008.1');

        $this->assertSame(0, SelfReportActivity::count());
    }

    public function test_a_fourth_quarter_is_refused(): void
    {
        $staff = $this->officer();

        $this->save($staff, $this->quarter(2025, 4))
            ->assertSessionHasErrors('activity.2025.4');
    }

    private function writeIn(BasicInformation $staff, int $year, string $activity): void
    {
        SelfReportActivity::create([
            'basic_information_ulid' => $staff->ulid,
            'year' => $year, 'quarter' => 1, 'position' => 0, 'activity' => $activity,
        ]);
    }

    /* ----------------------------------- where the record says nothing */

    public function test_an_officer_who_has_never_moved_reports_to_the_present_year(): void
    {
        // Nothing on record says when they last stood still, so there is no
        // promotion date to end the window with.
        $staff = BasicInformation::factory()->create([
            'grade_level' => 9,
            'date_of_employment' => null, 'date_of_confirmation' => null,
        ]);

        $this->assertSame(range(now()->year - 2, now()->year), $this->windowOf($staff));

        $this->section($staff)->assertSee('Nothing on record says when this officer last moved');
    }

    public function test_an_officer_at_the_top_of_the_scale_reports_to_the_present_year(): void
    {
        // GL 17 has nowhere to rise to, so no promotion anchors the window.
        $staff = $this->officer(['grade_level' => 17]);

        $this->assertSame(range(now()->year - 2, now()->year), $this->windowOf($staff));

        $this->section($staff)->assertSee('GL 17 has nowhere to rise to');
    }

    public function test_an_officer_with_no_grade_level_reports_to_the_present_year(): void
    {
        $staff = $this->officer(['grade_level' => null]);

        $this->assertSame(range(now()->year - 2, now()->year), $this->windowOf($staff));

        $this->section($staff)->assertSee('No grade level is on record');
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

    /* -------------------------------------- who writes the officer's comments */

    /**
     * An account with no staff record behind it, for the comment authority.
     *
     * @param array<string, mixed> $staffAttributes
     */
    private function account(string $role, array $staffAttributes = []): User
    {
        return User::factory()->create([
            'basic_information_ulid' => BasicInformation::factory()
                ->create($staffAttributes)->ulid,
            'role' => $role,
        ]);
    }

    /** The comment fields of the rendered section, disabled or not. */
    private function renderedComments(User $viewer, BasicInformation $staff): string
    {
        return $this->actingAs($viewer)
            ->get(route('hr-appraisal', ['section' => 'self-reporting', 'staff' => $staff->ulid]))
            ->getContent();
    }

    private const COMMENT_BOX = '/name="comment\[[0-9]+\]\[[0-9]+\]\[\]"[^>]*disabled/';

    public function test_a_staff_account_may_not_write_the_comments(): void
    {
        // The section is held open to the whole menu for every other role;
        // for the `User` role it is not open at all, and where it ever is,
        // the authority below is what keeps the comments out of their hands.
        $staff = $this->officer();

        $this->assertFalse(
            ReportingAuthority::mayCommentOn($this->account('User'), $staff));
    }

    /** Post an officer out to a local government, away from the state office. */
    private function postAway(BasicInformation $staff): Lga
    {
        $state = State::where('name', config('posting.default_state'))->firstOrFail();
        $lga = Lga::where('state_id', $state->id)
            ->where('lga_name', '!=', config('posting.default_lga'))
            ->firstOrFail();

        Posting::create([
            'basic_information_ulid' => $staff->ulid,
            'posting_type' => 'deployment',
            'to_lga' => $lga->id,
            'effective_date' => '2025-01-06',
            'deployed_as' => 'Registration Officer',
        ]);

        return $lga;
    }

    public function test_an_administrator_writes_the_comments(): void
    {
        $staff = $this->officer();
        $administrator = $this->actor();

        $this->assertTrue(ReportingAuthority::mayCommentOn($administrator, $staff));

        // Their boxes are open, and the officer is not told to keep out.
        $html = $this->renderedComments($administrator, $staff);

        $this->assertDoesNotMatchRegularExpression(self::COMMENT_BOX, $html);
        $this->assertStringNotContainsString('The reporting officer writes here', $html);
    }

    public function test_the_electoral_officer_of_the_officers_lga_writes_the_comments(): void
    {
        $staff = $this->officer();
        $lga = $this->postAway($staff);

        // Accounted on an ordinary role — it is the desk they are posted to
        // that put the comments within reach, not the account's own role.
        $electoralOfficer = $this->account('Registry Officer',
            ['designation' => 'Electoral Officer']);

        Posting::create([
            'basic_information_ulid' => $electoralOfficer->staff->ulid,
            'posting_type' => 'deployment',
            'to_lga' => $lga->id,
            'effective_date' => '2025-01-06',
            'deployed_as' => 'Electoral Officer',
        ]);

        $this->assertTrue(ReportingAuthority::mayCommentOn($electoralOfficer, $staff));

        $this->assertDoesNotMatchRegularExpression(
            self::COMMENT_BOX,
            $this->renderedComments($electoralOfficer, $staff),
        );
    }

    public function test_the_electoral_officer_of_another_lga_reads_on(): void
    {
        $staff = $this->officer();
        $lga = $this->postAway($staff);

        $elsewhere = State::where('name', config('posting.default_state'))->firstOrFail()
            ->lgas()
            ->where('lga_name', '!=', config('posting.default_lga'))
            ->where('lga_name', '!=', $lga->lga_name)
            ->firstOrFail();

        $officerDownTheRoad = $this->account('Registry Officer',
            ['designation' => 'Electoral Officer']);

        Posting::create([
            'basic_information_ulid' => $officerDownTheRoad->staff->ulid,
            'posting_type' => 'deployment',
            'to_lga' => $elsewhere->id,
            'effective_date' => '2025-01-06',
            'deployed_as' => 'Electoral Officer',
        ]);

        $this->assertFalse(ReportingAuthority::mayCommentOn($officerDownTheRoad, $staff));

        $this->assertMatchesRegularExpression(
            self::COMMENT_BOX,
            $this->renderedComments($officerDownTheRoad, $staff),
        );
    }

    public function test_the_head_of_the_officers_department_writes_the_comments(): void
    {
        // The officer sits in Electoral Operations, headed by the HOD EOPs
        // role.
        $staff = $this->officer();

        $head = $this->account('HOD EOPs');

        $this->assertTrue(ReportingAuthority::mayCommentOn($head, $staff));

        $this->assertDoesNotMatchRegularExpression(
            self::COMMENT_BOX,
            $this->renderedComments($head, $staff),
        );
    }

    public function test_the_head_of_another_department_reads_on(): void
    {
        $staff = $this->officer();

        $head = $this->account('HOD ICT');

        $this->assertFalse(ReportingAuthority::mayCommentOn($head, $staff));

        $this->assertMatchesRegularExpression(
            self::COMMENT_BOX,
            $this->renderedComments($head, $staff),
        );
    }

    public function test_an_officer_without_a_viewer_means_no_comments(): void
    {
        $this->assertFalse(ReportingAuthority::mayCommentOn(null, $this->officer()));
        $this->assertFalse(ReportingAuthority::mayCommentOn($this->actor(), null));
    }

    /* ------------------------------- sending what one may not write */

    public function test_an_account_may_not_send_the_comments_it_cannot_write(): void
    {
        // A disabled field on the form would be nothing more than a
        // suggestion, so the post is held to the same authority the page is.
        // A head of one department can reach the page and still may not comment
        // on the report of an officer of another.
        $staff = $this->officer();

        $this->actingAs($this->account('HOD ICT'))
            ->post(route('hr-appraisal.self-reporting'), [
                'staff' => $staff->ulid,
                'section' => 'self-reporting',
                'activity' => [now()->year => [1 => $this->four('a')]],
                'comment' => [now()->year => [1 => ['I did not head this', '', '', '']]],
            ])->assertForbidden();

        $this->assertSame(0, SelfReportActivity::count());
    }

    public function test_an_account_may_send_its_activities_without_comments(): void
    {
        // Describing what they did is the officer's own job; it is the
        // commenting beside it that is bounded by who is reporting on them.
        $staff = $this->officer();

        $this->actingAs($this->account('HOD EOPs'))
            ->post(route('hr-appraisal.self-reporting'), [
                'staff' => $staff->ulid,
                'section' => 'self-reporting',
                'activity' => [now()->year => [1 => $this->four('a')]],
            ])->assertSessionHasNoErrors();

        $this->assertSame(4, SelfReportActivity::where(
            'basic_information_ulid', $staff->ulid,
        )->whereNull('comment')->count());
    }

    public function test_the_administrators_comments_arrive(): void
    {
        $staff = $this->officer();

        $this->save($staff, $this->quarter(now()->year, 1, 4, ['Handled it well']));

        $this->assertSame('Handled it well', SelfReportActivity::where(
            'basic_information_ulid', $staff->ulid,
        )->orderBy('position')->first()->comment);
    }
}
