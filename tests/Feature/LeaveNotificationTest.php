<?php

namespace Tests\Feature;

use App\Models\BasicInformation;
use App\Models\DepartmentHead;
use App\Models\LeaveApplication;
use App\Models\Lga;
use App\Models\Notification;
use App\Models\Posting;
use App\Models\State;
use App\Models\User;
use App\Support\ApplicationRouting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * An application has to reach somebody. Until the heads of department have
 * dashboards of their own, that means a notice: to whoever the officer answers
 * to day to day — the Electoral Officer of the LGA they are posted to, or the
 * head of their department at headquarters — and to the administrators, who
 * see everything.
 */
class LeaveNotificationTest extends TestCase
{
    use RefreshDatabase;

    private function accountFor(BasicInformation $staff, string $role): User
    {
        return User::factory()->create([
            'basic_information_ulid' => $staff->ulid,
            'role' => $role,
        ]);
    }

    private function officer(array $attributes = []): BasicInformation
    {
        return BasicInformation::factory()->create($attributes + ['grade_level' => 5]);
    }

    /** Post an officer to an LGA, which is what takes them off headquarters. */
    private function postTo(BasicInformation $staff, Lga $lga): void
    {
        Posting::create([
            'basic_information_ulid' => $staff->ulid,
            'posting_type' => 'deployment',
            'to_lga' => $lga->id,
            'effective_date' => '2025-01-06',
            'deployed_as' => 'Registration Officer',
        ]);
    }

    private function anLgaAwayFromHeadquarters(): Lga
    {
        $state = State::where('name', config('posting.default_state'))->firstOrFail();

        return Lga::where('state_id', $state->id)
            ->where('lga_name', '!=', config('posting.default_lga'))
            ->firstOrFail();
    }

    private function apply(BasicInformation $staff): LeaveApplication
    {
        return LeaveApplication::create([
            'basic_information_ulid' => $staff->ulid,
            'leave_type' => 'Annual Leave',
            'effective_date' => '2026-11-02',
            'days' => 21,
        ]);
    }

    /* --------------------------------------------------- who is told */

    public function test_the_administrators_are_told(): void
    {
        $admin = $this->accountFor($this->officer(['surname' => 'Admin']), 'Administrator');
        $applicant = $this->officer(['surname' => 'Asuquo', 'firstname' => 'Bassey']);

        $this->apply($applicant);

        $notice = Notification::for($admin)->first();

        $this->assertNotNull($notice);
        $this->assertStringContainsString('Asuquo', $notice->title);
        $this->assertStringContainsString('Annual Leave', $notice->body);
    }

    public function test_the_head_of_department_is_told(): void
    {
        $head = $this->accountFor(
            $this->officer(['surname' => 'Head', 'department' => 'ICT & Voter Registry']),
            'HOD ICT',
        );

        DepartmentHead::updateOrCreate(['department' => 'ICT & Voter Registry'],
                                       ['role' => 'HOD ICT']);

        $applicant = $this->officer(['department' => 'ICT & Voter Registry']);

        $this->apply($applicant);

        $this->assertSame(1, Notification::for($head)->count());
    }

    public function test_a_head_of_another_department_is_not_told(): void
    {
        $other = $this->accountFor(
            $this->officer(['surname' => 'Other', 'department' => 'Operations']),
            'HOD EOPs',
        );

        DepartmentHead::updateOrCreate(['department' => 'Operations'], ['role' => 'HOD EOPs']);
        DepartmentHead::updateOrCreate(['department' => 'ICT & Voter Registry'],
                                       ['role' => 'HOD ICT']);

        $this->apply($this->officer(['department' => 'ICT & Voter Registry']));

        $this->assertSame(0, Notification::for($other)->count());
    }

