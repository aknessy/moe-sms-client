<?php

namespace Tests\Feature;

use App\Models\BasicInformation;
use App\Models\Career;
use App\Models\EduProgress;
use App\Models\Promotion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The working career section records the posts an officer held before this
 * one. There may be several, or none at all — this may be their first job.
 */
class WorkingCareerTest extends TestCase
{
    use RefreshDatabase;

    private BasicInformation $staff;

    protected function setUp(): void
    {
        parent::setUp();

        $this->staff = BasicInformation::factory()->create();

        // The wizard only opens this step once the ones before it are done.
        EduProgress::create([
            'basic_information_ulid' => $this->staff->ulid,
            'educational_record' => [['institution_name' => 'University of Calabar']],
        ]);

        Promotion::create([
            'basic_information_ulid' => $this->staff->ulid,
            'promotion_record' => [['designation' => 'Admin Officer']],
        ]);

        $this->withSession(['staff_draft_ulid' => $this->staff->ulid])
            ->actingAs(User::factory()->create(['role' => 'Administrator']));
    }

    private function submit(array $payload)
    {
        return $this->post(route('create'), $payload + ['form-type' => 'work-career']);
    }

    /** One appointment, as parallel arrays the way the form posts them. */
    private function appointment(array $rows): array
    {
        return [
            'date-of-employment' => array_column($rows, 0),
            'rank' => array_column($rows, 1),
            'grade-level' => array_column($rows, 2),
            'ministerial-dept' => array_column($rows, 3),
            'mode-of-employment' => array_column($rows, 4),
            'ministerial-dept-address' => array_column($rows, 5),
        ];
    }

    private function careers()
    {
        return Career::where('basic_information_ulid', $this->staff->ulid)->get();
    }

    /* ------------------------------------------- one or many appointments */

    public function test_a_single_previous_appointment_is_recorded(): void
    {
        $this->submit($this->appointment([
            ['2008-01-15', 'Administrative Officer II', '8', 'Ministry of Education', 'Permanent', 'Calabar'],
        ]))->assertRedirect(route('leave-record'));

        $career = $this->careers()->sole();

        $this->assertSame('2008-01-15', $career->date_of_employement);
        $this->assertSame('Administrative Officer II', $career->rank);
        $this->assertSame(8, $career->grade_level);
        $this->assertSame('Ministry of Education', $career->ministerial_dept);
        $this->assertSame('Permanent', $career->mode_of_employment);
        $this->assertSame('Calabar', $career->address_of_ministerial_dept);
        $this->assertFalse($career->no_previous_employment);
    }

    public function test_several_appointments_are_each_recorded_in_turn(): void
    {
        $this->submit($this->appointment([
            ['2008-01-15', 'Administrative Officer II', '8', 'Ministry of Education', 'Permanent', 'Calabar'],
            ['2010-06-01', 'Administrative Officer I', '9', 'SUBEB', 'Contract', 'Calabar'],
            ['2011-09-01', 'Senior Admin Officer', '10', 'Ministry of Finance', 'Permanent', 'Calabar'],
        ]))->assertRedirect(route('leave-record'));

        $this->assertCount(3, $this->careers());
        $this->assertSame(
            ['Administrative Officer II', 'Administrative Officer I', 'Senior Admin Officer'],
            $this->careers()->pluck('rank')->all()
        );
    }

    public function test_each_appointment_keeps_its_own_detail(): void
    {
        $this->submit($this->appointment([
            ['2008-01-15', 'Officer II', '8', 'Ministry of Education', 'Permanent', 'Calabar'],
            ['2010-06-01', 'Officer I', '9', 'SUBEB', 'Contract', 'Ogoja'],
        ]));

        [$first, $second] = $this->careers()->all();

        $this->assertSame('Contract', $second->mode_of_employment);
        $this->assertSame('Permanent', $first->mode_of_employment);
        $this->assertSame('Ogoja', $second->address_of_ministerial_dept);
        $this->assertSame(9, $second->grade_level);
    }

    public function test_the_status_counts_the_appointments(): void
    {
        $this->submit($this->appointment([
            ['2008-01-15', 'Officer II', '8', 'Ministry of Education', 'Permanent', 'Calabar'],
            ['2010-06-01', 'Officer I', '9', 'SUBEB', 'Contract', 'Ogoja'],
        ]))->assertSessionHas('status', fn ($status) => str_contains($status, '2 previous appointments'));
    }

    /* ---------------------------------------------- a partial appointment */

