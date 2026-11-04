<?php

namespace Tests\Feature;

use App\Models\BasicInformation;
use App\Models\EduProgress;
use App\Models\User;
use App\Support\Appraisal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The Qualifications section: what the officer has qualified in, captured
 * here whether or not any are on record.
 */
class AppraisalQualificationsTest extends TestCase
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
        ]);
    }

    private function section(BasicInformation $staff)
    {
        return $this->actingAs($this->actor())->get(route('hr-appraisal', [
            'section' => 'qualifications', 'staff' => $staff->ulid,
        ]));
    }

    private function save(BasicInformation $staff, array $rows = [])
    {
        return $this->actingAs($this->actor())->post(route('hr-appraisal.qualifications'),
            array_merge([
                'staff' => $staff->ulid,
                'section' => 'qualifications',
            ], $rows));
    }

    private function qualified(BasicInformation $staff, array ...$entries): void
    {
        EduProgress::create([
            'basic_information_ulid' => $staff->ulid,
            'educational_record' => $entries,
        ]);
    }

    /* ------------------------------------------------ the section itself */

    public function test_the_section_is_called_qualifications(): void
    {
        // It was "Academic Qualifications", which was narrower than what it
        // holds: professional and technical ones go here too.
        $this->assertSame('Qualifications', Appraisal::SECTIONS['qualifications']);
        $this->assertArrayNotHasKey('academic-qualifications', Appraisal::SECTIONS);
    }

    public function test_it_follows_career_history(): void
    {
        $this->assertSame('qualifications', Appraisal::next('career-history'));
        $this->assertSame('Continue to Qualifications', Appraisal::continueLabel('career-history'));
    }

    public function test_career_history_no_longer_asks_for_them(): void
    {
        $staff = $this->officer();

        $this->actingAs($this->actor())->get(route('hr-appraisal', [
            'section' => 'career-history', 'staff' => $staff->ulid,
        ]))->assertDontSee('Add Qualification');
    }

    /* ------------------------------------------------- what it shows */

    public function test_the_qualifications_on_record_are_filled_in(): void
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

    public function test_an_entry_without_a_qualification_is_not_listed(): void
    {
        $staff = $this->officer();

        // A school with nothing obtained from it says nothing here.
        $this->qualified($staff, ['institution_name' => 'A school', 'end_date' => '2001-06-30']);

        $this->assertCount(0, $this->section($staff->fresh())->viewData('qualifications'));
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

    /* ------------------------------------------------ capturing them */

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

    public function test_saving_carries_on_to_the_next_section(): void
    {
        $staff = $this->officer();

        $this->save($staff, ['qual_name' => ['B.Sc Political Science']])
            ->assertRedirect(route('hr-appraisal', [
                'section' => 'self-reporting', 'staff' => $staff->ulid,
            ]));
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
}