    public function test_the_electoral_officer_of_the_lga_is_told(): void
    {
        $lga = $this->anLgaAwayFromHeadquarters();

        $eoStaff = $this->officer(['surname' => 'Eo', 'designation' => 'Electoral Officer']);
        $this->postTo($eoStaff, $lga);
        $eo = $this->accountFor($eoStaff, 'User');

        $applicant = $this->officer(['department' => 'Operations']);
        $this->postTo($applicant, $lga);

        $this->apply($applicant);

        $this->assertSame(1, Notification::for($eo)->count());
    }

    public function test_an_electoral_officer_of_another_lga_is_not_told(): void
    {
        $state = State::where('name', config('posting.default_state'))->firstOrFail();
        $lgas = Lga::where('state_id', $state->id)
            ->where('lga_name', '!=', config('posting.default_lga'))
            ->take(2)->get();

        $elsewhere = $this->officer(['surname' => 'Elsewhere', 'designation' => 'Electoral Officer']);
        $this->postTo($elsewhere, $lgas[0]);
        $theirAccount = $this->accountFor($elsewhere, 'User');

        $applicant = $this->officer();
        $this->postTo($applicant, $lgas[1]);

        $this->apply($applicant);

        $this->assertSame(0, Notification::for($theirAccount)->count());
    }

    public function test_the_applicant_is_never_told_of_their_own_application(): void
    {
        // Even where they are the administrator.
        $applicant = $this->officer();
        $account = $this->accountFor($applicant, 'Administrator');

        $this->apply($applicant);

        $this->assertSame(0, Notification::for($account)->count());
    }

    public function test_a_department_with_nobody_named_still_reaches_the_administrators(): void
    {
        $admin = $this->accountFor($this->officer(['surname' => 'Admin']), 'Administrator');

        DepartmentHead::updateOrCreate(['department' => 'Legal & Litigation'], ['role' => null]);

        $this->apply($this->officer(['department' => 'Legal & Litigation']));

        $this->assertSame(1, Notification::for($admin)->count());
    }

    public function test_one_notice_each_however_many_hats_they_wear(): void
    {
        // The head of a department may also be an administrator.
        $head = $this->accountFor(
            $this->officer(['surname' => 'Both', 'department' => 'Operations']),
            'Administrator',
        );

        DepartmentHead::updateOrCreate(['department' => 'Operations'],
                                       ['role' => 'Administrator']);

        $this->apply($this->officer(['department' => 'Operations']));

        $this->assertSame(1, Notification::for($head)->count());
    }

    public function test_an_inactive_account_is_not_told(): void
    {
        $admin = $this->accountFor($this->officer(['surname' => 'Gone']), 'Administrator');
        $admin->update(['is_active' => 0]);

        $this->apply($this->officer());

        $this->assertSame(0, Notification::for($admin)->count());
    }

    /* ------------------------------------------ raised either way */

    public function test_an_application_made_by_the_officer_notifies(): void
    {
        $admin = $this->accountFor($this->officer(['surname' => 'Admin']), 'Administrator');

        $applicant = $this->officer();
        $account = $this->accountFor($applicant, 'User');

        $this->actingAs($account)->post(route('my-leave.store'), [
            'leave_type' => 'Annual Leave',
            'effective_date' => '2026-11-02',
        ]);

        $this->assertSame(1, Notification::for($admin)->count());
    }

    public function test_an_application_recorded_by_a_clerk_notifies_too(): void
    {
        $admin = $this->accountFor($this->officer(['surname' => 'Admin']), 'Administrator');
        $clerk = $this->accountFor($this->officer(['surname' => 'Clerk']), 'Registry Officer');

        $this->actingAs($clerk)->post(route('hr-leave.store'), [
            'basic_information_ulid' => $this->officer()->ulid,
            'leave_type' => 'Annual Leave',
            'effective_date' => '2026-11-02',
        ]);

        $this->assertSame(1, Notification::for($admin)->count());
    }

    /* ------------------------------------------------- the notice itself */

