<?php

namespace Tests\Feature;

use App\Models\BasicInformation;
use App\Models\Department;
use App\Models\Lga;
use App\Models\Posting;
use App\Models\State;
use App\Models\Unit;
use App\Models\User;
use App\Support\Appraisal;
use App\Support\PostingContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The Bio-data section of the appraisal: who is being reported on, drawn
 * from the staff record rather than asked for again.
 */
class AppraisalBioDataTest extends TestCase
{
    use RefreshDatabase;

    private function actor(): User
    {
        return User::factory()->create([
            'basic_information_ulid' => BasicInformation::factory()->create()->ulid,
            'role' => 'Administrator',
        ]);
    }

    private function officer(array $attributes = []): BasicInformation
    {
        $crossRiver = State::where('name', 'Cross River')->firstOrFail();

        $this->unitsOfOperations();

        return BasicInformation::factory()->create($attributes + [
            'surname' => 'Asuquo', 'firstname' => 'Bassey', 'othernames' => 'Eno',
            'date_of_birth' => '1985-04-12',
            'state_of_origin' => $crossRiver->id,
            'grade_level' => 9, 'step' => 4,
            'department' => 'Department of Electoral Operations',
            'division' => 'Calabar HQ',
            'unit' => 'Voter Registration Unit',
        ]);
    }

    /**
     * Two desks in Electoral Operations. A unit is only offered beside the
     * department it belongs to, so there has to be one to pick.
     */
    private function unitsOfOperations(): Department
    {
        $department = Department::where('department', 'Department of Electoral Operations')
            ->firstOrFail();

        foreach (['Voter Registration Unit', 'Field Operations Unit'] as $name) {
            Unit::firstOrCreate(['department_id' => $department->id, 'name' => $name]);
        }

        return $department;
    }

