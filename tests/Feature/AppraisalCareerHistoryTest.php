<?php

namespace Tests\Feature;

use App\Models\BasicInformation;
use App\Models\EduProgress;
use App\Models\Promotion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The Career History section: when the officer came in, when they were
 * confirmed, what they hold now, and what they have qualified in.
 */
class AppraisalCareerHistoryTest extends TestCase
{
    use RefreshDatabase;

    private ?User $actor = null;

    private function actor(): User
    {
        return $this->actor ??= User::factory()->create([
            'basic_information_ulid' => BasicInformation::factory()->create()->ulid,
            'role' => 'Administrator',
        ]);
    }

    private function officer(array $attributes = []): BasicInformation
    {
        return BasicInformation::factory()->create($attributes + [
            'surname' => 'Asuquo', 'firstname' => 'Bassey',
            'date_of_employment' => '2010-01-04',
            'date_of_confirmation' => '2012-01-04',
            'date_of_present_appointment' => null,
            'date_of_acting_appointment' => null,
        ]);
    }

    private function section(BasicInformation $staff)
    {
        return $this->actingAs($this->actor())->get(route('hr-appraisal', [
            'section' => 'career-history', 'staff' => $staff->ulid,
        ]));
    }

    private function save(BasicInformation $staff, array $overrides = [])
    {
        return $this->actingAs($this->actor())->post(route('hr-appraisal.career-history'),
            array_merge([
                'staff' => $staff->ulid,
                'section' => 'career-history',
                'date_of_employment' => '2010-01-04',
                'date_of_confirmation' => '2012-01-04',
            ], $overrides));
    }

    private function qualified(BasicInformation $staff, array ...$entries): void
    {
        EduProgress::create([
            'basic_information_ulid' => $staff->ulid,
            'educational_record' => $entries,
        ]);
    }

    /* ----------------------------------------------------- the dates */

    public function test_the_section_asks_for_the_four_dates(): void
    {
        $response = $this->section($this->officer());

        $response->assertOk();

        foreach (['Date of First Appointment', 'Date of Confirmation',
                  'Date of Present Appointment', 'Date of Acting Appointment'] as $label) {
            $response->assertSee($label);
        }
    }

    public function test_the_dates_on_record_are_filled_in(): void
    {
        $response = $this->section($this->officer());

        $response->assertSee('value="2010-01-04"', false);
        $response->assertSee('value="2012-01-04"', false);
    }

    public function test_the_present_appointment_opens_on_the_last_promotion(): void
    {
        // Nobody has said when it began, so the form offers the best guess
        // the record can make.
        $staff = $this->officer();

        Promotion::create([
            'basic_information_ulid' => $staff->ulid,
            'promotion_record' => [[
                'designation' => 'Senior Electoral Officer',
                'grade_level' => '12', 'step' => '4',
                'effective_from' => '2021-06-01',
            ]],
        ]);

        $this->assertSame('2021-06-01', $this->section($staff->fresh())
            ->viewData('presentAppointment'));
    }

    public function test_a_present_appointment_on_record_is_what_shows(): void
    {
        $staff = $this->officer(['date_of_present_appointment' => '2019-03-02']);

        $this->assertSame('2019-03-02', $this->section($staff)->viewData('presentAppointment'));
    }

    public function test_the_guess_is_marked_as_one_until_it_is_saved(): void
    {
        // Literal text in the template, so the apostrophe is not escaped —
        // unlike the table's empty message, which goes through Blade.
        $this->section($this->officer())
            ->assertSee("Taken from the officer's last promotion until it is saved.", false);
    }

    /* -------------------------------------------- saving the dates */

    public function test_the_dates_reach_the_staff_record(): void
    {
        $staff = $this->officer();

        $this->save($staff, [
            'date_of_present_appointment' => '2021-06-01',
            'date_of_acting_appointment' => '2024-02-01',
        ])->assertRedirect(route('hr-appraisal', [
            'section' => 'academic-qualifications', 'staff' => $staff->ulid,
        ]));

        $staff->refresh();

        $this->assertSame('2021-06-01', $staff->date_of_present_appointment);
        $this->assertSame('2024-02-01', $staff->date_of_acting_appointment);
    }

