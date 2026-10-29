<?php

namespace Tests\Feature;

use App\Models\BasicInformation;
use App\Models\Career;
use App\Models\EduProgress;
use App\Models\Leave;
use App\Models\Promotion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A newly employed officer has taken no leave yet, so the section may be
 * closed with nothing in it. A leave that is recorded still needs all of its
 * fields.
 */
class LeaveRecordSkipTest extends TestCase
{
    use RefreshDatabase;

    private BasicInformation $staff;

    protected function setUp(): void
    {
        parent::setUp();

        $this->staff = BasicInformation::factory()->create();

        // The wizard only opens this step once the ones before it are done.
        EduProgress::create(['basic_information_ulid' => $this->staff->ulid,
                             'educational_record' => [['institution_name' => 'UNICAL']]]);
        Promotion::create(['basic_information_ulid' => $this->staff->ulid,
                           'promotion_record' => [['designation' => 'Admin Officer']]]);
        Career::create(['basic_information_ulid' => $this->staff->ulid,
                        'no_previous_employment' => true]);

        $this->withSession(['staff_draft_ulid' => $this->staff->ulid])
            ->actingAs(User::factory()->create(['role' => 'Administrator']));
    }

    private function submit(array $payload)
    {
        return $this->post(route('create'), $payload + ['form-type' => 'leave']);
    }

    private function leave(): ?Leave
    {
        return Leave::where('basic_information_ulid', $this->staff->ulid)->first();
    }

    private function aLeave(): array
    {
        return [
            'leave-type' => ['Annual Leave'],
            'effective-from' => ['2024-06-01'],
            'expires-on' => ['2024-06-21'],
            'leave-duration' => ['21'],
        ];
    }

    /* -------------------------------------------------------- nothing yet */

    public function test_skipping_closes_the_section_with_nothing_in_it(): void
    {
        $this->submit(['action' => 'skip'])->assertRedirect(route('referee'));

        $this->assertNotNull($this->leave(), 'the section should still be recorded');
        $this->assertSame([], $this->leave()->leave_record);
    }

    public function test_skipping_says_so(): void
    {
        $this->submit(['action' => 'skip'])
            ->assertSessionHas('status', fn ($status) => str_contains($status, 'No leave recorded'));
    }

    public function test_skipping_raises_no_errors_about_missing_fields(): void
    {
        $this->submit(['action' => 'skip'])->assertSessionHasNoErrors();
    }

    public function test_saving_an_entirely_blank_section_is_the_same_as_skipping(): void
    {
        $this->submit([
            'leave-type' => [''], 'effective-from' => [''],
            'expires-on' => [''], 'leave-duration' => [''],
        ])->assertRedirect(route('referee'));

        $this->assertSame([], $this->leave()->leave_record);
    }

    public function test_submitting_no_fields_at_all_is_the_same_as_skipping(): void
    {
        $this->submit([])->assertRedirect(route('referee'));

        $this->assertSame([], $this->leave()->leave_record);
    }

    public function test_skipping_discards_anything_half_typed(): void
    {
        $this->submit($this->aLeave() + ['action' => 'skip']);

        $this->assertSame([], $this->leave()->leave_record);
    }

    public function test_skipping_closes_the_step_so_the_record_can_be_finished(): void
    {
        $this->submit(['action' => 'skip']);

        // Coming back to it is refused the same way a completed step is.
        $this->get(route('leave-record'))->assertRedirect(route('referee'));
    }

    /* ------------------------------------------- a leave that is recorded */

    public function test_a_leave_is_recorded_as_before(): void
    {
        $this->submit($this->aLeave())->assertRedirect(route('referee'));

        $this->assertSame([[
            'leave_type' => 'Annual Leave',
            'effective_from' => '2024-06-01',
            'expires_on' => '2024-06-21',
            'duration' => '21',
        ]], $this->leave()->leave_record);
    }

    public function test_several_leaves_are_recorded(): void
    {
        $this->submit([
            'leave-type' => ['Annual Leave', 'Study Leave'],
            'effective-from' => ['2024-06-01', '2025-01-10'],
            'expires-on' => ['2024-06-21', '2025-03-10'],
            'leave-duration' => ['21', '60'],
        ]);

        $this->assertCount(2, $this->leave()->leave_record);
    }

    public function test_a_leave_that_is_recorded_still_needs_all_of_its_fields(): void
    {
        // The section being skippable must not let a half-filled one through.
        $this->submit([
            'leave-type' => ['Annual Leave'], 'effective-from' => ['2024-06-01'],
            'expires-on' => [''], 'leave-duration' => ['21'],
        ])->assertSessionHasErrors('expires-on.0');

        $this->assertNull($this->leave());
    }

    public function test_a_blank_row_among_filled_ones_is_still_refused(): void
    {
        $this->submit([
            'leave-type' => ['Annual Leave', ''],
            'effective-from' => ['2024-06-01', ''],
            'expires-on' => ['2024-06-21', ''],
            'leave-duration' => ['21', ''],
        ])->assertSessionHasErrors();

        $this->assertNull($this->leave());
    }

    /* ---------------------------------- the sections that may not be empty */

    public function test_a_section_that_may_not_be_empty_cannot_be_skipped(): void
    {
        $this->submit(['action' => 'skip']);

        // Referees are not optional, so skipping it is refused.
        $this->post(route('create'), ['form-type' => 'referees', 'action' => 'skip'])
            ->assertSessionHasErrors();

        $this->assertSame(0, \App\Models\Referee::where('basic_information_ulid', $this->staff->ulid)->count());
    }

    /* ------------------------------------------------------------ the form */

    public function test_the_form_offers_a_way_to_skip(): void
    {
        $this->get(route('leave-record'))->assertSee('Skip — no leave to record', false);
    }

    public function test_the_form_still_offers_a_way_to_add_another_leave(): void
    {
        $this->get(route('leave-record'))->assertSee('Add Leave');
    }
}
