<?php

namespace Tests\Feature;

use App\Models\BasicInformation;
use App\Models\Department;
use App\Models\State;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The units inside a department.
 *
 * A unit is a desk, and a desk belongs to one directorate. The same name may
 * appear in two of them — "Registry" means a different desk in each — so a
 * unit is only ever offered beside the department it belongs to, and never
 * typed in freehand.
 */
class UnitReferenceTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create([
            'basic_information_ulid' => BasicInformation::factory()->create()->ulid,
            'role' => 'Administrator',
        ]);
    }

    private function operations(): Department
    {
        return Department::where('department', 'Department of Electoral Operations')
            ->firstOrFail();
    }

    private function finance(): Department
    {
        return Department::where('department', 'Department of Finance and Account')
            ->firstOrFail();
    }

    /* ------------------------------------------------ the page itself */

    public function test_the_administration_menu_offers_departments_and_units(): void
    {
        $this->actingAs($this->admin())->get(route('dashboard'))
            ->assertSee('Departments &amp; Units', false)
            ->assertSee(route('departments'));
    }

    public function test_the_page_lists_each_department_and_its_units(): void
    {
        Unit::create(['department_id' => $this->operations()->id,
                      'name' => 'Voter Registration Unit']);

        $this->actingAs($this->admin())->get(route('departments'))
            ->assertOk()
            ->assertSee('Department of Electoral Operations')
            ->assertSee('Voter Registration Unit')
            ->assertSee('Add a Unit');
    }

    public function test_an_officer_cannot_open_the_page(): void
    {
        $officer = User::factory()->create([
            'basic_information_ulid' => BasicInformation::factory()->create()->ulid,
            'role' => 'User',
        ]);

        $this->actingAs($officer)->get(route('departments'))->assertForbidden();
    }

    /* --------------------------------------------------- adding units */

    public function test_an_administrator_adds_a_unit_to_a_department(): void
    {
        $this->actingAs($this->admin())->post(route('config.units'), [
            'department_id' => $this->operations()->id,
            'name' => 'Voter Registration Unit',
        ])->assertRedirect(route('departments'));

        $this->assertTrue(Department::hasUnit('Department of Electoral Operations',
                                              'Voter Registration Unit'));
    }

    public function test_a_unit_needs_a_department_and_a_name(): void
    {
        $this->actingAs($this->admin())->post(route('config.units'), ['name' => 'Registry'])
            ->assertSessionHasErrors('department_id');

        $this->actingAs($this->admin())->post(route('config.units'), [
            'department_id' => $this->operations()->id,
        ])->assertSessionHasErrors('name');
    }

    public function test_the_same_name_may_serve_two_departments(): void
    {
        // "Registry" is a different desk in each.
        foreach ([$this->operations(), $this->finance()] as $department) {
            $this->actingAs($this->admin())->post(route('config.units'), [
                'department_id' => $department->id, 'name' => 'Registry',
            ])->assertSessionHasNoErrors();
        }

        $this->assertSame(2, Unit::where('name', 'Registry')->count());
    }

    public function test_a_department_cannot_have_the_same_unit_twice(): void
    {
        Unit::create(['department_id' => $this->operations()->id, 'name' => 'Registry']);

        $this->actingAs($this->admin())->post(route('config.units'), [
            'department_id' => $this->operations()->id, 'name' => 'Registry',
        ])->assertSessionHasErrors('name');

        $this->assertSame(1, Unit::where('name', 'Registry')->count());
    }

    public function test_an_officer_cannot_add_a_unit(): void
    {
        $officer = User::factory()->create([
            'basic_information_ulid' => BasicInformation::factory()->create()->ulid,
            'role' => 'User',
        ]);

        $this->actingAs($officer)->post(route('config.units'), [
            'department_id' => $this->operations()->id, 'name' => 'Whatever I Like',
        ])->assertForbidden();
    }

    /* ------------------------------------------------- taking one off */

    public function test_a_unit_may_be_taken_off(): void
    {
        $unit = Unit::create(['department_id' => $this->operations()->id,
                              'name' => 'Registry']);

        $this->actingAs($this->admin())->post(route('config.units.remove', $unit))
            ->assertRedirect(route('departments'));

        $this->assertSame(0, Unit::count());
    }

    public function test_taking_a_unit_off_leaves_officers_in_it_as_they_were(): void
    {
        $unit = Unit::create(['department_id' => $this->operations()->id,
                              'name' => 'Registry']);

        $staff = BasicInformation::factory()->create([
            'department' => 'Department of Electoral Operations',
            'unit' => 'Registry',
        ]);

        $this->actingAs($this->admin())->post(route('config.units.remove', $unit));

        // An officer is recorded against the name, not the row: removing it
        // stops the unit being chosen again and nothing else.
        $this->assertSame('Registry', $staff->fresh()->unit);
    }

    /* --------------------------------------- what the forms are given */

    public function test_the_units_are_grouped_under_their_departments(): void
    {
        Unit::create(['department_id' => $this->operations()->id, 'name' => 'Registry']);
        Unit::create(['department_id' => $this->operations()->id, 'name' => 'Field Operations']);
        Unit::create(['department_id' => $this->finance()->id, 'name' => 'Treasury']);

        $byDepartment = Department::unitsByDepartment();

        // Alphabetical within each, so the select reads in order.
        $this->assertSame(['Field Operations', 'Registry'],
                          $byDepartment['Department of Electoral Operations']);
        $this->assertSame(['Treasury'], $byDepartment['Department of Finance and Account']);
    }

    public function test_a_department_with_no_units_offers_none(): void
    {
        $this->assertSame([],
            Department::unitsByDepartment()['Department of Electoral Operations']);
    }

    public function test_a_unit_belongs_to_one_department_only(): void
    {
        Unit::create(['department_id' => $this->operations()->id, 'name' => 'Registry']);

        $this->assertTrue(Department::hasUnit('Department of Electoral Operations', 'Registry'));
        $this->assertFalse(Department::hasUnit('Department of Finance and Account', 'Registry'));
        $this->assertFalse(Department::hasUnit(null, 'Registry'));
        $this->assertFalse(Department::hasUnit('Department of Electoral Operations', null));
    }

    /* ------------------------------------------- the wizard holds to it */

    private function wizardPayload(array $overrides = []): array
    {
        $crossRiver = State::where('name', 'Cross River')->firstOrFail();

        return array_merge([
            'form-type' => 'basic-information',
            'file-number' => 'GL/F/0002',
            'last-name' => 'Edem', 'first-name' => 'Ekpo', 'gender' => 'Male',
            'department' => 'Department of Electoral Operations',
            'division' => 'Calabar HQ',
            'designation' => 'Executive Officer (EO)',
            'grade-level' => 9, 'step' => 4,
            'date-of-birth' => '1985-04-12', 'date-of-employment' => '2010-01-04',
            'phone-number' => '08030000002', 'email' => 'ekpo@inec.gov.ng',
            'state-of-origin' => $crossRiver->id,
            'lga-of-origin' => $crossRiver->lgas()->value('id'),
            'pfa' => 'Stanbic', 'rsa' => 'PEN2', 'nhis' => '2',
            'address' => 'Calabar',
        ], $overrides);
    }

    public function test_the_wizard_records_a_unit_of_the_chosen_department(): void
    {
        Unit::create(['department_id' => $this->operations()->id, 'name' => 'Registry']);

        $this->actingAs($this->admin())
            ->post(route('create'), $this->wizardPayload(['unit' => 'Registry']))
            ->assertSessionHasNoErrors();

        $this->assertSame('Registry', BasicInformation::where('surname', 'Edem')->value('unit'));
    }

    public function test_the_wizard_refuses_a_unit_of_another_department(): void
    {
        Unit::create(['department_id' => $this->finance()->id, 'name' => 'Treasury']);

        $this->actingAs($this->admin())
            ->post(route('create'), $this->wizardPayload(['unit' => 'Treasury']))
            ->assertSessionHasErrors('unit');

        $this->assertSame(0, BasicInformation::where('surname', 'Edem')->count());
    }

    public function test_the_wizard_takes_an_officer_with_no_unit(): void
    {
        // Nothing can be chosen until a department has desks on it, so the
        // wizard cannot insist on one.
        $this->actingAs($this->admin())
            ->post(route('create'), $this->wizardPayload())
            ->assertSessionHasNoErrors();

        $this->assertNull(BasicInformation::where('surname', 'Edem')->value('unit'));
    }

    public function test_the_wizard_offers_the_units_rather_than_a_text_box(): void
    {
        $html = $this->actingAs($this->admin())->get(route('basic-info'))->getContent();

        $this->assertMatchesRegularExpression('/<select[^>]*name="unit"/', $html);
        // And the office beside it stays what it is: a place, not a desk.
        $this->assertMatchesRegularExpression('/<input[^>]*name="division"/', $html);
        $this->assertStringContainsString('Office', $html);
    }
}
