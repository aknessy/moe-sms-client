<?php

namespace Tests\Feature;

use App\Models\BasicInformation;
use App\Models\Promotion;
use App\Models\PromotionBrief;
use App\Models\Setting;
use App\Models\User;
use App\Support\PromotionDue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The brief put up for a year's promotion exercise.
 *
 * Who is due follows from the records: three years on a grade level, counted
 * from the last promotion, or from confirmation where there has never been
 * one. Recording the brief turns that calculation into a document, which goes
 * on saying what was put up after the officers on it have moved.
 */
class PromotionBriefTest extends TestCase
{
    use RefreshDatabase;

    private ?User $actor = null;

    /**
     * The clerk drawing the brief. Made once, and put on the top grade level
     * so their own record never turns up on the list they are drawing — the
     * factory gives everyone a date of employment, which would otherwise make
     * every actor a candidate.
     */
    private function actor(): User
    {
        return $this->actor ??= User::factory()->create([
            'basic_information_ulid' => BasicInformation::factory()
                ->create(['surname' => 'Clerk', 'grade_level' => 17])->ulid,
            'role' => 'Administrator',
        ]);
    }

    /** An officer whose last move was on a given day. */
    private function officer(array $attributes = []): BasicInformation
    {
        return BasicInformation::factory()->create($attributes + [
            'grade_level' => 9, 'step' => 3,
            'date_of_confirmation' => null, 'date_of_employment' => null,
        ]);
    }

    private function promoted(BasicInformation $staff, string ...$dates): void
    {
        Promotion::create([
            'basic_information_ulid' => $staff->ulid,
            'promotion_record' => array_map(fn ($date) => [
                'designation' => 'Administrative Officer',
                'grade_level' => '9', 'step' => '3',
                'effective_from' => $date,
            ], $dates),
        ]);
    }

    private function brief(int $year = 2026)
    {
        return $this->actingAs($this->actor())->get(route('promotion-brief', ['year' => $year]));
    }

    private function record(int $year = 2026)
    {
        return $this->actingAs($this->actor())
            ->post(route('promotion-brief.store'), ['year' => $year]);
    }

    /* ------------------------------------------- how long each serves */

