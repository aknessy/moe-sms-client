<?php

namespace Tests\Feature;

use App\Models\BasicInformation;
use App\Models\DepartmentHead;
use App\Models\Role;
use App\Models\State;
use App\Models\User;
use App\Support\ApplicationRouting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * A department per head of department.
 *
 * Every HOD role is the head of one directorate, so the departments on a staff
 * record have to be those directorates and not short forms typed freehand — a
 * department nobody heads is one an officer's applications reach nobody from.
 */
class DepartmentReferenceTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create([
            'basic_information_ulid' => BasicInformation::factory()->create()->ulid,
            'role' => 'Administrator',
        ]);
    }

    /* ------------------------------------------- every role heads one */

    public static function headsAndTheirDepartments(): array
    {
        return [
            'general administration' => ['HOD GAP', 'Department of General Administration and Personnel'],
            'finance' => ['HOD Finance & Account', 'Department of Finance and Account'],
            'operations' => ['HOD EOPs', 'Department of Electoral Operations'],
            'ICT' => ['HOD ICT', 'Department of ICT and Voter Registry'],
            'party monitoring' => ['HOD EPM', 'Department of Election and Party Monitoring'],
            'voter education' => ['HOD VEP', 'Department of Voter Education and Publicity'],
        ];
    }

    #[DataProvider('headsAndTheirDepartments')]
    public function test_each_head_of_department_role_heads_its_directorate(string $role, string $department): void
    {
        $this->assertSame($role, DepartmentHead::roleFor($department),
            "{$department} should be headed by {$role}");
    }

    public function test_finance_and_account_is_a_department_of_its_own(): void
    {
        // It used to be folded into "Administration & Finance", which left
        // its head with nothing to head.
        $this->assertTrue(DepartmentHead::where('department', 'Department of Finance and Account')->exists());
        $this->assertTrue(DepartmentHead::where('department', 'Department of General Administration and Personnel')->exists());
        $this->assertFalse(DepartmentHead::where('department', 'Administration & Finance')->exists());
    }

    public function test_every_head_of_department_role_has_somewhere_to_head(): void
    {
        $headed = DepartmentHead::whereNotNull('role')->pluck('role')->all();

        foreach (Role::pluck('role') as $role) {
            if (str_starts_with($role, 'HOD ')) {
                $this->assertContains($role, $headed, "{$role} heads no department");
            }
        }
    }

    /* ----------------------------------------- the wizard offers the list */

    private function wizardPayload(array $overrides = []): array
    {
        $crossRiver = State::where('name', 'Cross River')->firstOrFail();

        return array_merge([
            'form-type' => 'basic-information',
            'file-number' => 'GL/F/0001',
            'last-name' => 'Eyo', 'first-name' => 'Bassey', 'gender' => 'Male',
            'department' => 'Department of Electoral Operations', 'division' => 'Calabar HQ',
            'designation' => 'Executive Officer (EO)',
            'grade-level' => 9, 'step' => 4,
            'date-of-birth' => '1985-04-12', 'date-of-employment' => '2010-01-04',
            'phone-number' => '08030000001', 'email' => 'bassey@inec.gov.ng',
            'state-of-origin' => $crossRiver->id,
            'lga-of-origin' => $crossRiver->lgas()->value('id'),
            'pfa' => 'Stanbic', 'rsa' => 'PEN1', 'nhis' => '1',
            'address' => 'Calabar',
        ], $overrides);
    }

    public function test_a_department_on_the_list_is_accepted(): void
    {
        $this->actingAs($this->admin())
            ->post(route('create'), $this->wizardPayload())
            ->assertSessionHasNoErrors();

        $this->assertSame('Department of Electoral Operations',
            BasicInformation::where('surname', 'Eyo')->value('department'));
    }

    public function test_a_department_typed_freehand_is_refused(): void
    {
        // The short form it used to be recorded under is no longer one of the
        // directorates.
        $this->actingAs($this->admin())
            ->post(route('create'), $this->wizardPayload(['department' => 'Operations']))
            ->assertSessionHasErrors('department');

        $this->assertSame(0, BasicInformation::where('surname', 'Eyo')->count());
    }

    public function test_the_wizard_offers_the_departments_rather_than_a_text_box(): void
    {
        $html = $this->actingAs($this->admin())->get(route('basic-info'))->getContent();

        $this->assertMatchesRegularExpression('/<select[^>]*name="department"/', $html);
        $this->assertStringContainsString('Department of Finance and Account', $html);
    }

    /* ------------------------------------------- adding one in Settings */

    public function test_an_administrator_adds_a_department(): void
    {
        $this->actingAs($this->admin())->post(route('config.departments'), [
            'department' => 'Department of Legal Services',
            'role' => 'HOD GAP',
        ])->assertRedirect(route('config'));

        $this->assertSame('HOD GAP', DepartmentHead::roleFor('Department of Legal Services'));
    }

    public function test_a_department_added_is_offered_by_the_wizard(): void
    {
        // Otherwise the list could never grow: the wizard offers only what is
        // on it, and the list was only ever built from staff records.
        DepartmentHead::create(['department' => 'Department of Legal Services']);

        $this->actingAs($this->admin())
            ->post(route('create'), $this->wizardPayload([
                'department' => 'Department of Legal Services',
            ]))
            ->assertSessionHasNoErrors();
    }

    public function test_a_department_cannot_be_added_twice(): void
    {
        $this->actingAs($this->admin())->post(route('config.departments'), [
            'department' => 'Department of Electoral Operations',
        ])->assertSessionHasErrors('department');
    }

    public function test_a_department_needs_a_name(): void
    {
        $this->actingAs($this->admin())->post(route('config.departments'), ['department' => ''])
            ->assertSessionHasErrors('department');
    }

    public function test_a_department_may_be_added_with_nobody_heading_it(): void
    {
        $this->actingAs($this->admin())->post(route('config.departments'), [
            'department' => 'Department of Legal Services',
            'role' => '',
        ])->assertRedirect(route('config'));

        $this->assertNull(DepartmentHead::roleFor('Department of Legal Services'));
    }

    public function test_an_officer_cannot_add_a_department(): void
    {
        $officer = User::factory()->create([
            'basic_information_ulid' => BasicInformation::factory()->create()->ulid,
            'role' => 'User',
        ]);

        $this->actingAs($officer)->post(route('config.departments'), [
            'department' => 'Department of Whatever I Like',
        ])->assertForbidden();
    }

    /* --------------------------------------------- how the letter reads */

    public function test_a_directorate_does_not_stutter_in_the_title(): void
    {
        $staff = BasicInformation::factory()->create([
            'department' => 'Department of Finance and Account',
        ]);

        $this->assertSame('The Head, Department of Finance and Account',
            ApplicationRouting::immediateSuperior($staff)['title']);
    }
}
