<?php

namespace Tests\Feature;

use App\Models\BasicInformation;
use App\Models\LeaveApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * How long a leave runs for.
 *
 * The 14/21/30 days a grade level carries is an *annual* leave entitlement.
 * Sick leave, study leave and the rest run for as long as the case calls for,
 * so for those the day count is asked for rather than worked out — and the
 * field is only readonly for annual leave.
 */
class LeaveDayCountTest extends TestCase
{
    use RefreshDatabase;

    private BasicInformation $staff;

    /** The officer signed in, applying for themselves. GL 5 carries 21 days. */
    private function officer(?int $gradeLevel = 5): User
    {
        $this->staff = BasicInformation::factory()->create(['grade_level' => $gradeLevel]);

        return User::factory()->create([
            'basic_information_ulid' => $this->staff->ulid,
            'role' => 'User',
        ]);
    }

    private function clerk(): User
    {
        return User::factory()->create([
            'basic_information_ulid' => BasicInformation::factory()->create()->ulid,
            'role' => 'Administrator',
        ]);
    }

    /** Every kind of leave but the annual one. */
    public static function leaveTypesSetByHand(): array
    {
        return array_map(fn ($type) => [$type],
            array_values(array_diff(array_keys(LeaveApplication::TYPES),
                                    [LeaveApplication::ENTITLEMENT_TYPE])));
    }

    /* ------------------------------------------ an officer, for themselves */

    public function test_annual_leave_still_runs_for_the_entitlement(): void
    {
        $this->actingAs($this->officer(5))->post(route('my-leave.store'), [
            'leave_type' => 'Annual Leave',
            'effective_date' => '2026-11-02',
        ])->assertRedirect(route('my-leave'));

        $this->assertSame(21, LeaveApplication::first()->days);
    }

    public function test_the_day_count_submitted_for_annual_leave_is_still_ignored(): void
    {
        // Readonly on screen, and overridden on save, so the field is more
        // than a suggestion.
        $this->actingAs($this->officer(5))->post(route('my-leave.store'), [
            'leave_type' => 'Annual Leave',
            'effective_date' => '2026-11-02',
            'days' => 300,
        ]);

        $this->assertSame(21, LeaveApplication::first()->days);
    }

    #[DataProvider('leaveTypesSetByHand')]
    public function test_another_kind_of_leave_runs_for_the_days_applied_for(string $type): void
    {
        $this->actingAs($this->officer(5))->post(route('my-leave.store'), [
            'leave_type' => $type,
            'effective_date' => '2026-11-02',
            'days' => 7,
        ])->assertRedirect(route('my-leave'));

        $application = LeaveApplication::first();

        // Seven, not the 21 their grade level carries.
        $this->assertSame(7, $application->days);
        $this->assertSame('2026-11-09', $application->resumption_date);
    }

    #[DataProvider('leaveTypesSetByHand')]
    public function test_another_kind_of_leave_must_say_how_many_days(string $type): void
    {
        $this->actingAs($this->officer(5))->post(route('my-leave.store'), [
            'leave_type' => $type,
            'effective_date' => '2026-11-02',
        ])->assertSessionHasErrors('days');

        $this->assertSame(0, LeaveApplication::count());
    }

    public function test_a_leave_of_no_days_is_refused(): void
    {
        $this->actingAs($this->officer(5))->post(route('my-leave.store'), [
            'leave_type' => 'Sick Leave',
            'effective_date' => '2026-11-02',
            'days' => 0,
        ])->assertSessionHasErrors('days');

        $this->assertSame(0, LeaveApplication::count());
    }

    public function test_a_leave_longer_than_a_year_is_refused(): void
    {
        $this->actingAs($this->officer(5))->post(route('my-leave.store'), [
            'leave_type' => 'Study Leave',
            'effective_date' => '2026-11-02',
            'days' => 400,
        ])->assertSessionHasErrors('days');
    }

    /* ---------------------------------------------- no grade level on file */

    public function test_an_officer_with_no_grade_level_cannot_take_annual_leave(): void
    {
        $this->actingAs($this->officer(null))->post(route('my-leave.store'), [
            'leave_type' => 'Annual Leave',
            'effective_date' => '2026-11-02',
        ])->assertSessionHasErrors('leave_type');

        $this->assertSame(0, LeaveApplication::count());
    }

