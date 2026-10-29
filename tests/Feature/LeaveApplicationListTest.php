<?php

namespace Tests\Feature;

use App\Models\BasicInformation;
use App\Models\LeaveApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Leave Management lists the applications officers have made, newest first.
 */
class LeaveApplicationListTest extends TestCase
{
    use RefreshDatabase;

    private function actor(): User
    {
        return User::factory()->create(['role' => 'Administrator']);
    }

    private function page()
    {
        return $this->actingAs($this->actor())->get(route('hr-leave'));
    }

    private function apply(array $overrides = [])
    {
        return $this->actingAs($this->actor())->post(route('hr-leave.store'), array_merge([
            'basic_information_ulid' => BasicInformation::factory()->create()->ulid,
            'leave_type' => 'Annual Leave',
            'effective_date' => '2026-11-02',
            'days' => 21,
        ], $overrides));
    }

    /** An application already on file, made at a given moment. */
    private function existing(string $surname, string $madeAt, array $overrides = []): LeaveApplication
    {
        $staff = BasicInformation::factory()->create(['surname' => $surname]);

        // Made then, so the timestamps the list sorts on are the real ones.
        return $this->travelTo($madeAt, fn () => LeaveApplication::create(array_merge([
            'basic_information_ulid' => $staff->ulid,
            'leave_type' => 'Annual Leave',
            'effective_date' => '2026-11-02',
            'days' => 21,
        ], $overrides)));
    }

    /* ----------------------------------------------------- newest first */

    public function test_the_newest_application_is_listed_first(): void
    {
        $this->existing('Older', '2026-01-01 09:00:00');
        $this->existing('Newest', '2026-10-30 09:00:00');
        $this->existing('Middling', '2026-06-01 09:00:00');

        $order = $this->page()->viewData('applications')
            ->map(fn ($a) => $a->staff->surname)->all();

        $this->assertSame(['Newest', 'Middling', 'Older'], $order);
    }

    public function test_applications_made_at_the_same_moment_keep_a_settled_order(): void
    {
        $this->existing('First', '2026-10-30 09:00:00');
        $this->existing('Second', '2026-10-30 09:00:00');

        // The later row wins, so the list does not shuffle between loads.
        $order = $this->page()->viewData('applications')
            ->map(fn ($a) => $a->staff->surname)->all();

        $this->assertSame(['Second', 'First'], $order);
    }

    public function test_an_application_just_made_goes_to_the_top(): void
    {
        $this->existing('Older', '2026-01-01 09:00:00');

        $this->apply(['basic_information_ulid' => BasicInformation::factory()
            ->create(['surname' => 'Fresh'])->ulid]);

        $this->assertSame('Fresh', $this->page()->viewData('applications')->first()->staff->surname);
    }

    /* ---------------------------------------------------- what it shows */

    public function test_the_list_heads_every_column_asked_for(): void
    {
        $response = $this->page();

        foreach (['S/N', 'Staff Name', 'Grade Level', 'Effective Date',
                  'No. of Days', 'Resumption Date', 'Action'] as $heading) {
            $response->assertSee($heading, false);
        }
    }

    public function test_the_row_carries_the_officers_name_and_grade_level(): void
    {
        $staff = BasicInformation::factory()->create([
            'surname' => 'Eyo', 'firstname' => 'Bassey', 'grade_level' => 'GL 12',
        ]);

        $this->apply(['basic_information_ulid' => $staff->ulid]);

        $response = $this->page();

        $response->assertSee('Eyo');
        $response->assertSee('Bassey');
        $response->assertSee('GL 12');
        $response->assertSee($staff->staff_number);
    }

    public function test_the_row_carries_the_dates_and_the_day_count(): void
    {
        $this->apply(['effective_date' => '2026-11-02', 'days' => 21]);

        $response = $this->page();

        $response->assertSee('2026-11-02');
        $response->assertSee('21');
        $response->assertSee('2026-11-23');
    }