    public function test_the_notice_points_at_the_letter(): void
    {
        $admin = $this->accountFor($this->officer(['surname' => 'Admin']), 'Administrator');

        $application = $this->apply($this->officer());

        $this->assertSame(route('hr-leave.show', $application->ulid),
            Notification::for($admin)->first()->url);
    }

    public function test_a_notice_starts_unread_and_can_be_cleared(): void
    {
        $admin = $this->accountFor($this->officer(['surname' => 'Admin']), 'Administrator');

        $this->apply($this->officer());

        $notice = Notification::for($admin)->first();
        $this->assertFalse($notice->is_read);

        $this->actingAs($admin)->get(route('notifications.open', $notice->ulid))
            ->assertRedirect($notice->url);

        $this->assertTrue($notice->fresh()->is_read);
    }

    public function test_an_account_cannot_open_somebody_elses_notice(): void
    {
        $admin = $this->accountFor($this->officer(['surname' => 'Admin']), 'Administrator');
        $other = $this->accountFor($this->officer(['surname' => 'Other']), 'Administrator');

        $this->apply($this->officer());

        // Both are administrators, so both were told — but each only has
        // their own notice.
        $theirs = Notification::for($admin)->first();

        $this->actingAs($other)->get(route('notifications.open', $theirs->ulid))
            ->assertNotFound();
    }

    public function test_the_navbar_counts_what_is_waiting(): void
    {
        $admin = $this->accountFor($this->officer(['surname' => 'Admin']), 'Administrator');

        $this->apply($this->officer(['surname' => 'One']));
        $this->apply($this->officer(['surname' => 'Two']));

        $html = $this->actingAs($admin)->get(route('dashboard'))->getContent();

        // The badge, which only renders when something is unread.
        $this->assertMatchesRegularExpression('/bg-error[^>]*>\s*2\s*<\/span>/', $html);
    }

    public function test_all_notices_can_be_cleared_at_once(): void
    {
        $admin = $this->accountFor($this->officer(['surname' => 'Admin']), 'Administrator');

        $this->apply($this->officer(['surname' => 'One']));
        $this->apply($this->officer(['surname' => 'Two']));

        $this->actingAs($admin)->post(route('notifications.read-all'));

        $this->assertSame(0, Notification::for($admin)->unread()->count());
    }

    public function test_an_officer_on_the_user_role_may_read_their_notices(): void
    {
        // The menu does not carry notifications, but they are addressed to the
        // account, so the page is open to every role.
        $account = $this->accountFor($this->officer(), 'User');

        $this->actingAs($account)->get(route('notifications'))->assertOk();
    }

    /* ------------------------------------------------------ the routing */

    public function test_at_headquarters_the_superior_is_the_head_of_department(): void
    {
        $staff = $this->officer(['department' => 'Operations']);

        $this->assertSame('The Head of Department, Operations',
            ApplicationRouting::immediateSuperior($staff)['title']);
    }

    public function test_a_department_named_in_full_does_not_stutter(): void
    {
        // Departments are on record in full, so "The Head of Department,
        // Department of Electoral Operations" has to lose a Department.
        $staff = $this->officer(['department' => 'Department of Electoral Operations']);

        $this->assertSame('The Head, Department of Electoral Operations',
            ApplicationRouting::immediateSuperior($staff)['title']);
    }

    public function test_an_officer_with_no_department_still_has_a_desk_named(): void
    {
        $staff = $this->officer(['department' => null]);

        $this->assertSame('The Head of Department',
            ApplicationRouting::immediateSuperior($staff)['title']);
    }

    public function test_away_from_headquarters_the_superior_is_the_electoral_officer(): void
    {
        $lga = $this->anLgaAwayFromHeadquarters();
        $staff = $this->officer(['department' => 'Operations']);
        $this->postTo($staff, $lga);

        $this->assertSame("The Electoral Officer, {$lga->lga_name} LGA",
            ApplicationRouting::immediateSuperior($staff)['title']);
    }
}