    public static function gradeLevelsAndTheirYears(): array
    {
        return [
            'junior, lowest' => [1, 2],
            'junior, lower band top' => [6, 2],
            'senior, first level' => [7, 3],
            'senior, mid' => [12, 3],
            'senior, last level that may rise' => [16, 3],
            // Nowhere left to go.
            'the top grade level' => [17, null],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('gradeLevelsAndTheirYears')]
    public function test_a_grade_level_asks_its_own_number_of_years(int $gradeLevel, ?int $years): void
    {
        $staff = $this->officer(['grade_level' => $gradeLevel]);

        $this->assertSame($years, PromotionDue::yearsOnGradeFor($staff));
    }

    public function test_an_officer_with_no_grade_level_has_nothing_to_count_against(): void
    {
        $staff = $this->officer(['grade_level' => null, 'date_of_employment' => '2020-01-01']);

        $this->assertNull(PromotionDue::yearsOnGradeFor($staff));
        $this->assertNull(PromotionDue::dueOn($staff));
    }

    public function test_a_junior_officer_falls_due_in_two_years(): void
    {
        $staff = $this->officer(['grade_level' => 5, 'date_of_employment' => '2024-04-01']);

        $this->assertSame('2026-04-01', PromotionDue::dueOn($staff)->toDateString());
    }

    public function test_the_same_date_falls_differently_by_grade_level(): void
    {
        // The only difference between them is where they stand.
        $junior = $this->officer(['grade_level' => 5, 'date_of_employment' => '2024-04-01']);
        $senior = $this->officer(['grade_level' => 12, 'date_of_employment' => '2024-04-01']);

        $this->assertSame('2026-04-01', PromotionDue::dueOn($junior)->toDateString());
        $this->assertSame('2027-04-01', PromotionDue::dueOn($senior)->toDateString());
    }

    public function test_the_bands_read_as_the_page_says_them(): void
    {
        // Starts at the lowest grade level considered, which is 3 by default,
        // so the page does not promise to look at officers it will leave off.
        $this->assertSame('GL 3–6 after 2 years, GL 7–16 after 3 years',
            PromotionDue::describeBands());
    }

    /* ------------------------------- the lowest grade level considered */

    public function test_the_lowest_grade_level_considered_is_three_by_default(): void
    {
        $this->assertSame(3, PromotionDue::lowestGradeLevel());
    }

    public function test_an_officer_below_the_lowest_grade_level_is_not_considered(): void
    {
        $below = $this->officer(['grade_level' => 2, 'date_of_employment' => '2020-01-01']);
        $at = $this->officer(['grade_level' => 3, 'date_of_employment' => '2020-01-01']);

        $this->assertFalse(PromotionDue::canRise($below));
        $this->assertTrue(PromotionDue::canRise($at));
    }

    public function test_the_brief_leaves_off_those_below_the_line(): void
    {
        $this->officer(['surname' => 'TooJunior', 'grade_level' => 2,
                        'date_of_employment' => '2020-01-01']);
        $this->officer(['surname' => 'JustAbove', 'grade_level' => 3,
                        'date_of_employment' => '2020-01-01']);

        $surnames = PromotionDue::forYear(2026)->map(fn ($r) => $r['staff']->surname)->all();

        $this->assertNotContains('TooJunior', $surnames);
        $this->assertContains('JustAbove', $surnames);
    }

    public function test_lowering_the_line_brings_more_officers_in(): void
    {
        $this->officer(['surname' => 'OnOne', 'grade_level' => 1,
                        'date_of_employment' => '2020-01-01']);

        $this->assertNotContains('OnOne',
            PromotionDue::forYear(2026)->map(fn ($r) => $r['staff']->surname)->all());

        Setting::put('promotion.lowest_grade_level', '1');

        $this->assertContains('OnOne',
            PromotionDue::forYear(2026)->map(fn ($r) => $r['staff']->surname)->all());
    }

    public function test_raising_the_line_leaves_officers_out(): void
    {
        $this->officer(['surname' => 'OnTen', 'grade_level' => 10,
                        'date_of_employment' => '2020-01-01']);

        Setting::put('promotion.lowest_grade_level', '12');

        $this->assertNotContains('OnTen',
            PromotionDue::forYear(2026)->map(fn ($r) => $r['staff']->surname)->all());
    }

    public function test_the_page_says_where_the_line_falls(): void
    {
        Setting::put('promotion.lowest_grade_level', '7');

        $this->assertSame('GL 7–16 after 3 years', PromotionDue::describeBands());
    }

    /* ------------------------------------------- setting it in Settings */

    public function test_an_administrator_sets_the_lowest_grade_level(): void
    {
        $this->actingAs($this->actor())->post(route('config.rules'), [
            'promotion_lowest_grade_level' => 5,
        ])->assertRedirect(route('config'));

        $this->assertSame(5, PromotionDue::lowestGradeLevel());
    }

    public function test_the_lowest_grade_level_may_be_one(): void
    {
        $this->actingAs($this->actor())->post(route('config.rules'), [
            'promotion_lowest_grade_level' => 1,
        ])->assertRedirect(route('config'));

        $this->assertSame(1, PromotionDue::lowestGradeLevel());
    }

    public function test_below_one_is_refused(): void
    {
        $this->actingAs($this->actor())->post(route('config.rules'), [
            'promotion_lowest_grade_level' => 0,
        ])->assertSessionHasErrors('promotion_lowest_grade_level');

        $this->assertSame(3, PromotionDue::lowestGradeLevel());
    }

    public function test_a_level_off_the_scale_is_refused(): void
    {
        $this->actingAs($this->actor())->post(route('config.rules'), [
            'promotion_lowest_grade_level' => 18,
        ])->assertSessionHasErrors('promotion_lowest_grade_level');
    }

    public function test_the_settings_page_shows_what_it_is_set_to(): void
    {
        Setting::put('promotion.lowest_grade_level', '6');

        $this->actingAs($this->actor())->get(route('config'))
            ->assertOk()
            ->assertSee('Lowest Grade Level Considered')
            ->assertSee('value="6"', false);
    }

    public function test_an_officer_cannot_change_it(): void
    {
        $officer = User::factory()->create([
            'basic_information_ulid' => BasicInformation::factory()->create()->ulid,
            'role' => 'User',
        ]);

        $this->actingAs($officer)->post(route('config.rules'), [
            'promotion_lowest_grade_level' => 1,
        ])->assertForbidden();
    }

    /* ------------------------------------------------- who falls due */

    public function test_a_senior_officer_falls_due_three_years_after_their_last_promotion(): void
    {
        $staff = $this->officer();
        $this->promoted($staff, '2023-04-01');

        $this->assertSame('2026-04-01', PromotionDue::dueOn($staff->fresh())->toDateString());
    }

    public function test_the_latest_promotion_is_the_one_counted_from(): void
    {
        $staff = $this->officer();
        // Out of order on purpose: the most recent is what matters.
        $this->promoted($staff, '2019-01-01', '2023-04-01', '2021-06-01');

        $this->assertSame('2026-04-01', PromotionDue::dueOn($staff->fresh())->toDateString());
    }

    /** GL 9 throughout these, so three years is what is being counted. */
    public function test_an_officer_never_promoted_counts_from_confirmation(): void
    {
        $staff = $this->officer([
            'date_of_confirmation' => '2023-07-15',
            'date_of_employment' => '2021-01-01',
        ]);

        $this->assertSame('2026-07-15', PromotionDue::dueOn($staff)->toDateString());
    }

    public function test_an_unconfirmed_officer_counts_from_employment(): void
    {
        $staff = $this->officer(['date_of_employment' => '2023-02-01']);

        $this->assertSame('2026-02-01', PromotionDue::dueOn($staff)->toDateString());
    }

    public function test_an_officer_with_no_dates_at_all_never_falls_due(): void
    {
        $this->assertNull(PromotionDue::dueOn($this->officer()));
    }

    public function test_an_officer_on_the_top_grade_level_has_nowhere_to_rise(): void
    {
        $staff = $this->officer(['grade_level' => 17, 'date_of_employment' => '2010-01-01']);

        $this->assertFalse(PromotionDue::canRise($staff));
        $this->assertFalse(PromotionDue::forYear(2026)->contains(fn ($r) => $r['staff']->is($staff)));
    }

    public function test_a_retired_officer_is_not_on_the_brief(): void
    {
        $this->officer(['surname' => 'Gone', 'is_retired' => 1, 'date_of_employment' => '2020-01-01']);

        $this->assertFalse(PromotionDue::forYear(2026)
            ->contains(fn ($r) => $r['staff']->surname === 'Gone'));
    }

    /* ------------------------------------------- the year's listing */

    public function test_opening_the_year_stages_who_falls_due(): void
    {
        $this->officer(['surname' => 'Due', 'date_of_employment' => '2023-05-01']);
        $this->officer(['surname' => 'NotYet', 'date_of_employment' => '2025-05-01']);

        $response = $this->brief(2026);

        $response->assertSee('Due');
        $this->assertFalse($response->viewData('staged')
            ->contains(fn ($e) => $e->staff->surname === 'NotYet'));
    }

    public function test_someone_due_in_an_earlier_year_is_carried_along(): void
    {
        // Still waiting, so leaving them off would quietly lose them.
        $this->officer(['surname' => 'Waiting', 'date_of_employment' => '2020-01-01']);

        $staged = $this->brief(2026)->viewData('staged');

        $overdue = $staged->first(fn ($e) => $e->staff->surname === 'Waiting');

        $this->assertNotNull($overdue);
        $this->assertTrue($overdue->is_overdue);
    }

    public function test_the_longest_standing_are_listed_first(): void
    {
        $this->officer(['surname' => 'Recent', 'date_of_employment' => '2023-10-01']);
        $this->officer(['surname' => 'Older', 'date_of_employment' => '2021-01-01']);

        $order = $this->brief(2026)->viewData('staged')
            ->map(fn ($e) => $e->staff->surname)->all();

        $this->assertLessThan(array_search('Recent', $order), array_search('Older', $order));
    }

    /* ---------------------------------- the brief starts empty */

    public function test_the_brief_is_empty_until_the_list_is_added_to_it(): void
    {
        $this->officer(['surname' => 'One', 'date_of_employment' => '2023-01-01']);

        $response = $this->brief(2026);

        // Staged, but not yet put up.
        $this->assertSame(1, $response->viewData('staged')->count());
        $this->assertSame(0, $response->viewData('brief')->count());
        $response->assertSee('Nothing has been added to the 2026 brief yet.');
    }

    /* ----------------------------------- adding the list to the brief */

    public function test_adding_moves_everyone_staged_onto_the_brief(): void
    {
        $this->officer(['surname' => 'One', 'date_of_employment' => '2023-01-01']);
        $this->officer(['surname' => 'Two', 'date_of_employment' => '2022-01-01']);

        $this->record(2026)->assertRedirect(route('promotion-brief', ['year' => 2026]));

        $this->assertSame(2, PromotionBrief::forYear(2026)->onBrief()->count());
        $this->assertSame(0, PromotionBrief::forYear(2026)->staged()->count());
    }

    public function test_an_entry_keeps_the_standing_it_was_drawn_at(): void
    {
        $staff = $this->officer([
            'surname' => 'Eyo', 'grade_level' => 9, 'step' => 3,
            'designation' => 'Administrative Officer', 'date_of_employment' => '2023-01-01',
        ]);

        $this->record(2026);

        $entry = PromotionBrief::first();

        $this->assertSame(9, $entry->grade_level);
        $this->assertSame('2023-01-01', $entry->last_moved_on);
        $this->assertSame('2026-01-01', $entry->due_on);

        // The officer moves; the brief goes on saying what was put up.
        $staff->update(['grade_level' => 10, 'step' => 1]);

        $this->assertSame(9, $entry->fresh()->grade_level);
    }

    public function test_adding_twice_adds_nobody_twice(): void
    {
        $this->officer(['surname' => 'One', 'date_of_employment' => '2023-01-01']);

        $this->record(2026);
        $this->record(2026);

        $this->assertSame(1, PromotionBrief::forYear(2026)->onBrief()->count());
    }

    public function test_someone_on_the_brief_is_no_longer_staged(): void
    {
        $this->officer(['surname' => 'One', 'date_of_employment' => '2023-01-01']);

        $this->record(2026);

        $response = $this->brief(2026);

        $this->assertSame(0, $response->viewData('staged')->count());
        $this->assertSame(1, $response->viewData('brief')->count());
    }

    public function test_each_year_has_its_own_brief(): void
    {
        $this->officer(['surname' => 'One', 'date_of_employment' => '2023-01-01']);

        $this->record(2026);

        $this->assertSame(1, PromotionBrief::forYear(2026)->onBrief()->count());
        $this->assertSame(0, PromotionBrief::forYear(2027)->onBrief()->count());
    }

    /* ---------------------------------------- the button that adds them */

    public function test_the_add_button_is_there_with_officers_to_add(): void
    {
        $this->officer(['date_of_employment' => '2023-01-01']);

        $this->brief(2026)->assertSee('Add to Promotion Brief');
    }

    public function test_the_add_button_stays_once_everyone_is_on(): void
    {
        // It used to go missing the moment it had been used, which read as
        // the page losing a feature.
        $this->officer(['date_of_employment' => '2023-01-01']);
        $this->record(2026);

        $this->brief(2026)
            ->assertSee('Add to Promotion Brief')
            ->assertSee('Nobody is waiting to be added to the 2026 brief.');
    }

    public function test_the_add_button_is_there_with_nobody_due_at_all(): void
    {
        $this->brief(2026)->assertSee('Add to Promotion Brief');
    }

    /* ----------------------------------------------- printing the brief */

    public function test_a_brief_that_has_been_put_up_can_be_printed(): void
    {
        $this->officer(['date_of_employment' => '2023-01-01']);
        $this->record(2026);

        $this->brief(2026)
            ->assertSee('Print the Brief')
            ->assertSee('window.print()', false);
    }

    public function test_an_empty_brief_offers_nothing_to_print(): void
    {
        // Staged but not put up: there is nothing to print yet.
        $this->officer(['date_of_employment' => '2023-01-01']);

        $this->brief(2026)->assertDontSee('Print the Brief');
    }

    public function test_the_printed_sheet_carries_the_commands_letterhead(): void
    {
        Setting::putMany([
            'organisation.name' => 'Independent National Electoral Commission',
            'organisation.command' => 'Cross River State Office',
        ]);

        $this->officer(['date_of_employment' => '2023-01-01']);
        $this->record(2026);

        $response = $this->brief(2026);

        $response->assertSee('Independent National Electoral Commission');
        $response->assertSee('Cross River State Office');
        $response->assertSee('Promotion Brief');
        $response->assertSee('1 officer listed');
    }

    public function test_only_the_brief_goes_on_paper(): void
    {
        $this->officer(['date_of_employment' => '2023-01-01']);
        $this->record(2026);

        $html = $this->brief(2026)->getContent();

        // Everything used to work the list up stays on the screen.
        $this->assertStringContainsString('print-hide', $html);
    }

    public function test_the_printable_brief_carries_no_status_or_controls(): void
    {
        $this->officer(['date_of_employment' => '2023-01-01']);
        $this->record(2026);

        $html = $this->brief(2026)->getContent();

        // What came of each officer is settled in the section above, so the
        // brief itself is the list and nothing else. The printable table is
        // the last one on the page.
        $printable = substr($html, strpos($html, 'As it goes on paper') ?: 0);

        $this->assertStringNotContainsString('promotion-brief/entry', $printable);
        $this->assertStringNotContainsString('Remove', $printable);
        $this->assertStringNotContainsString('Deferred', $printable);
    }

    /* ------------------------------------------------ adding by hand */

    public function test_an_officer_can_be_added_by_hand(): void
    {
        // For the ones the rule misses.
        $staff = $this->officer(['surname' => 'Special', 'date_of_employment' => '2025-01-01']);

        $this->actingAs($this->actor())->post(route('promotion-brief.entry'), [
            'year' => 2026,
            'basic_information_ulid' => $staff->ulid,
            'remarks' => 'Acting in the post since January.',
        ])->assertRedirect(route('promotion-brief', ['year' => 2026]));

        $entry = PromotionBrief::first();

        $this->assertSame($staff->ulid, $entry->basic_information_ulid);
        $this->assertSame('Acting in the post since January.', $entry->remarks);
    }

    public function test_the_same_officer_cannot_be_added_twice(): void
    {
        // Not due, so nothing stages them automatically.
        $staff = $this->officer(['date_of_employment' => '2025-06-01']);

        $payload = ['year' => 2026, 'basic_information_ulid' => $staff->ulid];

        $this->actingAs($this->actor())->post(route('promotion-brief.entry'), $payload);
        $this->actingAs($this->actor())->post(route('promotion-brief.entry'), $payload)
            ->assertSessionHasErrors('basic_information_ulid');

        $this->assertSame(1, PromotionBrief::count());
    }

    public function test_an_officer_out_of_the_service_cannot_be_added(): void
    {
        $retired = $this->officer(['is_retired' => 1]);

        $this->actingAs($this->actor())->post(route('promotion-brief.entry'), [
            'year' => 2026,
            'basic_information_ulid' => $retired->ulid,
        ])->assertSessionHasErrors('basic_information_ulid');

        $this->assertSame(0, PromotionBrief::count());
    }

    /* ---------------------------------------------- what came of it */

    public function test_an_entry_can_be_marked_promoted(): void
    {
        $this->officer(['date_of_employment' => '2023-01-01']);
        $this->record(2026);

        $entry = PromotionBrief::first();

        $this->actingAs($this->actor())->post(route('promotion-brief.review', $entry->ulid), [
            'status' => 'promoted',
            'remarks' => 'Approved at the December exercise.',
        ])->assertRedirect(route('promotion-brief', ['year' => 2026]));

        $this->assertSame('promoted', $entry->fresh()->status);
    }

    public function test_an_entry_starts_pending(): void
    {
        $this->officer(['date_of_employment' => '2023-01-01']);
        $this->record(2026);

        $this->assertSame('pending', PromotionBrief::first()->status);
    }

    public function test_a_standing_that_is_not_one_is_refused(): void
    {
        $this->officer(['date_of_employment' => '2023-01-01']);
        $this->record(2026);

        $entry = PromotionBrief::first();

        $this->actingAs($this->actor())->post(route('promotion-brief.review', $entry->ulid), [
            'status' => 'anything',
        ])->assertSessionHasErrors('status');
    }

    public function test_an_entry_put_on_in_error_can_be_taken_off(): void
    {
        $this->officer(['date_of_employment' => '2023-01-01']);
        $this->record(2026);

        $entry = PromotionBrief::first();

        $this->actingAs($this->actor())->delete(route('promotion-brief.remove', $entry->ulid))
            ->assertRedirect(route('promotion-brief', ['year' => 2026]));

        $this->assertSame(0, PromotionBrief::forYear(2026)->onBrief()->count());
        $this->assertSame(0, PromotionBrief::forYear(2026)->staged()->count());
    }

    public function test_an_officer_taken_off_is_not_staged_again(): void
    {
        // The row is kept, marked, precisely so that reopening the year does
        // not undo the decision to leave them off.
        $this->officer(['surname' => 'Dropped', 'date_of_employment' => '2023-01-01']);

        $entry = $this->brief(2026)->viewData('staged')->first();

        $this->actingAs($this->actor())->delete(route('promotion-brief.remove', $entry->ulid));

        $this->assertSame(0, $this->brief(2026)->viewData('staged')->count());
    }

    public function test_an_officer_taken_off_can_be_put_back_by_hand(): void
    {
        $staff = $this->officer(['surname' => 'Dropped', 'date_of_employment' => '2023-01-01']);

        $entry = $this->brief(2026)->viewData('staged')->first();
        $this->actingAs($this->actor())->delete(route('promotion-brief.remove', $entry->ulid));

        $this->actingAs($this->actor())->post(route('promotion-brief.entry'), [
            'year' => 2026,
            'basic_information_ulid' => $staff->ulid,
        ])->assertRedirect(route('promotion-brief', ['year' => 2026]));

        $this->assertSame(1, $this->brief(2026)->viewData('staged')->count());
    }

    /* ------------------------------------------------- who may see it */

    public function test_an_officer_on_the_user_role_cannot_reach_it(): void
    {
        $officer = User::factory()->create([
            'basic_information_ulid' => BasicInformation::factory()->create()->ulid,
            'role' => 'User',
        ]);

        $this->actingAs($officer)->get(route('promotion-brief'))->assertForbidden();
    }

    public function test_it_is_on_the_registry_menu(): void
    {
        $registry = collect(\App\Support\Menu::full())->firstWhere('label', 'Registry');

        $this->assertContains('Promotion Brief', array_column($registry['children'], 'label'));
    }
}
