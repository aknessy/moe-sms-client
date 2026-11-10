<?php

namespace Tests\Feature;

use App\Models\BasicInformation;
use App\Models\Department;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Things about this command that belong to the installation rather than the
 * code, recorded by an administrator: the letterhead above every letter the
 * system raises.
 *
 * Who heads which department moved to its own page; see
 * {@see DepartmentReferenceTest}.
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

    /* --------------------------------------------- printable bio-data */

    public function test_the_sheet_is_a4_and_portrait_before_anything_is_saved(): void
    {
        $this->assertSame('A4', Setting::get('bio_data.print_size'));
        $this->assertSame('Portrait', Setting::get('bio_data.print_orientation'));
    }

    public function test_an_administrator_sets_the_bio_data_to_a3_landscape(): void
    {
        $this->actingAs($this->admin())->post(route('config.printing'), [
            'print_size' => 'A3',
            'print_orientation' => 'Landscape',
        ])->assertRedirect(route('config'));

        $this->assertSame('A3', Setting::get('bio_data.print_size'));
        $this->assertSame('Landscape', Setting::get('bio_data.print_orientation'));
    }

    public function test_the_sheet_only_accepts_the_sizes_and_orientations_offered(): void
    {
        $this->actingAs($this->admin())->post(route('config.printing'), [
            'print_size' => 'Letter',
            'print_orientation' => 'Sideways',
        ])->assertSessionHasErrors(['print_size', 'print_orientation']);
    }

    public function test_an_officer_cannot_change_how_bio_data_prints(): void
    {
        $officer = User::factory()->create([
            'basic_information_ulid' => BasicInformation::factory()->create()->ulid,
            'role' => 'User',
        ]);

        $this->actingAs($officer)->post(route('config.printing'), [
            'print_size' => 'A3',
            'print_orientation' => 'Landscape',
        ])->assertForbidden();
    }
}
