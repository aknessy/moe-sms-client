<?php

namespace Tests\Feature;

use App\Models\BasicInformation;
use App\Models\LeaveApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Grade level and step are numbers, and how many days of leave an officer may
 * take follows from the grade level alone.
 */
class LeaveEntitlementTest extends TestCase
{
    use RefreshDatabase;

    private function actor(): User
    {
        return User::factory()->create(['role' => 'Administrator']);
    }

    private function officer(?int $gradeLevel, ?int $step = 1): BasicInformation
    {
        return BasicInformation::factory()->create([
            'grade_level' => $gradeLevel, 'step' => $step,
        ]);
    }

    /* ----------------------------------------------------- the entitlement */

    public static function gradeLevelsAndTheirDays(): array
    {
        return [
            'junior, lowest band' => [1, 14],
            'junior, lowest band top' => [3, 14],
            'junior, upper band' => [4, 21],
            'junior, upper band top' => [6, 21],
            'senior, first level' => [7, 30],
            'senior, mid' => [12, 30],
            'senior, top' => [17, 30],
        ];
    }

    #[DataProvider('gradeLevelsAndTheirDays')]
    public function test_a_grade_level_carries_its_days_of_leave(int $gradeLevel, int $days): void
    {
        $this->assertSame($days, $this->officer($gradeLevel)->leaveDays());
    }

    public function test_the_three_bands_are_fourteen_twenty_one_and_thirty(): void
    {
        $this->assertSame([14, 21, 30],
            array_column(BasicInformation::LEAVE_ENTITLEMENT, 'days'));
    }

    public function test_an_officer_with_no_grade_level_has_nothing_to_work_it_out_from(): void
    {
        $this->assertNull($this->officer(null, null)->leaveDays());
    }

    /* -------------------------------------- what reaches the application */

    public function test_the_day_count_is_the_officers_entitlement(): void
    {
        $staff = $this->officer(2);

        $this->actingAs($this->actor())->post(route('hr-leave.store'), [
            'basic_information_ulid' => $staff->ulid,
            'leave_type' => 'Annual Leave',
            'effective_date' => '2026-11-02',
        ])->assertRedirect(route('hr-leave'));

        $this->assertSame(14, LeaveApplication::first()->days);
        // Fourteen days from the 2nd runs to the 15th; back on the 16th.
        $this->assertSame('2026-11-16', LeaveApplication::first()->resumption_date);
    }

    public function test_a_senior_officer_gets_thirty(): void
    {
        $staff = $this->officer(14);

        $this->actingAs($this->actor())->post(route('hr-leave.store'), [
            'basic_information_ulid' => $staff->ulid,
            'leave_type' => 'Annual Leave',
            'effective_date' => '2026-11-02',
        ]);

        $this->assertSame(30, LeaveApplication::first()->days);
        $this->assertSame('2026-12-02', LeaveApplication::first()->resumption_date);
    }

    public function test_an_officer_with_no_grade_level_cannot_apply(): void
    {
        $staff = $this->officer(null, null);

        $this->actingAs($this->actor())->post(route('hr-leave.store'), [
            'basic_information_ulid' => $staff->ulid,
            'leave_type' => 'Annual Leave',
            'effective_date' => '2026-11-02',
        ])->assertSessionHasErrors('basic_information_ulid');

        $this->assertSame(0, LeaveApplication::count());
    }

    public function test_the_page_carries_what_each_officer_is_entitled_to(): void
    {
        $junior = $this->officer(2);
        $senior = $this->officer(12);

        $entitlements = $this->actingAs($this->actor())->get(route('hr-leave'))
            ->viewData('entitlements');

        $this->assertSame(14, $entitlements[$junior->ulid]);
        $this->assertSame(30, $entitlements[$senior->ulid]);
    }

    public function test_the_day_count_is_only_fixed_for_annual_leave(): void
    {
        // It used to be readonly outright. The entitlement is an *annual*
        // leave entitlement, so which it is now depends on the type chosen,
        // and is decided on the page rather than baked into the field.
        $this->actingAs($this->actor())->get(route('hr-leave'))
            ->assertDontSee('readonly data-days', false)
            ->assertSee('name="days"', false);
    }

    /* ------------------------------------------- the columns are numbers */

    public function test_grade_level_and_step_are_held_as_numbers(): void
    {
        $staff = $this->officer(9, 4);

        $this->assertSame(9, $staff->fresh()->grade_level);
        $this->assertSame(4, $staff->fresh()->step);
    }

    public function test_the_columns_are_integers_in_the_schema(): void
    {
        foreach (['grade_level', 'step'] as $column) {
            $this->assertSame('integer',
                Schema::getColumnType('basic_informations', $column),
                "{$column} should be an integer");
        }
    }

    public function test_a_number_reads_as_a_grade_level_on_screen(): void
    {
        $staff = $this->officer(9, 4);

        $this->assertSame('GL 09', $staff->grade_level_label);
        $this->assertSame('Step 4', $staff->step_label);
        $this->assertSame('GL 09 / Step 4', $staff->grade_step);
    }

    public function test_an_officer_with_nothing_on_file_reads_as_a_dash(): void
    {
        $this->assertSame('—', $this->officer(null, null)->grade_step);
    }

    public function test_the_wizard_refuses_a_grade_level_that_is_not_a_number(): void
    {
        $crossRiver = \App\Models\State::where('name', 'Cross River')->firstOrFail();

        $this->actingAs($this->actor())->post(route('create'), [
            'form-type' => 'basic-information',
            'file-number' => 'GL/F/0001',
            'last-name' => 'Eyo', 'first-name' => 'Bassey', 'gender' => 'Male',
            'department' => 'Department of Electoral Operations', 'division' => 'Calabar HQ',
            'designation' => 'Executive Officer (EO)',
            // Free text used to be accepted here.
            'grade-level' => 'GL 09', 'step' => 'Step 4',
            'date-of-birth' => '1985-04-12', 'date-of-employment' => '2010-01-04',
            'phone-number' => '08030000001', 'email' => 'bassey@inec.gov.ng',
            'state-of-origin' => $crossRiver->id,
            'lga-of-origin' => $crossRiver->lgas()->value('id'),
            'pfa' => 'Stanbic', 'rsa' => 'PEN1', 'nhis' => '1',
            'address' => 'Calabar',
        ])->assertSessionHasErrors(['grade-level', 'step']);
    }

    public function test_the_wizard_refuses_a_grade_level_outside_the_scale(): void
    {
        $this->assertSame([1, 17], BasicInformation::GRADE_LEVELS);
    }

    /* ---------------------------------------------- sorting is now plain */

    public function test_the_roll_still_sorts_by_standing(): void
    {
        $this->officer(7)->update(['surname' => 'Junior']);
        $this->officer(14)->update(['surname' => 'Senior']);

        $order = $this->actingAs($this->actor())->get(route('nominal-roll'))
            ->viewData('staff')->pluck('surname')->all();

        $this->assertLessThan(array_search('Junior', $order), array_search('Senior', $order));
    }
}