    public function test_an_officer_with_no_grade_level_may_still_take_sick_leave(): void
    {
        // Nothing about sick leave depends on the grade level, so a record
        // missing one is no reason to refuse it.
        $this->actingAs($this->officer(null))->post(route('my-leave.store'), [
            'leave_type' => 'Sick Leave',
            'effective_date' => '2026-11-02',
            'days' => 5,
        ])->assertRedirect(route('my-leave'));

        $this->assertSame(5, LeaveApplication::first()->days);
    }

    public function test_the_form_is_offered_to_an_officer_with_no_grade_level(): void
    {
        // The whole form used to be withheld from them.
        $this->actingAs($this->officer(null))->get(route('my-leave'))
            ->assertOk()
            ->assertSee('Submit Application')
            ->assertSee('name="days"', false);
    }

    /* ------------------------------------------ a clerk, for someone else */

    public function test_the_clerk_sees_annual_leave_run_for_the_entitlement(): void
    {
        $staff = BasicInformation::factory()->create(['grade_level' => 12]);

        $this->actingAs($this->clerk())->post(route('hr-leave.store'), [
            'basic_information_ulid' => $staff->ulid,
            'leave_type' => 'Annual Leave',
            'effective_date' => '2026-11-02',
            'days' => 3,
        ])->assertRedirect(route('hr-leave'));

        $this->assertSame(30, LeaveApplication::first()->days);
    }

    public function test_the_clerk_sets_the_days_for_another_kind_of_leave(): void
    {
        $staff = BasicInformation::factory()->create(['grade_level' => 12]);

        $this->actingAs($this->clerk())->post(route('hr-leave.store'), [
            'basic_information_ulid' => $staff->ulid,
            'leave_type' => 'Maternity Leave',
            'effective_date' => '2026-11-02',
            'days' => 90,
        ])->assertRedirect(route('hr-leave'));

        $this->assertSame(90, LeaveApplication::first()->days);
        $this->assertSame('2027-01-31', LeaveApplication::first()->resumption_date);
    }

    public function test_the_clerk_must_say_how_many_days_for_another_kind(): void
    {
        $staff = BasicInformation::factory()->create(['grade_level' => 12]);

        $this->actingAs($this->clerk())->post(route('hr-leave.store'), [
            'basic_information_ulid' => $staff->ulid,
            'leave_type' => 'Casual Leave',
            'effective_date' => '2026-11-02',
        ])->assertSessionHasErrors('days');
    }

    public function test_the_clerk_may_record_sick_leave_for_an_ungraded_officer(): void
    {
        $staff = BasicInformation::factory()->create(['grade_level' => null]);

        $this->actingAs($this->clerk())->post(route('hr-leave.store'), [
            'basic_information_ulid' => $staff->ulid,
            'leave_type' => 'Sick Leave',
            'effective_date' => '2026-11-02',
            'days' => 4,
        ])->assertRedirect(route('hr-leave'));

        $this->assertSame(4, LeaveApplication::first()->days);
    }

    public function test_the_clerk_cannot_record_annual_leave_for_an_ungraded_officer(): void
    {
        $staff = BasicInformation::factory()->create(['grade_level' => null]);

        $this->actingAs($this->clerk())->post(route('hr-leave.store'), [
            'basic_information_ulid' => $staff->ulid,
            'leave_type' => 'Annual Leave',
            'effective_date' => '2026-11-02',
        ])->assertSessionHasErrors('basic_information_ulid');

        $this->assertSame(0, LeaveApplication::count());
    }

    /* ----------------------------------------------------- the rule itself */

    public function test_only_annual_leave_follows_the_grade_level(): void
    {
        $this->assertTrue(LeaveApplication::entitlementApplies('Annual Leave'));

        foreach (array_keys(LeaveApplication::TYPES) as $type) {
            if ($type !== 'Annual Leave') {
                $this->assertFalse(LeaveApplication::entitlementApplies($type),
                    "{$type} should not follow the grade level.");
            }
        }

        $this->assertFalse(LeaveApplication::entitlementApplies(null));
    }

    public function test_the_field_is_not_readonly_in_the_markup(): void
    {
        // Which it is depends on the type chosen, so it is decided on the page
        // rather than baked into the field.
        $html = $this->actingAs($this->officer(5))->get(route('my-leave'))->getContent();

        $this->assertStringNotContainsString('readonly data-days', $html);
        $this->assertStringContainsString('data-entitlement="21"', $html);
    }
}
