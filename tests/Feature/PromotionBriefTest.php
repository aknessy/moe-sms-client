<?php

namespace Tests\Feature;

use App\Models\BasicInformation;
use App\Models\Promotion;
use App\Models\PromotionBrief;
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

    /* ------------------------------------------------- who falls due */

    public function test_an_officer_falls_due_three_years_after_their_last_promotion(): void
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

    public function test_the_listing_shows_who_falls_due_that_year(): void
    {
        $this->officer(['surname' => 'Due', 'date_of_employment' => '2023-05-01']);
        $this->officer(['surname' => 'NotYet', 'date_of_employment' => '2025-05-01']);

        $response = $this->brief(2026);

        $response->assertSee('Due');
        $this->assertFalse($response->viewData('candidates')
            ->contains(fn ($r) => $r['staff']->surname === 'NotYet'));
    }

    public function test_someone_due_in_an_earlier_year_is_carried_along(): void
    {
        // Still waiting, so leaving them off would quietly lose them.
        $this->officer(['surname' => 'Waiting', 'date_of_employment' => '2020-01-01']);

        $candidates = $this->brief(2026)->viewData('candidates');

        $overdue = $candidates->first(fn ($r) => $r['staff']->surname === 'Waiting');

        $this->assertNotNull($overdue);
        $this->assertTrue($overdue['overdue']);
    }

    public function test_the_longest_standing_are_listed_first(): void
    {
        $this->officer(['surname' => 'Recent', 'date_of_employment' => '2023-10-01']);
        $this->officer(['surname' => 'Older', 'date_of_employment' => '2021-01-01']);

        $order = $this->brief(2026)->viewData('candidates')
            ->map(fn ($r) => $r['staff']->surname)->all();

        $this->assertLessThan(array_search('Recent', $order), array_search('Older', $order));
    }

    /* --------------------------------------------- recording the brief */

    public function test_recording_puts_everyone_due_on_the_brief(): void
    {
        $this->officer(['surname' => 'One', 'date_of_employment' => '2023-01-01']);
        $this->officer(['surname' => 'Two', 'date_of_employment' => '2022-01-01']);

        $this->record(2026)->assertRedirect(route('promotion-brief', ['year' => 2026]));

        $this->assertSame(2, PromotionBrief::forYear(2026)->count());
    }

    public function test_a_recorded_entry_keeps_the_standing_it_was_drawn_at(): void
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

    public function test_recording_twice_adds_nobody_twice(): void
    {
        $this->officer(['surname' => 'One', 'date_of_employment' => '2023-01-01']);

        $this->record(2026);
        $this->record(2026);

        $this->assertSame(1, PromotionBrief::forYear(2026)->count());
    }

    public function test_someone_already_recorded_is_no_longer_a_candidate(): void
    {
        $this->officer(['surname' => 'One', 'date_of_employment' => '2023-01-01']);

        $this->record(2026);

        // What is left is what recording again would add.
        $this->assertSame(0, $this->brief(2026)->viewData('candidates')->count());
        $this->assertSame(1, $this->brief(2026)->viewData('recorded')->count());
    }

    public function test_each_year_has_its_own_brief(): void
    {
        $this->officer(['surname' => 'One', 'date_of_employment' => '2023-01-01']);

        $this->record(2026);

        $this->assertSame(1, PromotionBrief::forYear(2026)->count());
        $this->assertSame(0, PromotionBrief::forYear(2027)->count());
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
        $staff = $this->officer(['date_of_employment' => '2023-01-01']);

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

        $this->assertSame(0, PromotionBrief::count());
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
