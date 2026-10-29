<?php

namespace Tests\Feature;

use App\Models\BasicInformation;
use App\Models\EduProgress;
use App\Models\Promotion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A newly employed officer has not been promoted yet, so the section may be
 * closed with nothing in it. A promotion that is recorded still needs all of
 * its fields.
 */
class PromotionSkipTest extends TestCase
{
    use RefreshDatabase;

    private BasicInformation $staff;

    protected function setUp(): void
    {
        parent::setUp();

        $this->staff = BasicInformation::factory()->create();

        // The wizard only opens this step once the one before it is done.
        EduProgress::create(['basic_information_ulid' => $this->staff->ulid,
                             'educational_record' => [['institution_name' => 'UNICAL']]]);

        $this->withSession(['staff_draft_ulid' => $this->staff->ulid])
            ->actingAs(User::factory()->create(['role' => 'Administrator']));
    }

    private function submit(array $payload)
    {
        return $this->post(route('create'), $payload + ['form-type' => 'promotion']);
    }

    private function promotion(): ?Promotion
    {
        return Promotion::where('basic_information_ulid', $this->staff->ulid)->first();
    }

    private function aPromotion(): array
    {
        return [
            'designation' => ['Senior Electoral Officer'],
            'grade-level' => ['12'],
            'step' => ['4'],
            'effective-from' => ['2021-01-01'],
        ];
    }

    /* -------------------------------------------------------- nothing yet */

    public function test_skipping_closes_the_section_with_nothing_in_it(): void
    {
        $this->submit(['action' => 'skip'])->assertRedirect(route('career'));

        $this->assertNotNull($this->promotion(), 'the section should still be recorded');
        $this->assertSame([], $this->promotion()->promotion_record);
    }

    public function test_skipping_says_so(): void
    {
        $this->submit(['action' => 'skip'])
            ->assertSessionHas('status', fn ($status) => str_contains($status, 'No promotion recorded'));
    }

    public function test_skipping_raises_no_errors_about_missing_fields(): void
    {
        $this->submit(['action' => 'skip'])->assertSessionHasNoErrors();
    }

    public function test_saving_an_entirely_blank_section_is_the_same_as_skipping(): void
    {
        $this->submit([
            'designation' => [''], 'grade-level' => [''],
            'step' => [''], 'effective-from' => [''],
        ])->assertRedirect(route('career'));

        $this->assertSame([], $this->promotion()->promotion_record);
    }

    public function test_submitting_no_fields_at_all_is_the_same_as_skipping(): void
    {
        $this->submit([])->assertRedirect(route('career'));

        $this->assertSame([], $this->promotion()->promotion_record);
    }

    public function test_skipping_discards_anything_half_typed(): void
    {
        $this->submit($this->aPromotion() + ['action' => 'skip']);

        $this->assertSame([], $this->promotion()->promotion_record);
    }

    public function test_skipping_closes_the_step_so_the_record_can_be_finished(): void
    {
        $this->submit(['action' => 'skip']);

        // Coming back to it is refused the same way a completed step is.
        $this->get(route('promotion'))->assertRedirect(route('career'));
    }

    /* --------------------------------------- a promotion that is recorded */

    public function test_a_promotion_is_recorded_as_before(): void
    {
        $this->submit($this->aPromotion())->assertRedirect(route('career'));

        $this->assertSame([[
            'designation' => 'Senior Electoral Officer',
            'grade_level' => '12',
            'step' => '4',
            'effective_from' => '2021-01-01',
        ]], $this->promotion()->promotion_record);
    }

    public function test_several_promotions_are_recorded(): void
    {
        $this->submit([
            'designation' => ['Electoral Officer', 'Senior Electoral Officer'],
            'grade-level' => ['10', '12'],
            'step' => ['2', '4'],
            'effective-from' => ['2017-01-01', '2021-01-01'],
        ]);

        $this->assertCount(2, $this->promotion()->promotion_record);
    }

    public function test_a_promotion_that_is_recorded_still_needs_all_of_its_fields(): void
    {
        // The section being skippable must not let a half-filled one through.
        $this->submit([
            'designation' => ['Senior Electoral Officer'], 'grade-level' => ['12'],
            'step' => [''], 'effective-from' => ['2021-01-01'],
        ])->assertSessionHasErrors('step.0');

        $this->assertNull($this->promotion());
    }

    public function test_a_blank_row_among_filled_ones_is_still_refused(): void
    {
        $this->submit([
            'designation' => ['Electoral Officer', ''],
            'grade-level' => ['10', ''],
            'step' => ['2', ''],
            'effective-from' => ['2017-01-01', ''],
        ])->assertSessionHasErrors();

        $this->assertNull($this->promotion());
    }

    /* ------------------------------------------------------------ the form */

    public function test_the_form_offers_a_way_to_skip(): void
    {
        $this->get(route('promotion'))->assertSee('Skip — no promotion to record', false);
    }

    public function test_the_form_still_offers_a_way_to_add_another_promotion(): void
    {
        $this->get(route('promotion'))->assertSee('Add Promotion');
    }
}
