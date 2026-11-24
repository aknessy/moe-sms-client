<?php

namespace Tests\Feature;

use App\Models\BasicInformation;
use App\Models\GapDelegate;
use App\Models\Posting;
use App\Models\PromotionBrief;
use App\Models\RecordAmendment;
use App\Models\Suggestion;
use App\Models\User;
use App\Support\Menu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * HOD GAP designates officers of their department, or posted to it, as
 * Registry Officers: they work as the head does, but sign and approve nothing
 * on the head's behalf.
 */
class GapDelegateTest extends TestCase
{
    use RefreshDatabase;

    private const GAP = 'Department of General Administration and Procurement';

    private function account(string $role, array $staffAttributes = []): User
    {
        return User::factory()->create([
            'basic_information_ulid' => BasicInformation::factory()->create($staffAttributes)->ulid,
            'role' => $role,
        ]);
    }

    private function gapHead(): User
    {
        return $this->account('HOD GAP', ['department' => self::GAP]);
    }

    private function gapStaff(string $role = 'User'): User
    {
        return $this->account($role, ['department' => self::GAP]);
    }

    private function designate(User $head, User $officer)
    {
        return $this->actingAs($head)->post(route('gap-delegates.store'), [
            'basic_information_ulid' => $officer->basic_information_ulid,
        ]);
    }

    private function delegate(): User
    {
        $head = $this->gapHead();
        $officer = $this->gapStaff();
        $this->designate($head, $officer);

        return $officer->fresh();
    }

    /* ------------------------------------------------------- designating */

    public function test_hod_gap_designates_an_officer_of_their_department(): void
    {
        $head = $this->gapHead();
        $officer = $this->gapStaff();

        $this->designate($head, $officer)->assertRedirect(route('gap-delegates'));

        $this->assertSame('Registry Officer', $officer->fresh()->role);
        $this->assertDatabaseHas('gap_delegates', ['basic_information_ulid' => $officer->basic_information_ulid]);
    }

    public function test_an_officer_posted_to_the_department_may_be_designated(): void
    {
        $head = $this->gapHead();
        $officer = $this->account('User', ['department' => 'Department of Electoral Operations']);

        Posting::create([
            'basic_information_ulid' => $officer->basic_information_ulid,
            'posting_type' => 'transfer',
            'to_department' => self::GAP,
            'effective_date' => '2026-01-05',
        ]);

        $this->designate($head, $officer)->assertSessionHasNoErrors();

        $this->assertSame('Registry Officer', $officer->fresh()->role);
    }

    public function test_an_officer_from_elsewhere_is_refused(): void
    {
        $head = $this->gapHead();
        $officer = $this->account('User', ['department' => 'Department of Electoral Operations']);

        $this->designate($head, $officer)->assertSessionHasErrors('basic_information_ulid');

        $this->assertSame('User', $officer->fresh()->role);
    }

    public function test_someone_who_already_holds_another_office_is_not_demoted(): void
    {
        $head = $this->gapHead();
        $officer = $this->gapStaff('HOD ICT');

        $this->designate($head, $officer)->assertSessionHasErrors('basic_information_ulid');

        $this->assertSame('HOD ICT', $officer->fresh()->role);
    }

    public function test_the_staff_must_be_picked(): void
    {
        $this->actingAs($this->gapHead())->post(route('gap-delegates.store'), [])
            ->assertSessionHasErrors(['basic_information_ulid' => 'Pick a staff.']);
    }

    public function test_the_designated_officer_is_told(): void
    {
        $officer = $this->gapStaff();
        $this->designate($this->gapHead(), $officer);

        $this->assertSame(1, \App\Models\Notification::where('user_id', $officer->id)->count());
    }

    public function test_revoking_restores_the_role_they_held_before(): void
    {
        $head = $this->gapHead();
        $officer = $this->gapStaff();
        $this->designate($head, $officer);

        $this->actingAs($head)->post(route('gap-delegates.revoke', $officer->basic_information_ulid))
            ->assertRedirect(route('gap-delegates'));

        $this->assertSame('User', $officer->fresh()->role);
        $this->assertSame(0, GapDelegate::count());
    }