    public function test_an_officer_holding_no_acting_appointment_leaves_it_blank(): void
    {
        $staff = $this->officer();

        $this->save($staff, ['date_of_acting_appointment' => null])
            ->assertSessionHasNoErrors();

        $this->assertNull($staff->fresh()->date_of_acting_appointment);
    }

    public function test_an_officer_still_on_probation_has_no_confirmation_date(): void
    {
        $staff = $this->officer();

        $this->save($staff, ['date_of_confirmation' => null])->assertSessionHasNoErrors();

        $this->assertNull($staff->fresh()->date_of_confirmation);
    }

    public function test_the_first_appointment_is_required(): void
    {
        $this->save($this->officer(), ['date_of_employment' => ''])
            ->assertSessionHasErrors('date_of_employment');
    }

    public function test_nothing_may_predate_the_first_appointment(): void
    {
        $staff = $this->officer();

        $this->save($staff, [
            'date_of_confirmation' => '2009-01-01',
            'date_of_present_appointment' => '2009-01-01',
            'date_of_acting_appointment' => '2009-01-01',
        ])->assertSessionHasErrors([
            'date_of_confirmation',
            'date_of_present_appointment',
            'date_of_acting_appointment',
        ]);
    }

    public function test_correcting_the_first_appointment_moves_the_mode_of_retirement(): void
    {
        // The mode follows the dates, and the model keeps it in step.
        $staff = $this->officer(['date_of_birth' => '1960-01-01']);

        $this->save($staff, ['date_of_employment' => '1980-01-04']);

        // Thirty-five years from 1980 beats sixty years from 1960.
        $this->assertSame('years-of-service', $staff->fresh()->mode_of_retirement);
    }

    /* ------------------------------------------------ qualifications */

    public function test_the_qualifications_are_listed_with_their_year(): void
    {
        $staff = $this->officer();

        $this->qualified($staff, [
            'institution_type' => 'Tertiary',
            'institution_name' => 'University of Calabar',
            'start_date' => '2003-09-01',
            'end_date' => '2007-07-31',
            'qualification' => 'B.Sc Political Science',
        ]);

        $response = $this->section($staff->fresh());

        $response->assertSee('Qualifications (Educational / Professional / Technical)');
        $response->assertSee('B.Sc Political Science');
        $response->assertSee('University of Calabar');
        $response->assertSee('Tertiary');
        // The year comes off the day they left the institution.
        $response->assertSee('2007');
    }

    public function test_qualifications_read_oldest_first(): void
    {
        $staff = $this->officer();

        $this->qualified($staff,
            ['qualification' => 'M.Sc Public Administration', 'end_date' => '2015-07-31'],
            ['qualification' => 'B.Sc Political Science', 'end_date' => '2007-07-31'],
        );

        $order = $this->section($staff->fresh())->viewData('qualifications')
            ->pluck('qualification')->all();

        $this->assertSame(['B.Sc Political Science', 'M.Sc Public Administration'], $order);
    }

    public function test_qualifications_are_gathered_from_every_education_record(): void
    {
        $staff = $this->officer();

        $this->qualified($staff, ['qualification' => 'WASSCE', 'end_date' => '2001-06-30']);
        $this->qualified($staff, ['qualification' => 'B.Sc Political Science', 'end_date' => '2007-07-31']);

        $this->assertCount(2, $this->section($staff->fresh())->viewData('qualifications'));
    }

    public function test_an_officer_with_none_on_record_still_gets_a_row_to_write_in(): void
    {
        $response = $this->section($this->officer());

        $this->assertCount(0, $response->viewData('qualifications'));
        // There is always somewhere to write, whether or not anything is on
        // file — that is the point of capturing them here.
        $response->assertSee('name="qual_name[]"', false);
        $response->assertSee('Add Qualification');
    }

    public function test_the_rows_can_be_cloned(): void
    {
        $this->section($this->officer())
            ->assertSee('data-add-qualification', false)
            ->assertSee('data-qualification-row', false);
    }

    /* ------------------------------------------ capturing them here */