    private function section(BasicInformation $staff, string $section = 'bio-data')
    {
        return $this->actingAs($this->actor())
            ->get(route('hr-appraisal', ['section' => $section, 'staff' => $staff->ulid]));
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

    /* ------------------------------------------------- what it carries */

    public function test_the_officers_name_is_drawn_from_the_record(): void
    {
        $response = $this->section($this->officer());

        $response->assertOk();
        $response->assertSee('value="Asuquo"', false);
        $response->assertSee('value="Bassey"', false);
        $response->assertSee('value="Eno"', false);
    }

    public function test_the_section_labels_read_as_asked_for(): void
    {
        $response = $this->section($this->officer());

        foreach (['Surname', 'First Name', 'Middle Name', 'Date of Birth',
                  'State of Origin', 'Deployment', 'Grade Level / Step',
                  'Department', 'Unit'] as $label) {
            $response->assertSee($label);
        }
    }

    public function test_the_date_of_birth_comes_from_the_record(): void
    {
        $this->section($this->officer())->assertSee('value="1985-04-12"', false);
    }

    public function test_the_state_of_origin_is_chosen_and_the_right_one_is_selected(): void
    {
        $crossRiver = State::where('name', 'Cross River')->firstOrFail();

        $this->section($this->officer())
            ->assertSee('name="state_of_origin"', false)
            ->assertSee('value="' . $crossRiver->id . '" selected', false);
    }

    public function test_the_grade_level_and_step_are_two_fields_under_one_label(): void
    {
        $response = $this->section($this->officer());

        $response->assertSee('Grade Level / Step');
        $response->assertSee('name="grade_level"', false);
        $response->assertSee('name="step"', false);
        $response->assertSee('value="9"', false);
        $response->assertSee('value="4"', false);
    }

    public function test_the_unit_is_the_one_the_officer_holds(): void
    {
        // Filled in by the script that narrows the units to the department's,
        // so the record's answer travels as the selected one.
        $this->section($this->officer())
            ->assertSee('data-selected="Voter Registration Unit"', false);
    }

    public function test_the_unit_and_department_are_editable(): void
    {
        $response = $this->section($this->officer());

        $response->assertSee('name="unit"', false);
        $response->assertSee('name="department"', false);
    }

    public function test_the_units_offered_are_the_departments_own(): void
    {
        // Another department's desk is not one this officer can be put at.
        $this->unitsOfOperations();

        $elsewhere = Department::where('department', 'Department of Finance and Account')
            ->firstOrFail();
        Unit::create(['department_id' => $elsewhere->id, 'name' => 'Treasury Unit']);

        $byDepartment = Department::unitsByDepartment();

        $this->assertSame(['Field Operations Unit', 'Voter Registration Unit'],
                          $byDepartment['Department of Electoral Operations']);
        $this->assertSame(['Treasury Unit'],
                          $byDepartment['Department of Finance and Account']);
    }

    public function test_a_unit_of_another_department_is_refused(): void
    {
        $staff = $this->officer();

        $elsewhere = Department::where('department', 'Department of Finance and Account')
            ->firstOrFail();
        Unit::create(['department_id' => $elsewhere->id, 'name' => 'Treasury Unit']);

        $this->save($staff, ['unit' => 'Treasury Unit'])->assertSessionHasErrors('unit');

        $this->assertSame('Voter Registration Unit', $staff->fresh()->unit);
    }

    /* ------------------------------------------- putting it right here */

    private function payload(BasicInformation $staff, array $overrides = []): array
    {
        $crossRiver = State::where('name', 'Cross River')->firstOrFail();

        return array_merge([
            'staff' => $staff->ulid,
            'section' => 'bio-data',
            'surname' => 'Asuquo', 'firstname' => 'Bassey', 'othernames' => 'Eno',
            'date_of_birth' => '1985-04-12',
            'state_of_origin' => $crossRiver->id,
            'grade_level' => 9, 'step' => 4,
            'department' => 'Department of Electoral Operations',
            'unit' => 'Voter Registration Unit',
        ], $overrides);
    }

    private function save(BasicInformation $staff, array $overrides = [])
    {
        return $this->actingAs($this->actor())
            ->post(route('hr-appraisal.bio-data'), $this->payload($staff, $overrides));
    }

    public function test_a_correction_reaches_the_staff_record(): void
    {
        $staff = $this->officer();

        $this->save($staff, ['surname' => 'Asuquo-Eno', 'unit' => 'Field Operations Unit'])
            ->assertRedirect(route('hr-appraisal', [
                'section' => 'career-history', 'staff' => $staff->ulid,
            ]));

        $staff->refresh();

        $this->assertSame('Asuquo-Eno', $staff->surname);
        $this->assertSame('Field Operations Unit', $staff->unit);
    }

    public function test_saving_carries_on_to_the_next_section(): void
    {
        $staff = $this->officer();

        // The button that saves is the button that moves on.
        $this->save($staff)->assertRedirect(route('hr-appraisal', [
            'section' => 'career-history', 'staff' => $staff->ulid,
        ]));
    }

    public function test_the_grade_level_and_step_can_be_put_right(): void
    {
        $staff = $this->officer();

        $this->save($staff, ['grade_level' => 12, 'step' => 2]);

        $this->assertSame(12, $staff->fresh()->grade_level);
        $this->assertSame(2, $staff->fresh()->step);
    }

    public function test_a_grade_level_off_the_scale_is_refused(): void
    {
        $staff = $this->officer();

        $this->save($staff, ['grade_level' => 18])->assertSessionHasErrors('grade_level');

        $this->assertSame(9, $staff->fresh()->grade_level);
    }

    public function test_a_department_that_is_not_one_is_refused(): void
    {
        $staff = $this->officer();

        $this->save($staff, ['department' => 'Department of Whatever'])
            ->assertSessionHasErrors('department');
    }

    public function test_an_officer_with_no_other_names_is_saved_as_blank(): void
    {
        // The column will not take a null.
        $staff = $this->officer();

        $this->save($staff, ['othernames' => null])->assertSessionHasNoErrors();

        $this->assertSame('', $staff->fresh()->othernames);
    }

    public function test_the_surname_is_required(): void
    {
        $staff = $this->officer();

        $this->save($staff, ['surname' => ''])->assertSessionHasErrors('surname');
    }

    public function test_a_unit_may_be_left_unanswered(): void
    {
        // A department with no desks on it yet has none to offer, so the
        // section cannot insist on one.
        $staff = $this->officer();

        $this->save($staff, ['unit' => ''])->assertSessionHasNoErrors();

        $this->assertNull($staff->fresh()->unit);
    }

    public function test_an_officer_posted_out_needs_no_department(): void
    {
        $staff = $this->officer();
        $this->postAway($staff);

        // The field is not on their form, so it cannot be required of them.
        $this->actingAs($this->actor())->post(route('hr-appraisal.bio-data'),
            collect($this->payload($staff))->except('department')->all())
            ->assertSessionHasNoErrors();
    }

    public function test_an_officer_posted_out_keeps_the_department_on_file(): void
    {
        $staff = $this->officer();
        $this->postAway($staff);

        $this->actingAs($this->actor())->post(route('hr-appraisal.bio-data'),
            collect($this->payload($staff))->except('department')->all());

        // Not asked for means not written, rather than wiped.
        $this->assertSame('Department of Electoral Operations', $staff->fresh()->department);
    }

    public function test_moving_an_officer_to_another_state_drops_an_lga_that_is_not_in_it(): void
    {
        $crossRiver = State::where('name', 'Cross River')->firstOrFail();
        $staff = $this->officer(['lga_of_origin' => $crossRiver->lgas()->value('id')]);

        $elsewhere = State::where('name', '!=', 'Cross River')->firstOrFail();

        $this->save($staff, ['state_of_origin' => $elsewhere->id]);

        // An LGA belongs to a state; it cannot stay pointing at one the
        // officer is no longer from.
        $this->assertSame($elsewhere->id, $staff->fresh()->state_of_origin);
        $this->assertNull($staff->fresh()->lga_of_origin);
    }

    public function test_keeping_the_same_state_keeps_the_lga(): void
    {
        $crossRiver = State::where('name', 'Cross River')->firstOrFail();
        $lga = $crossRiver->lgas()->value('id');
        $staff = $this->officer(['lga_of_origin' => $lga]);

        $this->save($staff);

        $this->assertSame($lga, $staff->fresh()->lga_of_origin);
    }

    /* ----------------------------------------------------- deployment */

    public function test_an_officer_at_the_state_office_reads_as_such(): void
    {
        $staff = $this->officer();

        $this->assertTrue(PostingContext::atHeadquarters($staff));
        $this->section($staff)->assertSee('value="State Office"', false);
    }

    public function test_an_officer_posted_out_reads_as_their_local_government(): void
    {
        $staff = $this->officer();
        $lga = $this->postAway($staff);

        $this->assertFalse(PostingContext::atHeadquarters($staff));
        $this->section($staff)
            ->assertSee('value="' . $lga->lga_name . ' Local Government Office"', false);
    }

    public function test_a_department_is_shown_only_at_the_state_office(): void
    {
        $atOffice = $this->officer();

        $this->section($atOffice)->assertSee('Department of Electoral Operations');
    }

    public function test_an_officer_posted_out_is_not_asked_for_a_department(): void
    {
        $staff = $this->officer();
        $this->postAway($staff);

        $response = $this->section($staff);

        // Out in a local government there is no department to name.
        $this->assertFalse($response->viewData('atHeadquarters'));
        $response->assertDontSee('value="Department of Electoral Operations"', false);
    }

    /* ------------------------------------------- moving to the next one */

    public function test_the_section_offers_the_next_one(): void
    {
        $staff = $this->officer();

        $this->section($staff)
            ->assertSee('Continue to Career History')
            ->assertSee(route('hr-appraisal', ['section' => 'career-history', 'staff' => $staff->ulid]));
    }

    public function test_the_officer_travels_to_the_next_section(): void
    {
        // Moving on must not lose who is being reported on.
        $staff = $this->officer();

        $this->assertStringContainsString($staff->ulid,
            route('hr-appraisal', ['section' => 'career-history', 'staff' => $staff->ulid]));
    }

    public function test_each_section_names_the_one_after_it(): void
    {
        $this->assertSame('career-history', Appraisal::next('bio-data'));
        $this->assertSame('Continue to Career History', Appraisal::continueLabel('bio-data'));
        $this->assertSame('Continue to Sanctions', Appraisal::continueLabel('commendations'));
    }

    public function test_the_last_section_offers_nothing_after_it(): void
    {
        $this->assertNull(Appraisal::next('counter-signing-officer-report'));
        $this->assertNull(Appraisal::continueLabel('counter-signing-officer-report'));

        $this->section($this->officer(), 'counter-signing-officer-report')
            ->assertDontSee('Continue to');
    }

    /* --------------------------------------- nothing without an officer */

    public function test_no_officer_means_no_form(): void
    {
        $this->actingAs($this->actor())->get(route('hr-appraisal', ['section' => 'bio-data']))
            ->assertOk()
            ->assertSee('Choose the officer this report is on.')
            ->assertDontSee('Continue to Career History');
    }

    public function test_a_section_with_nothing_built_says_so(): void
    {
        $this->section($this->officer(), 'character-traits')
            ->assertSee('This section has not been built yet.');
    }
}
