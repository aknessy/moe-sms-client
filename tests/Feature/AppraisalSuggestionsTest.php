<?php

namespace Tests\Feature;

use App\Models\BasicInformation;
use App\Models\Lga;
use App\Models\Posting;
use App\Models\PromotionBrief;
use App\Models\State;
use App\Models\Suggestion;
use App\Models\User;
use App\Support\ReportingAuthority;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The Suggestions (By Superior Officer) section: whether the officer is
 * suggested for a different job in the same grade, and the kind of job and
 * reasons where they are.
 */
class AppraisalSuggestionsTest extends TestCase
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

    /** An account with no staff record behind it, for the reporting authority. */
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
            'section' => 'suggestions', 'staff' => $staff->ulid,
        ]));
    }

    private function saveAs(User $viewer, BasicInformation $staff, array $overrides = [])
    {
        return $this->actingAs($viewer)->post(route('hr-appraisal.suggestions'),
            array_merge([
                'staff' => $staff->ulid,
                'section' => 'suggestions',
                'different_job_suggested' => 'yes',
                'different_job_details' => 'Registration Officer, for the exposure to field operations.',
                'acted_on' => '2026-11-23',
                'signature' => 'A. Signer',
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
            'section' => 'suggestions', 'staff' => $staff->ulid,
        ]));
    }

    private function save(BasicInformation $staff, array $overrides = [])
    {
        return $this->actingAs($this->actor())->post(route('hr-appraisal.suggestions'),
            array_merge([
                'staff' => $staff->ulid,
                'section' => 'suggestions',
                'different_job_suggested' => 'yes',
                'different_job_details' => 'Registration Officer, for the exposure to field operations.',
                'acted_on' => '2026-11-23',
                'signature' => 'A. Signer',
            ], $overrides));
    }

    /* ----------------------------------------------------- the section */

    public function test_the_section_asks_the_question(): void
    {
        $response = $this->section($this->officer());

        $response->assertOk();
        $response->assertSee('Do you suggest the officer for a different job in the same');
    }

    public function test_the_detail_field_is_hidden_until_an_answer_is_shown_to_be_yes(): void
    {
        $response = $this->section($this->officer());

        $response->assertSee('style="display: none"', false);
    }

    public function test_a_yes_already_on_record_shows_the_details(): void
    {
        $staff = $this->officer();

        Suggestion::create([
            'basic_information_ulid' => $staff->ulid,
            'different_job_suggested' => true,
            'different_job_details' => 'Registration Officer, for the exposure to field operations.',
        ]);

        $this->section($staff)->assertSee('Registration Officer, for the exposure to field operations.');
    }

    /* -------------------------------------------------- saving the suggestion */

    public function test_a_yes_answer_with_detail_reaches_the_record(): void
    {
        $staff = $this->officer();

        $this->save($staff)->assertRedirect(route('hr-appraisal', [
            'section' => 'staff-declaration', 'staff' => $staff->ulid,
        ]));

        $suggestion = Suggestion::where('basic_information_ulid', $staff->ulid)->first();

        $this->assertTrue($suggestion->different_job_suggested);
        $this->assertSame('Registration Officer, for the exposure to field operations.',
            $suggestion->different_job_details);
    }

    public function test_a_no_answer_keeps_no_detail(): void
    {
        $staff = $this->officer();

        $this->save($staff, ['different_job_suggested' => 'no', 'different_job_details' => ''])
            ->assertRedirect();

        $suggestion = Suggestion::where('basic_information_ulid', $staff->ulid)->first();

        $this->assertFalse($suggestion->different_job_suggested);
        $this->assertNull($suggestion->different_job_details);
    }

    public function test_saving_again_corrects_the_one_suggestion_rather_than_adding_another(): void
    {
        $staff = $this->officer();

        $this->save($staff);
        $this->save($staff, ['different_job_details' => 'Revised reasoning for the move.']);

        $this->assertSame(1, Suggestion::where('basic_information_ulid', $staff->ulid)->count());
        $this->assertSame('Revised reasoning for the move.',
            Suggestion::where('basic_information_ulid', $staff->ulid)->first()->different_job_details);
    }

    public function test_the_question_is_required(): void
    {
        $this->save($this->officer(), ['different_job_suggested' => ''])
            ->assertSessionHasErrors('different_job_suggested');
    }

    public function test_detail_is_required_once_the_answer_is_yes(): void
    {
        $this->save($this->officer(), ['different_job_details' => ''])
            ->assertSessionHasErrors('different_job_details');
    }

    public function test_detail_is_not_required_when_the_answer_is_no(): void
    {
        $this->save($this->officer(), ['different_job_suggested' => 'no', 'different_job_details' => ''])
            ->assertSessionDoesntHaveErrors('different_job_details');
    }

    /* ------------------------------------------- who makes the suggestion */

    public function test_a_staff_account_may_not_make_the_suggestion(): void
    {
        $staff = $this->officer();

        $this->assertFalse(ReportingAuthority::mayCommentOn($this->account('User'), $staff));
    }

    public function test_an_administrator_makes_the_suggestion(): void
    {
        $staff = $this->officer();
        $administrator = $this->actor();

        $this->assertTrue(ReportingAuthority::mayCommentOn($administrator, $staff));

        $this->sectionAs($administrator, $staff)->assertSee('name="different_job_suggested"', false);
    }

    public function test_the_electoral_officer_of_the_officers_lga_makes_the_suggestion(): void
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

        $this->sectionAs($electoralOfficer, $staff)->assertSee('name="different_job_suggested"', false);
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

        $this->sectionAs($officerDownTheRoad, $staff)->assertDontSee('name="different_job_suggested"', false);
        $this->saveAs($officerDownTheRoad, $staff)->assertForbidden();
    }

    public function test_the_head_of_the_officers_department_makes_the_suggestion(): void
    {
        $staff = $this->officer();

        $head = $this->account('HOD EOPs');

        $this->assertTrue(ReportingAuthority::mayCommentOn($head, $staff));

        $this->sectionAs($head, $staff)->assertSee('name="different_job_suggested"', false);
        $this->saveAs($head, $staff)->assertRedirect();
    }

    public function test_the_head_of_another_department_only_reads_on(): void
    {
        $staff = $this->officer();

        $head = $this->account('HOD ICT');

        $this->assertFalse(ReportingAuthority::mayCommentOn($head, $staff));

        $this->sectionAs($head, $staff)->assertDontSee('name="different_job_suggested"', false);
        $this->saveAs($head, $staff)->assertForbidden();
    }

    public function test_an_unauthorized_viewer_sees_the_suggestion_already_on_record(): void
    {
        $staff = $this->officer();

        Suggestion::create([
            'basic_information_ulid' => $staff->ulid,
            'different_job_suggested' => true,
            'different_job_details' => 'Registration Officer, for the exposure to field operations.',
        ]);

        $html = $this->sectionAs($this->account('HOD ICT'), $staff)->getContent();

        $this->assertStringContainsString('Registration Officer, for the exposure to field operations.', $html);
        $this->assertStringContainsString('given by the Electoral Officer', $html);
    }
}