    public function test_an_appointment_may_be_recorded_without_its_dates(): void
    {
        // An officer may remember the employer but not when they left.
        $this->submit($this->appointment([
            ['', 'Administrative Officer II', '', 'Ministry of Education', '', ''],
        ]))->assertRedirect(route('leave-record'));

        $career = $this->careers()->sole();

        $this->assertSame('Administrative Officer II', $career->rank);
        $this->assertNull($career->date_of_employement);
        $this->assertNull($career->grade_level);
        $this->assertFalse($career->no_previous_employment);
    }

    public function test_a_row_nobody_typed_into_is_not_an_appointment(): void
    {
        // Three blocks were added; only two were filled in.
        $this->submit($this->appointment([
            ['2008-01-15', 'Officer II', '8', 'Ministry of Education', 'Permanent', 'Calabar'],
            ['', '', '', '', '', ''],
            ['2010-06-01', 'Officer I', '9', 'SUBEB', 'Contract', 'Ogoja'],
        ]));

        $this->assertCount(2, $this->careers());
        $this->assertSame(['Officer II', 'Officer I'], $this->careers()->pluck('rank')->all());
    }

    public function test_a_date_that_is_not_a_date_is_refused(): void
    {
        $this->submit($this->appointment([
            ['not-a-date', 'Officer II', '8', 'Ministry of Education', 'Permanent', 'Calabar'],
        ]))->assertSessionHasErrors('date-of-employment.0');

        $this->assertCount(0, $this->careers());
    }

    /* ------------------------------------------------- no previous employer */

    public function test_skipping_records_that_there_was_no_previous_employment(): void
    {
        $this->submit(['action' => 'skip'])->assertRedirect(route('leave-record'));

        $career = $this->careers()->sole();

        $this->assertTrue($career->no_previous_employment);
        $this->assertNull($career->rank);
        $this->assertNull($career->date_of_employement);
    }

    public function test_skipping_says_so(): void
    {
        $this->submit(['action' => 'skip'])
            ->assertSessionHas('status', fn ($status) => str_contains($status, 'No previous employment'));
    }

    public function test_skipping_discards_anything_half_typed(): void
    {
        $this->submit($this->appointment([
            ['2008-01-15', 'Officer II', '8', 'Ministry of Education', 'Permanent', 'Calabar'],
        ]) + ['action' => 'skip']);

        $this->assertTrue($this->careers()->sole()->no_previous_employment);
    }

    public function test_saving_an_entirely_blank_section_is_the_same_as_skipping(): void
    {
        $this->submit($this->appointment([['', '', '', '', '', '']]))
            ->assertRedirect(route('leave-record'));

        $this->assertTrue($this->careers()->sole()->no_previous_employment);
    }

    public function test_submitting_no_fields_at_all_is_the_same_as_skipping(): void
    {
        $this->submit([])->assertRedirect(route('leave-record'));

        $this->assertTrue($this->careers()->sole()->no_previous_employment);
    }

    public function test_the_officers_who_held_no_post_before_can_be_found(): void
    {
        $this->submit(['action' => 'skip']);

        $this->assertTrue(Career::none()->where('basic_information_ulid', $this->staff->ulid)->exists());
    }

    /* ------------------------------------------------ the step is closed */

    public function test_skipping_closes_the_step_so_the_record_can_be_finished(): void
    {
        $this->submit(['action' => 'skip']);

        // Coming back to it is refused the same way a completed step is.
        $this->get(route('career'))->assertRedirect(route('leave-record'));
    }

    public function test_the_step_cannot_be_reached_without_a_draft(): void
    {
        $this->withSession(['staff_draft_ulid' => null])
            ->post(route('create'), ['form-type' => 'work-career', 'action' => 'skip'])
            ->assertRedirect(route('basic-info'));

        $this->assertCount(0, $this->careers());
    }

    /* ------------------------------------------------------------ the form */

    public function test_the_form_offers_a_way_to_add_another_appointment(): void
    {
        $response = $this->get(route('career'));

        $response->assertOk();
        $response->assertSee('addCareer', false);
        $response->assertSee('Add Career');
    }

    public function test_the_form_offers_a_way_to_skip(): void
    {
        $this->get(route('career'))->assertSee('Skip — no previous employment', false);
    }

    public function test_every_field_repeats(): void
    {
        $response = $this->get(route('career'));

        foreach (['date-of-employment[]', 'rank[]', 'grade-level[]',
                  'ministerial-dept[]', 'mode-of-employment[]', 'ministerial-dept-address[]'] as $field) {
            $response->assertSee('name="' . $field . '"', false);
        }
    }

    public function test_the_ministry_placeholder_carries_no_value(): void
    {
        // It shipped as <option value="brazil">, which posted "brazil" as the
        // ministry for anyone who left the select alone.
        $this->get(route('career'))->assertDontSee('brazil', false);
    }
}
