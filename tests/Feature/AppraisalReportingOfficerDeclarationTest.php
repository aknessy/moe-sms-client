<?php

namespace Tests\Feature;

use App\Models\BasicInformation;
use App\Models\Lga;
use App\Models\Posting;
use App\Models\PromotionBrief;
use App\Models\ReportingOfficerDeclaration;
use App\Models\State;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The Declaration by Reporting Officer section: the reporting officer's
 * statement that they assessed the officer, with their post, grade, date and
 * signature. Made by an administrator, the head of the officer's department,
 * or the Electoral Officer of the LGA the officer is deployed to.
 */
class AppraisalReportingOfficerDeclarationTest extends TestCase
{
    use RefreshDatabase;

    private function officer(): BasicInformation
    {
        $staff = BasicInformation::factory()->create([
            'surname' => 'Asuquo', 'firstname' => 'Bassey',
            'department' => 'Department of Electoral Operations',
        ]);

        PromotionBrief::create([
            'year' => now()->year,
            'basic_information_ulid' => $staff->ulid,
            'on_brief_at' => now(),
        ]);

        return $staff;
    }

    private function account(string $role, array $staffAttributes = []): User
    {
        return User::factory()->create([
            'basic_information_ulid' => BasicInformation::factory()->create($staffAttributes)->ulid,
            'role' => $role,
        ]);
    }

    private function sectionAs(User $viewer, BasicInformation $staff)
    {
        return $this->actingAs($viewer)->get(route('hr-appraisal', [
            'section' => 'reporting-officer-declaration', 'staff' => $staff->ulid,
        ]));
    }

    private function saveAs(User $viewer, BasicInformation $staff, array $overrides = [])
    {
        return $this->actingAs($viewer)->post(route('hr-appraisal.reporting-officer-declaration'),
            array_merge([
                'staff' => $staff->ulid,
                'section' => 'reporting-officer-declaration',
                'officer_name' => 'Ekong Udoh',
                'post_held' => 'Electoral Officer',
                'grade_level' => '12',
                'declared_on' => '2026-11-20',
                'signature' => 'E. Udoh',
            ], $overrides));
    }

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

    public function test_the_section_shows_the_declaration_wording_and_fields(): void
    {
        $staff = $this->officer();

        $this->sectionAs($this->account('Administrator'), $staff)
            ->assertOk()
            ->assertSee('assessed the above named officer and declare')
            ->assertSee('name="post_held"', false)
            ->assertSee('name="grade_level"', false)
            ->assertSee('name="declared_on"', false)
            ->assertSee('name="signature"', false);
    }

    public function test_the_form_opens_on_the_declarants_own_post_and_grade(): void
    {
        $staff = $this->officer();
        $admin = $this->account('Administrator', ['designation' => 'Director of Operations', 'grade_level' => 15]);

        $this->sectionAs($admin, $staff)->assertSee('Director of Operations');
    }

    public function test_the_declaration_reaches_the_record(): void
    {
        $staff = $this->officer();

        $this->saveAs($this->account('Administrator'), $staff)->assertRedirect(route('hr-appraisal', [
            'section' => 'counter-signing-officer-report', 'staff' => $staff->ulid,
        ]));

        $declaration = ReportingOfficerDeclaration::where('basic_information_ulid', $staff->ulid)->sole();

        $this->assertSame('Ekong Udoh', $declaration->officer_name);
        $this->assertSame('Electoral Officer', $declaration->post_held);
        $this->assertSame('12', $declaration->grade_level);
        $this->assertSame('2026-11-20', $declaration->declared_on->toDateString());
        $this->assertSame('E. Udoh', $declaration->signature);
    }

    public function test_saving_again_corrects_the_one_declaration(): void
    {
        $staff = $this->officer();
        $admin = $this->account('Administrator');

        $this->saveAs($admin, $staff);
        $this->saveAs($admin, $staff, ['post_held' => 'Director']);

        $this->assertSame('Director', ReportingOfficerDeclaration::sole()->post_held);
    }

    public function test_every_field_is_required(): void
    {
        $staff = $this->officer();

        foreach (['officer_name', 'post_held', 'grade_level', 'declared_on', 'signature'] as $field) {
            $this->saveAs($this->account('Administrator'), $staff, [$field => ''])
                ->assertSessionHasErrors($field);
        }
    }

    public function test_the_head_of_the_officers_department_makes_it(): void
    {
        $staff = $this->officer();
        $head = $this->account('HOD EOPs');

        $this->sectionAs($head, $staff)->assertSee('name="signature"', false);
        $this->saveAs($head, $staff)->assertRedirect();
    }

    public function test_the_electoral_officer_of_the_officers_lga_makes_it(): void
    {
        $staff = $this->officer();
        $lga = $this->postAway($staff);
        $eo = $this->account('Registry Officer', ['designation' => 'Electoral Officer']);

        Posting::create([
            'basic_information_ulid' => $eo->staff->ulid,
            'posting_type' => 'deployment',
            'to_lga' => $lga->id,
            'effective_date' => '2025-01-06',
            'deployed_as' => 'Electoral Officer',
        ]);

        $this->sectionAs($eo, $staff)->assertSee('name="signature"', false);
        $this->saveAs($eo, $staff)->assertRedirect();
    }

    public function test_the_head_of_another_department_only_reads_on(): void
    {
        $staff = $this->officer();
        $other = $this->account('HOD ICT');

        $this->sectionAs($other, $staff)->assertDontSee('name="signature"', false);
        $this->saveAs($other, $staff)->assertForbidden();
        $this->assertSame(0, ReportingOfficerDeclaration::count());
    }

    public function test_the_officer_themselves_may_not_make_it(): void
    {
        $staff = $this->officer();
        $self = User::factory()->create(['basic_information_ulid' => $staff->ulid, 'role' => 'User']);

        $this->sectionAs($self, $staff)->assertDontSee('name="signature"', false);
        $this->saveAs($self, $staff)->assertForbidden();
    }

    public function test_a_declaration_on_record_shows_to_those_who_may_not_make_it(): void
    {
        $staff = $this->officer();

        ReportingOfficerDeclaration::create([
            'basic_information_ulid' => $staff->ulid,
            'officer_name' => 'Ekong Udoh', 'post_held' => 'Electoral Officer',
            'grade_level' => '12', 'declared_on' => '2026-11-20', 'signature' => 'E. Udoh',
        ]);

        $this->sectionAs($this->account('HOD ICT'), $staff)
            ->assertSee('Ekong Udoh')
            ->assertSee('Electoral Officer')
            ->assertSee('20 Nov 2026');
    }
}
