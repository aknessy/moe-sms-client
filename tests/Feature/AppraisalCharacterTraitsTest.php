<?php

namespace Tests\Feature;

use App\Models\BasicInformation;
use App\Models\CharacterTraitAssessment;
use App\Models\Lga;
use App\Models\Posting;
use App\Models\PromotionBrief;
use App\Models\State;
use App\Models\User;
use App\Support\ReportingAuthority;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The Character Traits section: a grade for each of dependability,
 * appearance, attendance and resource utilization, and one justification
 * covering all four together.
 */
class AppraisalCharacterTraitsTest extends TestCase
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

    /** Only an officer on the current year's promotion brief has a report open on them. */
    private function putOnBrief(BasicInformation $staff): void
    {
        PromotionBrief::create([
            'year' => now()->year,
            'basic_information_ulid' => $staff->ulid,
            'on_brief_at' => now(),
        ]);
    }

    private function officer(array $attributes = []): BasicInformation
    {
        $staff = BasicInformation::factory()->create($attributes + [
            'surname' => 'Asuquo', 'firstname' => 'Bassey',
            'department' => 'Department of Electoral Operations',
        ]);

        $this->putOnBrief($staff);

        return $staff;
    }

    /** An account with no staff record behind it, for the grading authority. */
    private function account(string $role, array $staffAttributes = []): User
    {
        return User::factory()->create([
            'basic_information_ulid' => BasicInformation::factory()
                ->create($staffAttributes)->ulid,
            'role' => $role,
        ]);
    }

    private function sectionAs(User $viewer, BasicInformation $staff)
    {
        return $this->actingAs($viewer)->get(route('hr-appraisal', [
            'section' => 'character-traits', 'staff' => $staff->ulid,
        ]));
    }

    private function saveAs(User $viewer, BasicInformation $staff, array $overrides = [])
    {
        return $this->actingAs($viewer)->post(route('hr-appraisal.character-traits'),
            array_merge([
                'staff' => $staff->ulid,
                'section' => 'character-traits',
                'dependability_grade' => 'Very Good',
                'appearance_grade' => 'Excellent',
                'attendance_grade' => 'Very Good',
                'resource_utilization_grade' => 'Good',
                'justification' => 'Reliable, presentable, regular and makes good use of resources.',
            ], $overrides));
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

    private function section(BasicInformation $staff)
    {
        return $this->actingAs($this->actor())->get(route('hr-appraisal', [
            'section' => 'character-traits', 'staff' => $staff->ulid,
        ]));
    }

    private function save(BasicInformation $staff, array $overrides = [])
    {
        return $this->actingAs($this->actor())->post(route('hr-appraisal.character-traits'),
            array_merge([
                'staff' => $staff->ulid,
                'section' => 'character-traits',
                'dependability_grade' => 'Very Good',
                'appearance_grade' => 'Excellent',
                'attendance_grade' => 'Very Good',
                'resource_utilization_grade' => 'Good',
                'justification' => 'Reliable, presentable, regular and makes good use of resources.',
            ], $overrides));
    }

    /* ----------------------------------------------------- the section */

    public function test_the_section_names_the_four_traits(): void
    {
        $response = $this->section($this->officer());

        $response->assertOk();

        foreach (['Dependability', 'Appearance', 'Attendance', 'Resource Utilization'] as $trait) {
            $response->assertSee($trait);
        }
    }

    public function test_each_trait_offers_the_six_grades_as_checkboxes(): void
    {
        $response = $this->section($this->officer());

        foreach (array_keys(CharacterTraitAssessment::TRAITS) as $column) {
            foreach (CharacterTraitAssessment::GRADES as $grade) {
                $response->assertSee('name="' . $column . '" value="' . $grade . '"', false);
            }
        }

        $response->assertSee('type="checkbox"', false);
    }

    public function test_grades_already_on_record_are_checked_and_the_justification_shows(): void
    {
        $staff = $this->officer();

        CharacterTraitAssessment::create([
            'basic_information_ulid' => $staff->ulid,
            'dependability_grade' => 'Good',
            'appearance_grade' => 'Very Good',
            'attendance_grade' => 'Excellent',
            'resource_utilization_grade' => 'Fair',
            'justification' => 'Steady work, could present better.',
        ]);

        $this->section($staff)->assertSee('Steady work, could present better.');
    }

    /* -------------------------------------------------- saving the grades */

    public function test_the_grades_and_justification_reach_the_record(): void
    {
        $staff = $this->officer();

        $this->save($staff)->assertRedirect(route('hr-appraisal', [
            'section' => 'commendations', 'staff' => $staff->ulid,
        ]));

        $assessment = CharacterTraitAssessment::where('basic_information_ulid', $staff->ulid)->first();

        $this->assertSame('Very Good', $assessment->dependability_grade);
        $this->assertSame('Excellent', $assessment->appearance_grade);
        $this->assertSame('Very Good', $assessment->attendance_grade);
        $this->assertSame('Good', $assessment->resource_utilization_grade);
        $this->assertSame('Reliable, presentable, regular and makes good use of resources.',
            $assessment->justification);
    }

    public function test_saving_again_corrects_the_one_assessment_rather_than_adding_another(): void
    {
        $staff = $this->officer();

        $this->save($staff);
        $this->save($staff, [
            'dependability_grade' => 'Outstanding',
            'justification' => 'Improved further since the last report.',
        ]);

        $this->assertSame(1, CharacterTraitAssessment::where('basic_information_ulid', $staff->ulid)->count());
        $this->assertSame('Outstanding', CharacterTraitAssessment::where('basic_information_ulid', $staff->ulid)
            ->first()->dependability_grade);
    }

    public function test_every_trait_is_required(): void
    {
        $this->save($this->officer(), ['appearance_grade' => ''])
            ->assertSessionHasErrors('appearance_grade');
    }

    public function test_a_grade_must_be_one_of_the_six_offered(): void
    {
        $this->save($this->officer(), ['attendance_grade' => 'Terrible'])
            ->assertSessionHasErrors('attendance_grade');
    }

    public function test_the_justification_is_required(): void
    {
        $this->save($this->officer(), ['justification' => ''])
            ->assertSessionHasErrors('justification');
    }

    /* ------------------------------------------- who grades the traits */

    public function test_a_staff_account_may_not_grade_the_traits(): void
    {
        $staff = $this->officer();

        $this->assertFalse(ReportingAuthority::mayCommentOn($this->account('User'), $staff));
    }

    public function test_an_administrator_grades_the_traits(): void
    {
        $staff = $this->officer();
        $administrator = $this->actor();

        $this->assertTrue(ReportingAuthority::mayCommentOn($administrator, $staff));

        $this->sectionAs($administrator, $staff)->assertSee('type="checkbox"', false);
    }

    public function test_the_electoral_officer_of_the_officers_lga_grades_the_traits(): void
    {
        $staff = $this->officer();
        $lga = $this->postAway($staff);

        $electoralOfficer = $this->account('Registry Officer',
            ['designation' => 'Electoral Officer']);

        Posting::create([
            'basic_information_ulid' => $electoralOfficer->staff->ulid,
            'posting_type' => 'deployment',
            'to_lga' => $lga->id,
            'effective_date' => '2025-01-06',
            'deployed_as' => 'Electoral Officer',
        ]);

        $this->assertTrue(ReportingAuthority::mayCommentOn($electoralOfficer, $staff));

        $this->sectionAs($electoralOfficer, $staff)->assertSee('type="checkbox"', false);
        $this->saveAs($electoralOfficer, $staff)->assertRedirect();
    }

    public function test_the_electoral_officer_of_another_lga_only_reads_on(): void
    {
        $staff = $this->officer();
        $lga = $this->postAway($staff);

        $elsewhere = State::where('name', config('posting.default_state'))->firstOrFail()
            ->lgas()
            ->where('lga_name', '!=', config('posting.default_lga'))
            ->where('lga_name', '!=', $lga->lga_name)
            ->firstOrFail();

        $officerDownTheRoad = $this->account('Registry Officer',
            ['designation' => 'Electoral Officer']);

        Posting::create([
            'basic_information_ulid' => $officerDownTheRoad->staff->ulid,
            'posting_type' => 'deployment',
            'to_lga' => $elsewhere->id,
            'effective_date' => '2025-01-06',
            'deployed_as' => 'Electoral Officer',
        ]);

        $this->assertFalse(ReportingAuthority::mayCommentOn($officerDownTheRoad, $staff));

        $this->sectionAs($officerDownTheRoad, $staff)->assertDontSee('name="dependability_grade"', false);
        $this->saveAs($officerDownTheRoad, $staff)->assertForbidden();
    }

    public function test_the_head_of_the_officers_department_grades_the_traits(): void
    {
        $staff = $this->officer();

        $head = $this->account('HOD EOPs');

        $this->assertTrue(ReportingAuthority::mayCommentOn($head, $staff));

        $this->sectionAs($head, $staff)->assertSee('name="dependability_grade"', false);
        $this->saveAs($head, $staff)->assertRedirect();
    }

    public function test_the_head_of_another_department_only_reads_on(): void
    {
        $staff = $this->officer();

        $head = $this->account('HOD ICT');

        $this->assertFalse(ReportingAuthority::mayCommentOn($head, $staff));

        $this->sectionAs($head, $staff)->assertDontSee('name="dependability_grade"', false);
        $this->saveAs($head, $staff)->assertForbidden();
    }

    public function test_an_unauthorized_viewer_sees_the_grades_already_on_record(): void
    {
        $staff = $this->officer();

        CharacterTraitAssessment::create([
            'basic_information_ulid' => $staff->ulid,
            'dependability_grade' => 'Good',
            'appearance_grade' => 'Very Good',
            'attendance_grade' => 'Excellent',
            'resource_utilization_grade' => 'Fair',
            'justification' => 'Steady work, could present better.',
        ]);

        $html = $this->sectionAs($this->account('HOD ICT'), $staff)->getContent();

        $this->assertStringContainsString('Steady work, could present better.', $html);
        $this->assertStringContainsString('graded by the Electoral Officer', $html);
    }
}