    public function test_qualifications_can_be_captured_where_there_were_none(): void
    {
        $staff = $this->officer();

        $this->save($staff, [
            'qual_type' => ['Educational', 'Professional'],
            'qual_name' => ['B.Sc Political Science', 'ICAN'],
            'qual_institution' => ['University of Calabar', 'Institute of Chartered Accountants'],
            'qual_year' => [2007, 2013],
        ])->assertSessionHasNoErrors();

        $captured = $staff->fresh()->qualifications();

        $this->assertCount(2, $captured);
        $this->assertSame('B.Sc Political Science', $captured[0]['qualification']);
        $this->assertSame('2007', (string) $captured[0]['year']);
        $this->assertSame('Professional', $captured[1]['type']);
    }

    public function test_a_row_with_no_qualification_named_is_ignored(): void
    {
        $staff = $this->officer();

        $this->save($staff, [
            'qual_name' => ['B.Sc Political Science', ''],
            'qual_institution' => ['University of Calabar', 'A school'],
            'qual_year' => [2007, 2010],
        ]);

        $this->assertCount(1, $staff->fresh()->qualifications());
    }

    public function test_what_is_sent_back_replaces_what_was_there(): void
    {
        $staff = $this->officer();
        $this->qualified($staff, ['qualification' => 'WASSCE', 'end_date' => '2001-06-30']);

        $this->save($staff, [
            'qual_name' => ['B.Sc Political Science'],
            'qual_year' => [2007],
        ]);

        $captured = $staff->fresh()->qualifications();

        $this->assertCount(1, $captured);
        $this->assertSame('B.Sc Political Science', $captured[0]['qualification']);
    }

    public function test_the_schooling_dates_are_carried_through_untouched(): void
    {
        // Editing a qualification here must not throw away what the wizard
        // recorded about the institution behind it.
        $staff = $this->officer();
        $this->qualified($staff, [
            'institution_type' => 'Tertiary',
            'institution_name' => 'University of Calabar',
            'start_date' => '2003-09-01',
            'end_date' => '2007-07-31',
            'qualification' => 'B.Sc Political Science',
        ]);

        $this->save($staff, [
            'qual_type' => ['Tertiary'],
            'qual_name' => ['B.Sc Political Science (Hons)'],
            'qual_institution' => ['University of Calabar'],
            'qual_year' => [2007],
            'qual_start' => ['2003-09-01'],
            'qual_end' => ['2007-07-31'],
        ]);

        $captured = $staff->fresh()->qualifications();

        $this->assertSame('2003-09-01', $captured[0]['start_date']);
        $this->assertSame('2007-07-31', $captured[0]['end_date']);
    }

    public function test_a_year_that_is_not_one_is_refused(): void
    {
        $staff = $this->officer();

        $this->save($staff, [
            'qual_name' => ['B.Sc Political Science'],
            'qual_year' => [1492],
        ])->assertSessionHasErrors('qual_year.0');
    }

    public function test_clearing_every_row_clears_the_record(): void
    {
        $staff = $this->officer();
        $this->qualified($staff, ['qualification' => 'WASSCE', 'end_date' => '2001-06-30']);

        $this->save($staff, ['qual_name' => ['']]);

        $this->assertCount(0, $staff->fresh()->qualifications());
    }

    public function test_a_year_recorded_here_is_what_shows(): void
    {
        // Rather than the one worked out from the schooling dates.
        $staff = $this->officer();

        $this->save($staff, [
            'qual_name' => ['B.Sc Political Science'],
            'qual_year' => [2008],
            'qual_end' => ['2007-07-31'],
        ]);

        $this->assertSame('2008', (string) $staff->fresh()->qualifications()[0]['year']);
    }

    public function test_an_entry_without_a_qualification_is_not_listed(): void
    {
        $staff = $this->officer();

        // A school with nothing obtained from it says nothing here.
        $this->qualified($staff, ['institution_name' => 'A school', 'end_date' => '2001-06-30']);

        $this->assertCount(0, $this->section($staff->fresh())->viewData('qualifications'));
    }

    /* ------------------------------------------- moving on from here */

    public function test_the_section_carries_on_to_academic_qualifications(): void
    {
        $this->section($this->officer())->assertSee('Continue to Academic Qualifications');
    }
}