    public function test_only_hod_gap_designates(): void
    {
        $officer = $this->gapStaff();

        foreach (['Administrator', 'Registry Officer', 'HOD ICT'] as $role) {
            $this->actingAs($this->account($role))->get(route('gap-delegates'))->assertForbidden();
            $this->actingAs($this->account($role))->post(route('gap-delegates.store'), [
                'basic_information_ulid' => $officer->basic_information_ulid,
            ])->assertForbidden();
        }

        $this->assertSame('User', $officer->fresh()->role);
    }

    public function test_the_head_sees_the_page_and_the_sidebar_entry(): void
    {
        $head = $this->gapHead();

        $this->actingAs($head)->get(route('gap-delegates'))->assertOk()->assertSee('Designate a Registry Officer');
        $this->assertContains('Registry Officer Delegates', array_column(Menu::forUser($head), 'label'));
    }

    /* ------------------------------------------- what a delegate does */

    public function test_a_delegate_has_the_same_sidebar_as_the_head_but_cannot_designate(): void
    {
        $delegate = $this->delegate();
        $labels = array_column(Menu::forUser($delegate), 'label');

        $this->assertContains('Performance Appraisal', $labels);
        $this->assertNotContains('ICT/Voter Registry', $labels);
        $this->assertNotContains('Registry Officer Delegates', $labels);

        $this->actingAs($delegate)->get(route('gap-delegates'))->assertForbidden();
    }

    public function test_a_delegate_is_kept_out_of_what_the_head_is(): void
    {
        $delegate = $this->delegate();

        foreach (['salary-structure', 'payroll', 'voter-registry'] as $route) {
            $this->actingAs($delegate)->get(route($route))->assertForbidden();
        }

        $this->actingAs($delegate)->get(route('allowances'))->assertOk();
    }

    public function test_a_delegate_reads_other_reports_but_cannot_sign_the_suggestions(): void
    {
        $delegate = $this->delegate();
        $staff = BasicInformation::factory()->create(['department' => 'Department of Electoral Operations']);
        PromotionBrief::create(['year' => now()->year, 'basic_information_ulid' => $staff->ulid, 'on_brief_at' => now()]);

        $this->actingAs($delegate)->get(route('hr-appraisal', ['section' => 'suggestions', 'staff' => $staff->ulid]))
            ->assertOk()
            ->assertDontSee('name="signature"', false)
            ->assertSee('Signing and acting on it is HOD GAP');

        $this->actingAs($delegate)->post(route('hr-appraisal.suggestions'), [
            'staff' => $staff->ulid, 'section' => 'suggestions',
            'different_job_suggested' => 'no', 'acted_on' => '2026-11-24', 'signature' => 'Delegate',
        ])->assertForbidden();

        $this->assertSame(0, Suggestion::count());
    }

    public function test_a_delegate_cannot_approve_an_amendment(): void
    {
        $delegate = $this->delegate();
        $staff = BasicInformation::factory()->create(['phone_number' => '08030000000']);

        $amendment = RecordAmendment::create([
            'basic_information_ulid' => $staff->ulid,
            'field' => 'phone_number', 'requested_value' => '08039999999', 'status' => 'pending',
        ]);

        $this->actingAs($delegate)->post(route('record-amendments.review', $amendment->ulid), [
            'decision' => 'approved',
        ])->assertForbidden();

        $this->assertSame('pending', $amendment->fresh()->status);
    }

    public function test_a_delegate_still_fills_their_own_report(): void
    {
        $delegate = $this->delegate();
        $own = $delegate->staff;
        PromotionBrief::create(['year' => now()->year, 'basic_information_ulid' => $own->ulid, 'on_brief_at' => now()]);

        $this->actingAs($delegate)->post(route('hr-appraisal.staff-declaration'), [
            'staff' => $own->ulid, 'section' => 'staff-declaration',
            'comments' => 'No comments.', 'declared_on' => '2026-11-24', 'signature' => 'Me',
        ])->assertRedirect();
    }

    public function test_a_registry_officer_who_is_not_designated_is_untouched(): void
    {
        $plain = $this->account('Registry Officer');

        $this->assertNotContains('Performance Appraisal', array_column(Menu::forUser($plain), 'label'));
        $this->actingAs($plain)->get(route('voter-registry'))->assertOk();
    }
}
