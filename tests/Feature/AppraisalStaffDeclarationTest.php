<?php

namespace Tests\Feature;

use App\Models\BasicInformation;
use App\Models\Lga;
use App\Models\Posting;
use App\Models\PromotionBrief;
use App\Models\StaffDeclaration;
use App\Models\State;
use App\Models\User;
use App\Support\ReportingAuthority;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The Staff Declaration section: the officer's own certification that they
 * have seen the report and that the reporting officer has discussed it with
 * them, with whatever comments they have to make.
 */
class AppraisalStaffDeclarationTest extends TestCase
{
    use RefreshDatabase;

    private ?User $administrator = null;

    private function administrator(): User
    {
        return $this->administrator ??= User::factory()->create([
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

    /** The account belonging to the officer themselves. */
    private function self(BasicInformation $staff, string $role = 'User'): User
    {
        return User::factory()->create([
            'basic_information_ulid' => $staff->ulid,
            'role' => $role,
        ]);
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
            'section' => 'staff-declaration', 'staff' => $staff->ulid,
        ]));
    }

    private function saveAs(User $viewer, BasicInformation $staff, array $overrides = [])
    {
        return $this->actingAs($viewer)->post(route('hr-appraisal.staff-declaration'),
            array_merge([
                'staff' => $staff->ulid,
                'section' => 'staff-declaration',
                'comments' => 'I have no comments to make.',
                'declared_on' => '2026-11-23',
                'signature' => 'B. Asuquo',
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

    /* ----------------------------------------------------- the section */

    public function test_the_section_shows_the_certification_wording(): void
    {
        $staff = $this->officer();

        $this->sectionAs($this->self($staff), $staff)
            ->assertOk()
            ->assertSee('I certify that I have seen the contents of this report');
    }

    public function test_comments_already_on_record_show(): void
    {
        $staff = $this->officer();

        StaffDeclaration::create([
            'basic_information_ulid' => $staff->ulid,
            'comments' => 'No comments, the report is fair.',
        ]);

        $this->sectionAs($this->self($staff), $staff)
            ->assertSee('No comments, the report is fair.');
    }

    /* -------------------------------------------------- saving the declaration */

    public function test_the_comments_reach_the_record(): void
    {
        $staff = $this->officer();

        $this->saveAs($this->self($staff), $staff)->assertRedirect(route('hr-appraisal', [
            'section' => 'reporting-officer-declaration', 'staff' => $staff->ulid,
        ]));

        $declaration = StaffDeclaration::where('basic_information_ulid', $staff->ulid)->first();

        $this->assertSame('I have no comments to make.', $declaration->comments);
    }

    public function test_saving_again_corrects_the_one_declaration_rather_than_adding_another(): void
    {
        $staff = $this->officer();
        $self = $this->self($staff);

        $this->saveAs($self, $staff);
        $this->saveAs($self, $staff, ['comments' => 'On reflection, I would add this.']);

        $this->assertSame(1, StaffDeclaration::where('basic_information_ulid', $staff->ulid)->count());
        $this->assertSame('On reflection, I would add this.',
            StaffDeclaration::where('basic_information_ulid', $staff->ulid)->first()->comments);
    }

    public function test_the_comments_are_required(): void
    {
        $this->saveAs($this->self($staff = $this->officer()), $staff, ['comments' => ''])
            ->assertSessionHasErrors('comments');
    }

    public function test_the_date_and_signature_reach_the_record(): void
    {
        $staff = $this->officer();

        $this->saveAs($this->self($staff), $staff);

        $declaration = StaffDeclaration::sole();

        $this->assertSame('2026-11-23', $declaration->declared_on->toDateString());
        $this->assertSame('B. Asuquo', $declaration->signature);
    }

    public function test_the_date_and_signature_are_required(): void
    {
        $staff = $this->officer();
        $self = $this->self($staff);

        $this->saveAs($self, $staff, ['declared_on' => ''])->assertSessionHasErrors('declared_on');
        $this->saveAs($self, $staff, ['signature' => ''])->assertSessionHasErrors('signature');
    }

    public function test_the_form_asks_for_a_date_and_signature(): void
    {
        $staff = $this->officer();

        $this->sectionAs($this->self($staff), $staff)
            ->assertSee('name="declared_on"', false)
            ->assertSee('name="signature"', false);
    }

    /* ------------------------------------------- who makes the declaration */

    public function test_a_reporting_officer_may_not_make_the_declaration(): void
    {
        $staff = $this->officer();

        $head = $this->account('HOD EOPs');

        $this->assertTrue(ReportingAuthority::mayCommentOn($head, $staff));
        $this->assertFalse(ReportingAuthority::mayDeclare($head, $staff));
    }

    public function test_the_officer_themselves_makes_the_declaration(): void
    {
        $staff = $this->officer();
        $self = $this->self($staff);

        $this->assertTrue(ReportingAuthority::mayDeclare($self, $staff));

        $this->sectionAs($self, $staff)->assertSee('name="comments"', false);
        $this->saveAs($self, $staff)->assertRedirect();
    }

    public function test_an_administrator_makes_the_declaration(): void
    {
        $staff = $this->officer();
        $administrator = $this->administrator();

        $this->assertTrue(ReportingAuthority::mayDeclare($administrator, $staff));

        $this->sectionAs($administrator, $staff)->assertSee('name="comments"', false);
        $this->saveAs($administrator, $staff)->assertRedirect();
    }

    public function test_the_electoral_officer_of_the_officers_lga_only_reads_on(): void
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

        $this->assertFalse(ReportingAuthority::mayDeclare($electoralOfficer, $staff));

        $this->sectionAs($electoralOfficer, $staff)->assertDontSee('name="comments"', false);
        $this->saveAs($electoralOfficer, $staff)->assertForbidden();
    }

    public function test_the_head_of_the_officers_department_only_reads_on(): void
    {
        $staff = $this->officer();

        $head = $this->account('HOD EOPs');

        $this->assertFalse(ReportingAuthority::mayDeclare($head, $staff));

        $this->sectionAs($head, $staff)->assertDontSee('name="comments"', false);
        $this->saveAs($head, $staff)->assertForbidden();
    }

    public function test_a_self_service_officer_always_views_their_own_report_whatever_staff_is_named(): void
    {
        $staff = $this->officer();
        $self = $this->self($staff);
        $somebodyElse = $this->officer();

        StaffDeclaration::create([
            'basic_information_ulid' => $staff->ulid,
            'comments' => 'This is mine.',
        ]);
        StaffDeclaration::create([
            'basic_information_ulid' => $somebodyElse->ulid,
            'comments' => 'This is not mine.',
        ]);

        // Naming somebody else in the url changes nothing: a self-service
        // account reads its own record off the session, never off the
        // request.
        $html = $this->sectionAs($self, $somebodyElse)->getContent();

        $this->assertStringContainsString('This is mine.', $html);
        $this->assertStringNotContainsString('This is not mine.', $html);
    }

    public function test_a_self_service_officer_always_declares_for_themselves_whatever_staff_is_named(): void
    {
        $staff = $this->officer();
        $self = $this->self($staff);
        $somebodyElse = $this->officer();

        // The hidden field a tampered form carried is ignored the same way.
        $this->saveAs($self, $somebodyElse)->assertRedirect();

        $this->assertSame($staff->ulid, StaffDeclaration::sole()->basic_information_ulid);
    }

    public function test_an_unauthorized_viewer_sees_the_declaration_already_on_record(): void
    {
        $staff = $this->officer();

        StaffDeclaration::create([
            'basic_information_ulid' => $staff->ulid,
            'comments' => 'No comments, the report is fair.',
        ]);

        $html = $this->sectionAs($this->account('HOD ICT'), $staff)->getContent();

        $this->assertStringContainsString('No comments, the report is fair.', $html);
        $this->assertStringContainsString('made by the officer themselves', $html);
    }
}
