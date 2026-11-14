<?php

namespace Tests\Feature;

use App\Models\BasicInformation;
use App\Models\LeadershipTraitAssessment;
use App\Models\Lga;
use App\Models\Posting;
use App\Models\PromotionBrief;
use App\Models\State;
use App\Models\User;
use App\Support\ReportingAuthority;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The Leadership Traits section: a letter grade, A to F, for each of five
 * questions on how the officer leads, and one more for the overall
 * assessment the five of them add up to.
 */
class AppraisalLeadershipTraitsTest extends TestCase
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
            'surname' => 'Eyo', 'firstname' => 'Ani',
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

    private function sectionAs(User $viewer, BasicInformation $staff)
    {
        return $this->actingAs($viewer)->get(route('hr-appraisal', [
            'section' => 'leadership-traits', 'staff' => $staff->ulid,
        ]));
    }

    private function saveAs(User $viewer, BasicInformation $staff, array $overrides = [])
    {
        return $this->actingAs($viewer)->post(route('hr-appraisal.leadership-traits'),
            array_merge([
                'staff' => $staff->ulid,
                'section' => 'leadership-traits',
                'standards_grade' => 'B',
                'timeliness_grade' => 'A',
                'example_grade' => 'B',
                'suggestions_grade' => 'C',
                'value_added_grade' => 'B',
                'overall_grade' => 'B',
            ], $overrides));
    }

    private function section(BasicInformation $staff)
    {
        return $this->sectionAs($this->actor(), $staff);
    }

    private function save(BasicInformation $staff, array $overrides = [])
    {
        return $this->saveAs($this->actor(), $staff, $overrides);
    }

    /* ----------------------------------------------------- the section */

    public function test_the_section_names_the_five_questions(): void
    {
        $response = $this->section($this->officer());

        $response->assertOk();

        foreach (array_keys(LeadershipTraitAssessment::TRAITS) as $column) {
            $response->assertSee('name="' . $column . '" value="A"', false);
        }
    }

    public function test_each_question_offers_the_six_grades_as_checkboxes(): void
    {
        $response = $this->section($this->officer());

        foreach (array_keys(LeadershipTraitAssessment::TRAITS) as $column) {
            foreach (LeadershipTraitAssessment::GRADES as $grade) {
                $response->assertSee('name="' . $column . '" value="' . $grade . '"', false);
            }
        }
    }

    public function test_the_overall_assessment_offers_the_six_grades_with_their_meaning(): void
    {
        $response = $this->section($this->officer());

        foreach (LeadershipTraitAssessment::OVERALL_GRADES as $option => $meaning) {
            $response->assertSee('name="overall_grade" value="' . $option . '"', false);
            $response->assertSee($meaning['label']);
            $response->assertSee($meaning['description']);
        }
    }

    public function test_grades_already_on_record_are_checked(): void
    {
        $staff = $this->officer();

        LeadershipTraitAssessment::create([
            'basic_information_ulid' => $staff->ulid,
            'standards_grade' => 'C',
            'timeliness_grade' => 'B',
            'example_grade' => 'A',
            'suggestions_grade' => 'D',
            'value_added_grade' => 'C',
            'overall_grade' => 'C',
        ]);

        $html = $this->section($staff)->getContent();

        $this->assertMatchesRegularExpression(
            '/name="standards_grade" value="C"\s+checked/', $html,
        );
        $this->assertMatchesRegularExpression(
            '/name="overall_grade" value="C"\s+checked/', $html,
        );
    }

    /* -------------------------------------------------- saving the grades */

    public function test_the_grades_reach_the_record(): void
    {
        $staff = $this->officer();

        $this->save($staff)->assertRedirect(route('hr-appraisal', [
            'section' => 'general-remarks', 'staff' => $staff->ulid,
        ]));

        $assessment = LeadershipTraitAssessment::where('basic_information_ulid', $staff->ulid)->first();

        $this->assertSame('B', $assessment->standards_grade);
        $this->assertSame('A', $assessment->timeliness_grade);
        $this->assertSame('B', $assessment->example_grade);
        $this->assertSame('C', $assessment->suggestions_grade);
        $this->assertSame('B', $assessment->value_added_grade);
        $this->assertSame('B', $assessment->overall_grade);
    }

    public function test_saving_again_corrects_the_one_assessment_rather_than_adding_another(): void
    {
        $staff = $this->officer();

        $this->save($staff);
        $this->save($staff, ['overall_grade' => 'A']);

        $this->assertSame(1, LeadershipTraitAssessment::where('basic_information_ulid', $staff->ulid)->count());
        $this->assertSame('A', LeadershipTraitAssessment::where('basic_information_ulid', $staff->ulid)
            ->first()->overall_grade);
    }

    public function test_every_question_is_required(): void
    {
        $this->save($this->officer(), ['timeliness_grade' => ''])
            ->assertSessionHasErrors('timeliness_grade');
    }

    public function test_a_grade_must_be_one_of_the_six_offered(): void
    {
        $this->save($this->officer(), ['example_grade' => 'Z'])
            ->assertSessionHasErrors('example_grade');
    }

    public function test_the_overall_assessment_is_required(): void
    {
        $this->save($this->officer(), ['overall_grade' => ''])
            ->assertSessionHasErrors('overall_grade');
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

        $this->sectionAs($officerDownTheRoad, $staff)->assertDontSee('name="standards_grade"', false);
        $this->saveAs($officerDownTheRoad, $staff)->assertForbidden();
    }

    public function test_the_head_of_the_officers_department_grades_the_traits(): void
    {
        $staff = $this->officer();

        $head = $this->account('HOD EOPs');

        $this->assertTrue(ReportingAuthority::mayCommentOn($head, $staff));

        $this->sectionAs($head, $staff)->assertSee('name="standards_grade"', false);
        $this->saveAs($head, $staff)->assertRedirect();
    }

    public function test_the_head_of_another_department_only_reads_on(): void
    {
        $staff = $this->officer();

        $head = $this->account('HOD ICT');

        $this->assertFalse(ReportingAuthority::mayCommentOn($head, $staff));

        $this->sectionAs($head, $staff)->assertDontSee('name="standards_grade"', false);
        $this->saveAs($head, $staff)->assertForbidden();
    }

    public function test_an_unauthorized_viewer_sees_the_grades_already_on_record(): void
    {
        $staff = $this->officer();

        LeadershipTraitAssessment::create([
            'basic_information_ulid' => $staff->ulid,
            'standards_grade' => 'C',
            'timeliness_grade' => 'B',
            'example_grade' => 'A',
            'suggestions_grade' => 'D',
            'value_added_grade' => 'C',
            'overall_grade' => 'B',
        ]);

        $html = $this->sectionAs($this->account('HOD ICT'), $staff)->getContent();

        $this->assertStringContainsString('graded by the Electoral Officer', $html);
        $this->assertStringContainsString('Very Good', $html);
    }
}
