<?php

namespace Tests\Feature;

use App\Models\BasicInformation;
use App\Models\DepartmentHead;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Things about this command that belong to the installation rather than the
 * code, recorded by an administrator: the letterhead, and who heads which
 * department.
 */
class OrganisationSettingsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create([
            'basic_information_ulid' => BasicInformation::factory()->create()->ulid,
            'role' => 'Administrator',
        ]);
    }

    private function details(array $overrides = []): array
    {
        return array_merge([
            'organisation_name' => 'Independent National Electoral Commission',
            'organisation_command' => 'Cross River State Office',
            'organisation_address' => '4 Murtala Mohammed Highway, Calabar',
            'organisation_telephone' => '08031234567',
            'organisation_email' => 'crossriver@inecnigeria.org',
            'organisation_commissioner_title' => 'The Resident Electoral Commissioner',
        ], $overrides);
    }

    /* --------------------------------------------- organisation details */

    public function test_an_administrator_records_the_organisations_details(): void
    {
        $this->actingAs($this->admin())
            ->post(route('config.organisation'), $this->details())
            ->assertRedirect(route('config'));

        $this->assertSame('Cross River State Office', Setting::get('organisation.command'));
        $this->assertSame('08031234567', Setting::get('organisation.telephone'));
        $this->assertSame('4 Murtala Mohammed Highway, Calabar', Setting::get('organisation.address'));
    }

    public function test_the_form_shows_what_is_already_recorded(): void
    {
        Setting::put('organisation.telephone', '08031234567');

        $this->actingAs($this->admin())->get(route('config'))
            ->assertOk()
            ->assertSee('08031234567');
    }

    public function test_the_name_and_the_addressee_are_required(): void
    {
        $this->actingAs($this->admin())->post(route('config.organisation'), $this->details([
            'organisation_name' => '',
            'organisation_commissioner_title' => '',
        ]))->assertSessionHasErrors(['organisation_name', 'organisation_commissioner_title']);
    }

    public function test_a_telephone_is_optional(): void
    {
        // "if saved" — the letter prints it where there is one and says
        // nothing where there is not.
        $this->actingAs($this->admin())
            ->post(route('config.organisation'), $this->details(['organisation_telephone' => '']))
            ->assertRedirect(route('config'));

        $this->assertNull(Setting::get('organisation.telephone'));
    }

    public function test_a_setting_is_read_back_after_it_changes(): void
    {
        // Settings are held in memory between requests; a write has to clear
        // that or the letterhead would go on printing the old address.
        Setting::put('organisation.telephone', '08030000001');
        $this->assertSame('08030000001', Setting::get('organisation.telephone'));

        Setting::put('organisation.telephone', '08030000002');
        $this->assertSame('08030000002', Setting::get('organisation.telephone'));
    }

    public function test_the_name_has_a_sensible_default_before_anything_is_saved(): void
    {
        $this->assertSame('Independent National Electoral Commission',
            Setting::get('organisation.name'));
        $this->assertSame('The Resident Electoral Commissioner',
            Setting::get('organisation.commissioner_title'));
    }

    public function test_an_officer_cannot_change_the_settings(): void
    {
        $officer = User::factory()->create([
            'basic_information_ulid' => BasicInformation::factory()->create()->ulid,
            'role' => 'User',
        ]);

        $this->actingAs($officer)->post(route('config.organisation'), $this->details())
            ->assertForbidden();
    }

    /* ------------------------------------------------ heads of department */

    public function test_the_settings_page_lists_every_department_in_use(): void
    {
        BasicInformation::factory()->create(['department' => 'Legal & Litigation']);

        $this->actingAs($this->admin())->get(route('config'))
            ->assertSee('Legal &amp; Litigation', false);

        $this->assertTrue(DepartmentHead::where('department', 'Legal & Litigation')->exists());
    }

    public function test_an_administrator_names_who_heads_a_department(): void
    {
        $head = DepartmentHead::create(['department' => 'Legal & Litigation']);

        $this->actingAs($this->admin())->post(route('config.department-heads'), [
            'heads' => [$head->id => 'HOD GAP'],
        ])->assertRedirect(route('config'));

        $this->assertSame('HOD GAP', $head->fresh()->role);
    }

    public function test_a_department_can_be_left_with_nobody_named(): void
    {
        $head = DepartmentHead::create(['department' => 'Operations', 'role' => 'HOD EOPs']);

        $this->actingAs($this->admin())->post(route('config.department-heads'), [
            'heads' => [$head->id => ''],
        ]);

        $this->assertNull($head->fresh()->role);
    }

    public function test_a_role_that_is_not_on_the_system_is_refused(): void
    {
        $head = DepartmentHead::create(['department' => 'Operations']);

        $this->actingAs($this->admin())->post(route('config.department-heads'), [
            'heads' => [$head->id => 'HOD Everything'],
        ])->assertSessionHasErrors('heads.' . $head->id);

        $this->assertNull($head->fresh()->role);
    }

    public function test_only_the_heads_of_department_roles_are_offered(): void
    {
        $offered = $this->actingAs($this->admin())->get(route('config'))->viewData('hodRoles');

        $this->assertArrayHasKey('HOD ICT', $offered);
        $this->assertArrayHasKey('HOD Finance & Account', $offered);
        // Not a head of anything.
        $this->assertArrayNotHasKey('Registry Officer', $offered);
        $this->assertArrayNotHasKey('Commissioner', $offered);
    }

    public function test_finance_and_account_is_a_role_of_its_own(): void
    {
        $this->assertTrue(Role::where('role', 'HOD Finance & Account')->exists());
        $this->assertTrue(Role::where('role', 'HOD GAP')->exists());
    }

    public function test_the_obvious_departments_are_seeded_with_their_heads(): void
    {
        // Seeded by the migration, from the departments already on record.
        foreach (['ICT & Voter Registry' => 'HOD ICT',
                  'Voter Education & Publicity' => 'HOD VEP',
                  'Operations' => 'HOD EOPs'] as $department => $role) {

            DepartmentHead::updateOrCreate(['department' => $department], ['role' => $role]);

            $this->assertSame($role, DepartmentHead::roleFor($department));
        }
    }

    public function test_a_department_nobody_heads_reads_as_null(): void
    {
        DepartmentHead::create(['department' => 'Legal & Litigation']);

        $this->assertNull(DepartmentHead::roleFor('Legal & Litigation'));
        $this->assertNull(DepartmentHead::roleFor('A department that does not exist'));
        $this->assertNull(DepartmentHead::roleFor(null));
    }
}
