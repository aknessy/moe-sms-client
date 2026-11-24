<?php

namespace Tests\Feature;

use App\Models\BasicInformation;
use App\Models\LeaveRosterEntry;
use App\Models\User;
use App\Support\Menu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The Leave Roster of the Department of General Administration and
 * Procurement, put together by HOD GAP and the Registry Officers they
 * designate.
 */
class LeaveRosterTest extends TestCase
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

    private function gapOfficer(array $attributes = []): BasicInformation
    {
        return BasicInformation::factory()->create(['department' => self::GAP] + $attributes);
    }

    private function add(User $viewer, BasicInformation $staff, array $overrides = [])
    {
        return $this->actingAs($viewer)->post(route('leave-roster.store'), array_merge([
            'basic_information_ulid' => $staff->ulid,
            'leave_type' => 'Casual Leave',
            'start_date' => now()->year . '-03-02',
            'days' => 10,
        ], $overrides));
    }

    private function delegate(User $head): User
    {
        $officer = $this->account('User', ['department' => self::GAP]);
        $this->actingAs($head)->post(route('gap-delegates.store'), [
            'basic_information_ulid' => $officer->basic_information_ulid,
        ]);

        return $officer->fresh();
    }

    public function test_the_sidebar_carries_the_roster_for_hod_gap_and_their_delegate_only(): void
    {
        $head = $this->gapHead();

        $this->assertContains('Leave Roster', array_column(Menu::forUser($head), 'label'));
        $this->assertContains('Leave Roster', array_column(Menu::forUser($this->delegate($head)), 'label'));
        $this->assertNotContains('Leave Roster', array_column(Menu::forUser($this->account('Administrator')), 'label'));
    }

    public function test_hod_gap_adds_an_officer_to_the_roster(): void
    {
        $staff = $this->gapOfficer();

        $this->add($this->gapHead(), $staff)->assertRedirect(route('leave-roster', ['year' => now()->year]));

        $entry = LeaveRosterEntry::sole();

        $this->assertSame(self::GAP, $entry->department);
        $this->assertSame(10, $entry->days);
        $this->assertSame(now()->year . '-03-12', $entry->resumption_date->toDateString());
    }

    public function test_the_registry_officer_they_designated_adds_too(): void
    {
        $delegate = $this->delegate($this->gapHead());

        $this->add($delegate, $this->gapOfficer())->assertSessionHasNoErrors();

        $this->assertSame(1, LeaveRosterEntry::count());
    }

    public function test_annual_leave_defaults_to_the_officers_entitlement(): void
    {
        $staff = $this->gapOfficer(['grade_level' => 8]);

        $this->add($this->gapHead(), $staff, ['leave_type' => 'Annual Leave', 'days' => ''])
            ->assertSessionHasNoErrors();

        $this->assertSame($staff->fresh()->leaveDays(), LeaveRosterEntry::sole()->days);
    }

    public function test_any_other_kind_of_leave_must_say_how_long(): void
    {
        $this->add($this->gapHead(), $this->gapOfficer(), ['days' => ''])
            ->assertSessionHasErrors('days');
    }

    public function test_an_officer_of_another_department_is_refused(): void
    {
        $outsider = BasicInformation::factory()->create(['department' => 'Department of Electoral Operations']);

        $this->add($this->gapHead(), $outsider)->assertSessionHasErrors('basic_information_ulid');

        $this->assertSame(0, LeaveRosterEntry::count());
    }

    public function test_the_staff_must_be_picked(): void
    {
        $this->add($this->gapHead(), $this->gapOfficer(), ['basic_information_ulid' => ''])
            ->assertSessionHasErrors(['basic_information_ulid' => 'Pick a staff.']);
    }

    public function test_the_page_lists_the_years_entries_only(): void
    {
        $head = $this->gapHead();
        $staff = $this->gapOfficer(['surname' => 'Rostered']);

        $this->add($head, $staff);
        $this->add($head, $this->gapOfficer(['surname' => 'NextYear']), ['start_date' => (now()->year + 1) . '-01-05']);

        // The staff picker lists every name, so read what the page loaded for
        // the year rather than searching its text.
        $response = $this->actingAs($head)->get(route('leave-roster'))->assertOk();

        $this->assertSame(['Rostered'], $response->viewData('entries')->pluck('staff.surname')->all());
    }

    public function test_another_departments_roster_can_be_read_but_not_changed(): void
    {
        $head = $this->gapHead();
        $other = 'Department of Electoral Operations';

        \App\Models\Department::firstOrCreate(['department' => $other]);

        $entry = LeaveRosterEntry::create([
            'basic_information_ulid' => BasicInformation::factory()->create(['department' => $other, 'surname' => 'Elsewhere'])->ulid,
            'department' => $other,
            'leave_type' => 'Casual Leave',
            'start_date' => now()->year . '-04-06',
            'days' => 5,
            'created_by' => $head->basic_information_ulid,
        ]);

        $response = $this->actingAs($head)->get(route('leave-roster', ['department' => $other]))
            ->assertOk()
            ->assertSee("another department's roster", false);

        $this->assertSame(['Elsewhere'], $response->viewData('entries')->pluck('staff.surname')->all());
        $response->assertDontSee(route('leave-roster.remove', $entry->ulid));

        // Reading is not changing: the entry cannot be taken off from here.
        $this->actingAs($head)->post(route('leave-roster.remove', $entry->ulid))->assertNotFound();
        $this->assertSame(1, LeaveRosterEntry::count());
    }

    public function test_the_designated_registry_officer_reads_other_rosters_too(): void
    {
        $head = $this->gapHead();
        $delegate = $this->delegate($head);
        $other = 'Department of Electoral Operations';

        \App\Models\Department::firstOrCreate(['department' => $other]);

        $this->actingAs($delegate)->get(route('leave-roster', ['department' => $other]))
            ->assertOk()->assertSee("another department's roster", false);
    }

    /** A department with a head account, the way Settings maps one. */
    private function otherDepartmentWithHead(string $name = 'Department of Electoral Operations', string $role = 'HOD EOPs'): array
    {
        \App\Models\Department::updateOrCreate(['department' => $name], ['role' => $role]);

        return [$name, $this->account($role, ['department' => $name])];
    }

    public function test_the_gap_desk_can_remind_a_department_that_has_made_no_roster(): void
    {
        $head = $this->gapHead();
        [$department, $otherHead] = $this->otherDepartmentWithHead();

        $page = $this->actingAs($head)->get(route('leave-roster', ['department' => $department]));
        $page->assertSee('Remind the head or delegate to create their roster');

        $this->actingAs($head)->post(route('leave-roster.remind'), [
            'department' => $department, 'year' => now()->year,
        ])->assertSessionHasNoErrors()->assertSessionHas('status');

        $this->assertSame(1, \App\Models\Notification::where('user_id', $otherHead->id)->count());
    }

    public function test_the_reminder_reaches_the_officer_delegate_of_the_department_too(): void
    {
        $gap = $this->gapHead();
        [$department, $otherHead] = $this->otherDepartmentWithHead();

        // An officer of that department, designated by its head.
        $delegate = $this->account('Registry Officer', ['department' => $department]);
        \App\Models\GapDelegate::create([
            'basic_information_ulid' => $delegate->basic_information_ulid,
            'designated_by' => $otherHead->basic_information_ulid,
        ]);

        // An ordinary Registry Officer of the same department, never designated.
        $plain = $this->account('Registry Officer', ['department' => $department]);

        $this->actingAs($gap)->post(route('leave-roster.remind'), [
            'department' => $department, 'year' => now()->year,
        ])->assertSessionHasNoErrors();

        $this->assertSame(1, \App\Models\Notification::where('user_id', $otherHead->id)->count());
        $this->assertSame(1, \App\Models\Notification::where('user_id', $delegate->id)->count());
        $this->assertSame(0, \App\Models\Notification::where('user_id', $plain->id)->count());
    }

    public function test_a_department_with_only_a_delegate_and_no_head_account_is_still_reminded(): void
    {
        $gap = $this->gapHead();
        $department = 'Department of Electoral Operations';
        \App\Models\Department::updateOrCreate(['department' => $department], ['role' => 'HOD EOPs']);

        $head = BasicInformation::factory()->create(['department' => $department]);
        $delegate = $this->account('Registry Officer', ['department' => $department]);
        \App\Models\GapDelegate::create([
            'basic_information_ulid' => $delegate->basic_information_ulid,
            'designated_by' => $head->ulid,
        ]);

        $this->actingAs($gap)->post(route('leave-roster.remind'), [
            'department' => $department, 'year' => now()->year,
        ])->assertSessionHasNoErrors();

        $this->assertSame(1, \App\Models\Notification::where('user_id', $delegate->id)->count());
    }

    public function test_the_reminder_links_to_the_roster_the_head_can_open(): void
    {
        $head = $this->gapHead();
        [$department, $otherHead] = $this->otherDepartmentWithHead();

        $this->actingAs($head)->post(route('leave-roster.remind'), ['department' => $department, 'year' => now()->year]);

        $this->assertSame(
            route('leave-roster', ['year' => now()->year]),
            \App\Models\Notification::where('user_id', $otherHead->id)->value('url'),
        );
    }

    public function test_the_registry_officer_they_designated_can_remind_too(): void
    {
        $delegate = $this->delegate($this->gapHead());
        [$department, $otherHead] = $this->otherDepartmentWithHead();

        $this->actingAs($delegate)->post(route('leave-roster.remind'), [
            'department' => $department, 'year' => now()->year,
        ])->assertSessionHasNoErrors();

        $this->assertSame(1, \App\Models\Notification::where('user_id', $otherHead->id)->count());
    }

    public function test_no_reminder_goes_to_a_department_that_already_has_a_roster(): void
    {
        $head = $this->gapHead();
        [$department, $otherHead] = $this->otherDepartmentWithHead();

        LeaveRosterEntry::create([
            'basic_information_ulid' => BasicInformation::factory()->create(['department' => $department])->ulid,
            'department' => $department, 'leave_type' => 'Casual Leave',
            'start_date' => now()->year . '-04-06', 'days' => 5, 'created_by' => $head->basic_information_ulid,
        ]);

        $this->actingAs($head)->get(route('leave-roster', ['department' => $department]))
            ->assertDontSee('Remind the head or delegate to create their roster');

        $this->actingAs($head)->post(route('leave-roster.remind'), [
            'department' => $department, 'year' => now()->year,
        ])->assertSessionHasErrors('department');

        $this->assertSame(0, \App\Models\Notification::where('user_id', $otherHead->id)->count());
    }

    public function test_a_department_nobody_heads_says_so_rather_than_sending_nothing(): void
    {
        \App\Models\Department::firstOrCreate(['department' => 'Unheaded Unit']);

        $this->actingAs($this->gapHead())->post(route('leave-roster.remind'), [
            'department' => 'Unheaded Unit', 'year' => now()->year,
        ])->assertSessionHasErrors('department');
    }

    public function test_nobody_outside_the_gap_desk_can_remind(): void
    {
        [$department] = $this->otherDepartmentWithHead();

        foreach (['Administrator', 'User'] as $role) {
            $this->actingAs($this->account($role))->post(route('leave-roster.remind'), [
                'department' => $department, 'year' => now()->year,
            ])->assertForbidden();
        }
    }

    public function test_a_department_head_builds_their_own_roster_and_reads_no_other(): void
    {
        [$department, $otherHead] = $this->otherDepartmentWithHead();
        $staff = BasicInformation::factory()->create(['department' => $department]);

        $this->assertContains('Leave Roster', array_column(
            collect(Menu::forUser($otherHead))->firstWhere('label', 'Human Resources')['children'], 'label'));

        $this->add($otherHead, $staff)->assertSessionHasNoErrors();
        $this->assertSame($department, LeaveRosterEntry::sole()->department);

        // Asking for GAP's roster still shows their own.
        $response = $this->actingAs($otherHead)->get(route('leave-roster', ['department' => self::GAP]))->assertOk();
        $this->assertSame($department, $response->viewData('viewing'));
        $response->assertDontSee('Remind the head or delegate');
    }

    public function test_a_head_cannot_roster_an_officer_of_another_department(): void
    {
        [, $otherHead] = $this->otherDepartmentWithHead();

        $this->add($otherHead, $this->gapOfficer())->assertSessionHasErrors('basic_information_ulid');
    }

    public function test_it_opens_on_their_own_department_unless_another_is_asked_for(): void
    {
        $response = $this->actingAs($this->gapHead())->get(route('leave-roster'))->assertOk();

        $this->assertSame(self::GAP, $response->viewData('viewing'));
        $this->assertTrue($response->viewData('viewingOwn'));
    }

    public function test_an_empty_year_shows_an_empty_state_with_an_icon(): void
    {
        $this->actingAs($this->gapHead())->get(route('leave-roster'))
            ->assertOk()
            ->assertSee('Nobody is on the ' . now()->year . ' roster yet')
            ->assertSee('<svg', false)
            ->assertDontSee('0 records');
    }

    public function test_the_count_sits_at_the_foot_and_the_year_dropdown_in_the_header(): void
    {
        $head = $this->gapHead();
        $this->add($head, $this->gapOfficer());

        $html = $this->actingAs($head)->get(route('leave-roster'))->getContent();

        $this->assertStringContainsString('1 record', $html);
        // The dropdown comes before the table, the count after it.
        $this->assertLessThan(strpos($html, '<table'), strpos($html, 'id="roster-year"'));
        $this->assertGreaterThan(strpos($html, '</table>'), strpos($html, '1 record'));
    }

    public function test_an_entry_is_removed(): void
    {
        $head = $this->gapHead();
        $this->add($head, $this->gapOfficer());

        $this->actingAs($head)->post(route('leave-roster.remove', LeaveRosterEntry::sole()->ulid))
            ->assertRedirect();

        $this->assertSame(0, LeaveRosterEntry::count());
    }

    public function test_nobody_else_may_open_or_build_it(): void
    {
        $staff = $this->gapOfficer();

        foreach (['Administrator', 'Registry Officer', 'HOD ICT', 'User'] as $role) {
            $viewer = $this->account($role);

            $this->actingAs($viewer)->get(route('leave-roster'))->assertForbidden();
            $this->add($viewer, $staff)->assertForbidden();
        }

        $this->assertSame(0, LeaveRosterEntry::count());
    }
}