    public function test_the_serial_number_runs_on_across_pages(): void
    {
        foreach (range(1, 17) as $i) {
            $this->existing('Officer' . $i, '2026-01-' . str_pad($i, 2, '0', STR_PAD_LEFT) . ' 09:00:00');
        }

        $second = $this->actingAs($this->actor())->get(route('hr-leave', ['page' => 2]));

        // 15 to a page, so the second page starts at 16.
        $this->assertSame(16, $second->viewData('applications')->firstItem());
    }

    public function test_the_list_says_so_when_there_is_nothing_on_it(): void
    {
        $this->page()->assertSee('No leave applications yet.');
    }

    /* ------------------------------------------------- resumption date */

    public function test_the_officer_resumes_the_day_after_the_leave_ends(): void
    {
        // Twenty-one days from 2 November runs to the 22nd; back on the 23rd.
        $this->apply(['effective_date' => '2026-11-02', 'days' => 21]);

        $this->assertSame('2026-11-23', LeaveApplication::first()->resumption_date);
    }

    public function test_a_single_day_of_leave_is_back_the_next_day(): void
    {
        $this->apply(['effective_date' => '2026-11-02', 'days' => 1]);

        $this->assertSame('2026-11-03', LeaveApplication::first()->resumption_date);
    }

    public function test_the_resumption_date_carries_across_a_month_end(): void
    {
        $this->apply(['effective_date' => '2026-12-20', 'days' => 21]);

        $this->assertSame('2027-01-10', LeaveApplication::first()->resumption_date);
    }

    public function test_correcting_the_day_count_moves_the_resumption_date(): void
    {
        $this->apply(['effective_date' => '2026-11-02', 'days' => 21]);

        $application = LeaveApplication::first();
        $application->update(['days' => 10]);

        // Derived on save rather than entered, so it cannot fall out of step.
        $this->assertSame('2026-11-12', $application->fresh()->resumption_date);
    }

    public function test_the_resumption_date_is_not_taken_from_the_form(): void
    {
        // The field on screen is readonly; what is submitted is ignored.
        $this->apply(['effective_date' => '2026-11-02', 'days' => 21,
                      'resumption_date' => '2030-01-01']);

        $this->assertSame('2026-11-23', LeaveApplication::first()->resumption_date);
    }

    /* --------------------------------------------- making an application */

    public function test_an_application_is_recorded(): void
    {
        $this->apply()->assertRedirect(route('hr-leave'));

        $this->assertSame(1, LeaveApplication::count());
        $this->assertSame('Annual Leave', LeaveApplication::first()->leave_type);
    }

    public function test_an_application_starts_out_pending(): void
    {
        $this->apply();

        $this->assertSame('pending', LeaveApplication::first()->status);
    }

    public function test_an_application_needs_an_officer_a_type_a_date_and_a_day_count(): void
    {
        $this->actingAs($this->actor())->post(route('hr-leave.store'), [])
            ->assertSessionHasErrors(['basic_information_ulid', 'leave_type', 'effective_date', 'days']);

        $this->assertSame(0, LeaveApplication::count());
    }

    public function test_a_leave_of_no_days_is_refused(): void
    {
        $this->apply(['days' => 0])->assertSessionHasErrors('days');

        $this->assertSame(0, LeaveApplication::count());
    }

    public function test_a_type_outside_the_list_is_refused(): void
    {
        $this->apply(['leave_type' => 'Sabbatical'])->assertSessionHasErrors('leave_type');
    }

    public function test_an_officer_not_on_the_roll_is_refused(): void
    {
        $this->apply(['basic_information_ulid' => 'not-a-real-ulid'])
            ->assertSessionHasErrors('basic_information_ulid');
    }

    public function test_the_form_offers_every_kind_of_leave(): void
    {
        $response = $this->page();

        foreach (LeaveApplication::TYPES as $type) {
            $response->assertSee($type);
        }
    }

    public function test_only_serving_officers_may_be_applied_for(): void
    {
        BasicInformation::factory()->create(['surname' => 'Retired', 'is_retired' => 1]);
        BasicInformation::factory()->create(['surname' => 'Serving']);

        $options = $this->page()->viewData('staffOptions')->implode(' ');

        $this->assertStringContainsString('Serving', $options);
        $this->assertStringNotContainsString('Retired', $options);
    }
}
